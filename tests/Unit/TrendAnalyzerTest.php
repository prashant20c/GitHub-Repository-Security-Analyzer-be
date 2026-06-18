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
}
