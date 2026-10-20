<?php

namespace TDSoft\AiTutor\Writing;

final class WritingComparison
{
    public static function between(array $current, array $previous, int $revision, ?string $currentOriginal = null, ?string $previousOriginal = null): ?array
    {
        $scale = $current['score_scale'] ?? 'practice_0_100';
        if ($scale !== ($previous['score_scale'] ?? 'practice_0_100')
            || array_diff(array_keys($current['criteria']), array_keys($previous['criteria']))
            || array_diff(array_keys($previous['criteria']), array_keys($current['criteria']))) {
            return null;
        }
        $delta = static fn ($now, $before) => is_numeric($now) && is_numeric($before) ? round((float) $now - (float) $before, 2) : null;
        $criteria = [];
        foreach ($current['criteria'] as $key => $criterion) {
            $old = $previous['criteria'][$key];
            $criteria[$key] = [
                'previous' => $old['score'], 'current' => $criterion['score'],
                'delta' => $criterion['status'] === 'assessed' && $old['status'] === 'assessed'
                    ? $delta($criterion['score'], $old['score']) : null,
            ];
        }

        return ['previous_revision' => $revision, 'score_scale' => $scale,
            'previous_score' => $previous['overall_score'], 'current_score' => $current['overall_score'],
            'overall_delta' => $delta($current['overall_score'], $previous['overall_score']),
            'criteria' => $criteria, 'previous_issue_count' => count($previous['issues'] ?? []),
            'current_issue_count' => count($current['issues'] ?? []),
            'issue_changes' => $currentOriginal !== null && $previousOriginal !== null
                ? self::issues($current['issues'] ?? [], $previous['issues'] ?? [], $currentOriginal, $previousOriginal) : null];
    }

    private static function issues(array $current, array $previous, string $currentOriginal, string $previousOriginal): array
    {
        $unverified = 0;
        $grounded = static function (array $issues, string $original) use (&$unverified): array {
            $result = [];
            foreach ($issues as $issue) {
                $quote = $issue['original'] ?? '';
                if (! is_string($quote) || trim($quote) === '' || ! str_contains($original, $quote)
                    || ! in_array($issue['category'] ?? null, ['grammar', 'vocabulary', 'coherence', 'task_response', 'style'], true)) {
                    $unverified++;

                    continue;
                }
                // Match exact category + quote, never moving offsets or fuzzy similarity.
                $key = json_encode([$issue['category'], $quote], JSON_THROW_ON_ERROR);
                $result[$key] ??= ['category' => $issue['category'], 'original' => $quote,
                    'replacement' => $issue['replacement'] ?? '', 'explanation' => $issue['explanation'] ?? ''];
            }

            return $result;
        };
        $now = $grounded($current, $currentOriginal);
        $before = $grounded($previous, $previousOriginal);
        $recurring = array_values(array_intersect_key($now, $before));
        $new = array_values(array_diff_key($now, $before));
        $notReported = array_values(array_diff_key($before, $now));
        foreach ($notReported as &$issue) {
            $issue['original_still_present'] = str_contains($currentOriginal, $issue['original']);
        }
        unset($issue);
        $group = static fn (array $items) => ['count' => count($items), 'items' => array_slice($items, 0, 5)];

        return ['recurring' => $group($recurring), 'not_reported' => $group($notReported),
            'newly_reported' => $group($new), 'unverified_count' => $unverified];
    }
}
