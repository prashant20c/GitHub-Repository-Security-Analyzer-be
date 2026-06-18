<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Severity;
use App\Services\Scoring\RiskScoringService;
use PHPUnit\Framework\TestCase;

final class RiskScoringServiceTest extends TestCase
{
    public function test_it_scores_findings_by_severity_and_tool(): void
    {
        $service = new RiskScoringService();

        $result = $service->score([
            ['tool' => 'semgrep', 'severity' => Severity::Critical],
            ['tool' => 'gitleaks', 'severity' => Severity::High],
            ['tool' => 'composer-audit', 'severity' => Severity::Medium],
            ['tool' => 'npm-audit', 'severity' => Severity::Low],
        ]);

        $this->assertSame(80, $result['security_score']);
        $this->assertSame(85, $result['secret_score']);
        $this->assertSame(80, $result['dependency_score']);
        $this->assertSame(86, $result['overall_health_score']);
        $this->assertSame('Low Risk', $result['risk_level']->value);
    }
}
