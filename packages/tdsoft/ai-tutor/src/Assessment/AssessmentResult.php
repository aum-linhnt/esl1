<?php

namespace TDSoft\AiTutor\Assessment;

use TDSoft\AiTutor\Core\AiException;

final readonly class AssessmentResult
{
    public function __construct(public array $criteria, public ?float $overallScore, public string $feedback) {}

    /** $evidenceTypes comes from trusted provider capabilities, never model output/browser. */
    public static function fromArray(array $data, RubricDefinition $rubric, array $evidenceTypes = ['text']): self
    {
        if (! is_array($data['criteria'] ?? null)
            || array_diff(array_keys($data['criteria']), array_keys($rubric->criteria))
            || array_diff(array_keys($rubric->criteria), array_keys($data['criteria']))
            || ! is_string($data['feedback'] ?? null) || strlen($data['feedback']) > 20000) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $result = [];
        $complete = true;
        $total = 0;
        foreach ($rubric->criteria as $key => $definition) {
            $criterion = $data['criteria'][$key];
            if (! is_array($criterion) || ! in_array($criterion['status'] ?? null, ['assessed', 'not_available'], true)) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            // A transcript does not demonstrate pronunciation, even if the model claims it does.
            if (! in_array($definition['evidence_type'], $evidenceTypes, true) || $criterion['status'] === 'not_available') {
                $result[$key] = ['status' => 'not_available', 'score' => null, 'evidence' => []];
                $complete = false;

                continue;
            }
            $score = $criterion['score'] ?? null;
            $evidence = $criterion['evidence'] ?? null;
            if ((! is_int($score) && ! is_float($score)) || ! is_finite((float) $score)
                || $score < 0 || $score > 100 || ! is_array($evidence) || ! array_is_list($evidence)
                || $evidence === [] || count($evidence) > 20) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            foreach ($evidence as $item) {
                if (! is_string($item) || trim($item) === '' || strlen($item) > 2000) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
            }
            $result[$key] = ['status' => 'assessed', 'score' => (float) $score, 'evidence' => $evidence];
            $total += $score * $definition['weight'] / 100;
        }

        // Ignore provider-supplied overall scores; calculate only from a complete rubric.
        return new self($result, $complete ? round($total, 2) : null, $data['feedback']);
    }

    public function toArray(): array
    {
        return ['criteria' => $this->criteria, 'overall_score' => $this->overallScore,
            'feedback' => $this->feedback, 'score_scale' => 'practice_0_100'];
    }
}
