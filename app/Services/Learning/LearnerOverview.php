<?php

namespace App\Services\Learning;

use App\Models\LearnerSkill;
use App\Models\LearningGoal;
use App\Models\LearnerSkillSnapshot;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;

final class LearnerOverview
{
    public function __construct(private SkillSnapshots $snapshots, private StudyTime $studyTime) {}

    public function skills(User $user): Collection
    {
        $skills = LearnerSkill::where('user_id', $user->id)->get()->keyBy('skill_type');
        if (! $this->snapshots->ready()) return $skills;
        $latest = LearnerSkillSnapshot::where('user_id', $user->id)->orderByDesc('assessed_at')->orderByDesc('id')->get()->unique('skill');
        foreach ($latest as $snapshot) {
            $old = $skills->get($snapshot->skill);
            $oldDate = $old?->last_assessed_at ?? $old?->updated_at;
            if ($oldDate && $oldDate->gt($snapshot->assessed_at)) continue;
            $skills[$snapshot->skill] = (object) ['skill_type' => $snapshot->skill, 'mastery_score' => $snapshot->score,
                'score_max' => $snapshot->score_scale === 'ielts_band_0_9' ? 9 : 100,
                'score_label' => $snapshot->score_scale === 'ielts_band_0_9' ? 'IELTS Writing' : ($snapshot->skill === 'speaking' ? 'Điểm luyện phát âm' : 'Điểm luyện viết'),
                'last_assessed_at' => $snapshot->assessed_at];
        }
        return $skills;
    }

    public function recommendations(User $user, Collection $skills, ?Lesson $nextLesson, ?LearningGoal $goal = null): array
    {
        $items = [];
        if ($this->snapshots->ready()) {
            $history = LearnerSkillSnapshot::where('user_id', $user->id)->orderByDesc('assessed_at')->orderByDesc('id')->limit(10)->get();
            foreach (['writing', 'speaking'] as $skill) {
                $counts = [];
                foreach ($history->where('skill', $skill) as $snapshot) {
                    foreach (collect($snapshot->issues ?? [])->unique(fn ($issue) => $issue['category'].'|'.$issue['original']) as $issue) {
                        $key = $issue['category'].'|'.$issue['original'];
                        $counts[$key] = ($counts[$key] ?? 0) + 1;
                    }
                }
                if (collect($counts)->max() >= 2) {
                    $items[] = $this->item('repeated_'.$skill, $skill, 'Sửa lỗi '.($skill === 'writing' ? 'Writing' : 'phát âm').' lặp lại',
                        'Một lỗi xuất hiện trong ít nhất 2 lần chấm gần đây.');
                }
            }
        }
        $weakest = $skills->filter(fn ($skill) => in_array($skill->skill_type, ['listening', 'speaking', 'reading', 'writing'])
            && ($skill->score_max ?? 100) === 100 && $skill->mastery_score < 70)->sortBy('mastery_score')->first();
        if ($weakest) $items[] = $this->item('weak_skill', $weakest->skill_type, 'Củng cố '.ucfirst($weakest->skill_type), 'Điểm luyện tập hiện tại: '.$weakest->mastery_score.'/100.');
        if ($goal) {
            $deadline = $goal->target_date ? ' · Hạn '.$goal->target_date->format('d/m/Y') : '';
            $items[] = $this->goalItem($goal, $deadline);
        }
        $lastActivity = $this->studyTime->lastActivity($user);
        if (! $lastActivity || $lastActivity->lt(today()->subDays(3))) {
            $items[] = $this->item('return_to_study', 'speaking', 'Khởi động lại thói quen học', 'Bắt đầu với một bài luyện nói ngắn hôm nay.');
        }
        if ($nextLesson) $items[] = ['rule' => 'unfinished_lesson', 'title' => 'Tiếp tục bài học còn dang dở', 'reason' => $nextLesson->title,
            'url' => route('lessons.show', $nextLesson->id), 'icon' => 'book', 'color' => 'blue'];
        foreach (['speaking', 'writing', 'reading', 'listening'] as $skill) {
            if (! $skills->has($skill)) $items[] = $this->item('missing_'.$skill, $skill, 'Đánh giá '.ucfirst($skill).' lần đầu', 'Luyện tập để có dữ liệu năng lực của bạn.');
        }
        if (count($items) < 3) $items[] = $this->item('practice', 'reading', 'Ôn tập theo trình độ', 'Duy trì luyện tập để củng cố kiến thức.');
        $selected = collect($items)->unique(fn ($item) => $this->destination($item['url']))->take(3)->values();
        $goalItem = collect($items)->first(fn ($item) => str_starts_with($item['rule'], 'goal_'));
        if ($goalItem && ! $selected->contains(fn ($item) => str_starts_with($item['rule'], 'goal_'))) {
            $matching = $selected->first(fn ($item) => $this->destination($item['url']) === $this->destination($goalItem['url']));
            if ($matching) $goalItem['reason'] = $matching['reason'].' · '.$goalItem['reason'];
            $selected = $selected->filter(fn ($item) => $this->destination($item['url']) !== $this->destination($goalItem['url']))->take(2)->push($goalItem)->values();
        }
        return $selected->all();
    }

    private function goalItem(LearningGoal $goal, string $deadline): array
    {
        if ($goal->framework === 'toeic') {
            return ['rule' => 'goal_toeic', 'title' => 'Luyện Listening cho mục tiêu TOEIC',
                'reason' => $goal->label().$deadline.' · Củng cố kỹ năng nghe.', 'url' => route('practice.index', ['skill' => 'listening']),
                'icon' => 'headphones', 'color' => 'green'];
        }
        if ($goal->framework === 'cefr' && in_array($goal->target, ['C1', 'C2'], true)) {
            return ['rule' => 'goal_cefr', 'title' => 'Khám phá khóa học CEFR '.$goal->target,
                'reason' => 'Mục tiêu '.$goal->label().$deadline, 'url' => route('courses.index', ['level' => $goal->target]), 'icon' => 'book', 'color' => 'blue'];
        }
        $preset = $goal->framework === 'ielts' ? match (true) {
            (float) $goal->target < 5.5 => 'Foundation',
            (float) $goal->target < 6.5 => '5.5',
            (float) $goal->target < 7 => '6.5',
            default => '7.0+',
        } : $goal->target;
        return ['rule' => 'goal_'.$goal->framework, 'title' => 'Luyện Writing hướng tới '.$goal->label(),
            'reason' => 'Mục tiêu '.$goal->label().$deadline.($preset !== $goal->target ? ' · Nhóm bài luyện '.$preset : ''),
            'url' => route('ai-tutor.writing.index', ['framework' => $goal->framework, 'target' => $preset]), 'icon' => 'document', 'color' => 'purple'];
    }

    private function destination(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        return $path === '/practice' ? $url : (string) $path;
    }

    private function item(string $rule, string $skill, string $title, string $reason): array
    {
        [$url, $icon, $color] = match ($skill) {
            'speaking' => [route('ai.speaking.index'), 'mic', 'green'],
            'writing' => [route('ai-tutor.writing.index'), 'document', 'purple'],
            'listening' => [route('practice.index', ['skill' => 'listening']), 'headphones', 'green'],
            default => [route('practice.index', ['skill' => 'reading']), 'book', 'orange'],
        };
        return compact('rule', 'title', 'reason', 'url', 'icon', 'color');
    }
}
