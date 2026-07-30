<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Analytics\TrendAnalyzer;
use PHPUnit\Framework\TestCase;

final class TrendAnalyzerTest extends TestCase
{
    public function test_it_marks_a_rising_score_as_improving(): void
    {
        $service = new TrendAnalyzer();

        $this->assertSame('improving', $service->trendDirection([
            ['overall_health_score' => 70],
            ['overall_health_score' => 76],
        ]));
    }

    public function test_it_marks_a_falling_score_as_worsening(): void
    {
        $service = new TrendAnalyzer();

        $this->assertSame('worsening', $service->trendDirection([
            ['overall_health_score' => 80],
            ['overall_health_score' => 72],
        ]));
    }

    public function test_it_sorts_history_by_created_at_before_calculating_trend(): void
    {
        $service = new TrendAnalyzer();

        $this->assertSame('improving', $service->trendDirection([
            ['created_at' => '2026-07-15 10:00:00', 'overall_health_score' => 70],
            ['created_at' => '2026-07-01 10:00:00', 'overall_health_score' => 60],
            ['created_at' => '2026-07-16 10:00:00', 'overall_health_score' => 77],
        ]));
    }
}
