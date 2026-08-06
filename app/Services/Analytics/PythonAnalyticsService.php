<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\RiskLevel;
use App\Services\Scoring\RiskScoringService;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

final class PythonAnalyticsService
{
    public function __construct(
        private readonly TrendAnalyzer $trendAnalyzer,
        private readonly RiskScoringService $riskScoringService,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array{trend_direction:string,series:array<int, array{label:string,value:int}>,moving_averages:array<string, array<int, float>>}
     */
    public function trendPayload(array $history): array
    {
        $fallback = $this->fallbackTrendPayload($history);
        $script = base_path('backend/python-analytics/analyze_trends.py');

        if (! File::exists($script)) {
            return $fallback;
        }

        $result = $this->runScript($script, ['history' => $history]);

        if (! is_array($result)) {
            return $fallback;
        }

        return [
            'trend_direction' => isset($result['trend_direction']) && is_string($result['trend_direction'])
                ? $result['trend_direction']
                : $fallback['trend_direction'],
            'series' => isset($result['series']) && is_array($result['series'])
                ? array_values(array_filter($result['series'], static fn (mixed $item): bool => is_array($item)))
                : $fallback['series'],
            'moving_averages' => isset($result['moving_averages']) && is_array($result['moving_averages'])
                ? $result['moving_averages']
                : $fallback['moving_averages'],
        ];
    }

    /**
     * @param array<string, int|float|string> $metrics
     */
    public function classifyRisk(array $metrics): RiskLevel
    {
        $fallback = $this->fallbackRiskLevel($metrics);
        $script = base_path('backend/python-analytics/classify_risk.py');

        if (! File::exists($script)) {
            return $fallback;
        }

        $result = $this->runScript($script, ['metrics' => $metrics]);

        if (! is_array($result)) {
            return $fallback;
        }

        $riskLevel = $result['risk_level'] ?? null;

        if (! is_string($riskLevel) || $riskLevel === '') {
            return $fallback;
        }

        return RiskLevel::tryFrom($riskLevel) ?? $fallback;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|null
     */
    private function runScript(string $script, array $input): ?array
    {
        $process = new Process([
            (string) config('security.python_binary', 'python3'),
            $script,
        ]);

        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->setTimeout((int) config('security.python_analytics_timeout_seconds', 60));
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        if ($output === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array{trend_direction:string,series:array<int, array{label:string,value:int}>,moving_averages:array<string, array<int, float>>}
     */
    private function fallbackTrendPayload(array $history): array
    {
        $normalizedHistory = array_map(static function (array $item): array {
            return [
                'created_at' => $item['created_at'] ?? null,
                'overall_health_score' => (int) ($item['overall_health_score'] ?? 0),
            ];
        }, $history);

        $series = [];
        foreach ($normalizedHistory as $item) {
            if (! is_string($item['created_at']) || $item['created_at'] === '') {
                continue;
            }

            $series[] = [
                'label' => substr($item['created_at'], 0, 10),
                'value' => (int) $item['overall_health_score'],
            ];
        }

        return [
            'trend_direction' => $this->trendAnalyzer->trendDirection($normalizedHistory),
            'series' => $series,
            'moving_averages' => [
                'overall_health_score' => [],
                'security_score' => [],
                'code_quality_score' => [],
                'dependency_score' => [],
                'secret_score' => [],
            ],
        ];
    }

    /**
     * @param array<string, int|float|string> $metrics
     */
    private function fallbackRiskLevel(array $metrics): RiskLevel
    {
        $findings = [];

        for ($i = 0; $i < (int) ($metrics['critical_count'] ?? 0); $i++) {
            $findings[] = ['tool' => 'semgrep', 'severity' => 'critical'];
        }
        for ($i = 0; $i < (int) ($metrics['high_count'] ?? 0); $i++) {
            $findings[] = ['tool' => 'semgrep', 'severity' => 'high'];
        }
        for ($i = 0; $i < (int) ($metrics['medium_count'] ?? 0); $i++) {
            $findings[] = ['tool' => 'semgrep', 'severity' => 'medium'];
        }
        for ($i = 0; $i < (int) ($metrics['low_count'] ?? 0); $i++) {
            $findings[] = ['tool' => 'semgrep', 'severity' => 'low'];
        }

        $calculated = $this->riskScoringService->score($findings);

        return $calculated['risk_level'];
    }
}
