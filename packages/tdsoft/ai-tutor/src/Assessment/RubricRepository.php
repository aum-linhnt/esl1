<?php

namespace TDSoft\AiTutor\Assessment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\SubjectEnglish\EnglishProfile;

final class RubricRepository
{
    /** Trusted server-side provisioning only; deliberately no public/admin route yet. */
    public function provision(string $key, string $skill, string $task, string $promptVersion, RubricDefinition $definition): array
    {
        if (! preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $key)
            || ! in_array($task, EnglishProfile::TASKS[$skill] ?? [], true)
            || ! preg_match('/^[a-zA-Z0-9._-]{1,64}$/D', $promptVersion)) {
            throw new AiException('AI_RUBRIC_INVALID');
        }

        return DB::transaction(function () use ($key, $skill, $task, $promptVersion, $definition) {
            DB::table('tutor_ai_assessment_rubrics')->insertOrIgnore([
                'id' => (string) Str::uuid(), 'key' => $key, 'skill' => $skill, 'task' => $task,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $rubric = DB::table('tutor_ai_assessment_rubrics')->where('key', $key)->lockForUpdate()->first();
            if (! $rubric || $rubric->skill !== $skill || $rubric->task !== $task) {
                throw new AiException('AI_RUBRIC_CONTEXT_CHANGED');
            }
            $criteria = $definition->criteria;
            ksort($criteria);
            foreach ($criteria as &$criterion) {
                ksort($criterion);
            }
            unset($criterion);
            $content = json_encode($criteria, JSON_THROW_ON_ERROR);
            $fingerprint = hash('sha256', $promptVersion.'\n'.$content);
            $existing = DB::table('tutor_ai_assessment_rubric_versions')
                ->where('rubric_id', $rubric->id)->where('fingerprint', $fingerprint)->first();
            if ($existing) {
                return $this->snapshot($existing->id);
            }
            $id = (string) Str::uuid();
            $version = 1 + (int) DB::table('tutor_ai_assessment_rubric_versions')->where('rubric_id', $rubric->id)->max('version');
            DB::table('tutor_ai_assessment_rubric_versions')->insert([
                'id' => $id, 'rubric_id' => $rubric->id, 'version' => $version,
                'prompt_version' => $promptVersion, 'criteria' => $content,
                'fingerprint' => $fingerprint, 'created_at' => now(),
            ]);

            return $this->snapshot($id);
        }, 3);
    }

    public function snapshot(string $id): array
    {
        $version = DB::table('tutor_ai_assessment_rubric_versions')->where('id', $id)->first();
        if (! $version) {
            throw new AiException('AI_RUBRIC_NOT_FOUND');
        }
        $rubric = DB::table('tutor_ai_assessment_rubrics')->where('id', $version->rubric_id)->first();
        if (! $rubric) {
            throw new AiException('AI_RUBRIC_NOT_FOUND');
        }
        $definition = new RubricDefinition(json_decode($version->criteria, true, flags: JSON_THROW_ON_ERROR));

        return [
            'id' => $version->id, 'key' => $rubric->key, 'skill' => $rubric->skill, 'task' => $rubric->task,
            'version' => (int) $version->version, 'prompt_version' => $version->prompt_version,
            'criteria' => $definition->criteria, 'fingerprint' => $version->fingerprint,
            'score_scale' => str_starts_with($version->prompt_version, 'ielts-band-') ? 'ielts_band_0_9' : 'practice_0_100',
        ];
    }
}
