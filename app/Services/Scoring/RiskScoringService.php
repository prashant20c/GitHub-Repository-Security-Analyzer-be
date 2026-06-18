<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Enums\RiskLevel;
use App\Enums\Severity;

final class RiskScoringService
{
    /**
     * @param array<array-key, mixed> $findings
     */
    public function score(array $findings): array
    {
        $critical = 0;
        $high = 0;
        $medium = 0;
        $low = 0;
        $secrets = 0;
        $dependencyRisk = 0;

        foreach ($findings as $finding) {
            $severity = $finding['severity'] instanceof Severity ? $finding['severity'] : Severity::tryFrom((string) $finding['severity']) ?? Severity::Medium;

            match ($severity) {
                Severity::Critical => $critical++,
                Severity::High => $high++,
                Severity::Medium => $medium++,
                Severity::Low, Severity::Info => $low++,
            };

            if (($finding['tool'] ?? '') === 'gitleaks') {
                $secrets++;
            }

            if (in_array($finding['tool'] ?? '', ['composer-audit', 'npm-audit'], true)) {
                $dependencyRisk++;
            }
        }

        $security = max(0, 100 - ($critical * 20) - ($high * 10) - ($medium * 5) - ($low * 2));
        $secretScore = max(0, 100 - ($secrets * 15));
        $dependencyScore = max(0, 100 - ($dependencyRisk * 10));
        $codeQualityScore = max(0, 100 - min(30, count($findings) > 25 ? 10 : 0));
        $overall = (int) round(($security * 0.40) + ($codeQualityScore * 0.25) + ($dependencyScore * 0.25) + ($secretScore * 0.10));

        return [
            'security_score' => $security,
            'code_quality_score' => $codeQualityScore,
            'dependency_score' => $dependencyScore,
            'secret_score' => $secretScore,
            'overall_health_score' => $overall,
            'critical_count' => $critical,
            'high_count' => $high,
            'medium_count' => $medium,
            'low_count' => $low,
            'secret_leak_count' => $secrets,
            'dependency_risk_count' => $dependencyRisk,
            'risk_level' => $this->classifyRisk($overall),
        ];
    }

    private function classifyRisk(int $overallHealthScore): RiskLevel
    {
        return match (true) {
            $overallHealthScore >= 80 => RiskLevel::Low,
            $overallHealthScore >= 60 => RiskLevel::Medium,
            $overallHealthScore >= 40 => RiskLevel::High,
            default => RiskLevel::Critical,
        };
    }
}
