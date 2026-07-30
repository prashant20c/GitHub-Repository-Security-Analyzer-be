<?php

declare(strict_types=1);

namespace App\Services\Analytics;

final class TrendAnalyzer
{
    /**
     * @param array<int, array{overall_health_score:int}> $history
     */
    public function trendDirection(array $history): string
    {
        if (count($history) < 2) {
            return 'stable';
        }

        usort($history, static function (array $left, array $right): int {
            return strcmp(
                (string) ($left['created_at'] ?? ''),
                (string) ($right['created_at'] ?? '')
            );
        });

        $latest = (int) $history[array_key_last($history)]['overall_health_score'];
        $previous = (int) $history[array_key_first($history)]['overall_health_score'];
        $delta = $latest - $previous;

        return match (true) {
            $delta >= 5 => 'improving',
            $delta <= -5 => 'worsening',
            default => 'stable',
        };
    }
}
