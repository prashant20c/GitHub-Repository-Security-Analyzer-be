<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ScanStatus;
use App\Models\Repository;
use App\Models\Scan;
use App\Services\Analysis\CodeQualityAnalyzer;
use App\Services\Analytics\TrendAnalyzer;
use App\Services\Ai\RecommendationService;
use App\Services\Repository\RepositoryCloner;
use App\Services\Scanning\ComposerAuditScanTool;
use App\Services\Scanning\GitleaksScanTool;
use App\Services\Scanning\NpmAuditScanTool;
use App\Services\Scanning\ScanResultNormalizer;
use App\Services\Scanning\SemgrepScanTool;
use App\Services\Scoring\RiskScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class RunRepositoryScanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public function __construct(public readonly int $scanId)
    {
    }

    public function handle(
        RepositoryCloner $cloner,
        SemgrepScanTool $semgrep,
        GitleaksScanTool $gitleaks,
        ComposerAuditScanTool $composerAudit,
        NpmAuditScanTool $npmAudit,
        CodeQualityAnalyzer $codeQualityAnalyzer,
        ScanResultNormalizer $normalizer,
        RiskScoringService $scoring,
        RecommendationService $recommendationService,
        TrendAnalyzer $trendAnalyzer
    ): void {
        $scan = Scan::with('repository')->findOrFail($this->scanId);
        $repository = $scan->repository;

        $clonePath = storage_path("app/scans/{$scan->id}/repo");
        $scan->update(['status' => ScanStatus::Running, 'started_at' => now()]);

        try {
            $cloner->cloneRepository($repository->url, $clonePath);
            $commitHash = $cloner->commitHash($clonePath);

            $processResults = [
                $semgrep->scan($clonePath),
                $gitleaks->scan($clonePath),
                $composerAudit->scan($clonePath),
                $npmAudit->scan($clonePath),
            ];

            $findings = [];
            foreach ($processResults as $result) {
                $findings = array_merge($findings, $normalizer->normalize($result));
            }

            $scores = $scoring->score(array_map(static fn ($finding): array => [
                'tool' => $finding->tool,
                'severity' => $finding->severity,
            ], $findings));
            $scores['code_quality_score'] = $codeQualityAnalyzer->score($clonePath);
            $scores['overall_health_score'] = (int) round(
                ($scores['security_score'] * 0.40)
                + ($scores['code_quality_score'] * 0.25)
                + ($scores['dependency_score'] * 0.25)
                + ($scores['secret_score'] * 0.10)
            );

            DB::transaction(function () use ($scan, $findings, $scores, $recommendationService, $trendAnalyzer): void {
                $scan->findings()->delete();

                foreach ($findings as $finding) {
                    $model = $scan->findings()->create([
                        'tool' => $finding->tool,
                        'title' => $finding->title,
                        'description' => $finding->description,
                        'severity' => $finding->severity,
                        'file_path' => $finding->filePath,
                        'line_number' => $finding->lineNumber,
                        'code_snippet' => $finding->codeSnippet,
                        'owasp_category' => $finding->owaspCategory,
                        'cwe_id' => $finding->cweId,
                        'risk_score' => $finding->riskScore,
                    ]);

                    $recommendationService->generate($model);
                }

                $scan->update(array_merge($scores, [
                    'total_findings' => count($findings),
                    'commit_hash' => $commitHash,
                    'completed_at' => now(),
                    'status' => ScanStatus::Completed,
                ]));

                $previousScan = $scan->repository->scans()
                    ->where('id', '<>', $scan->id)
                    ->orderByDesc('created_at')
                    ->first();

                $scan->analytics()->updateOrCreate(
                    ['scan_id' => $scan->id],
                    [
                        'repository_id' => $scan->repository_id,
                        'scan_number' => $scan->repository->scans()->count(),
                        'security_score' => $scores['security_score'],
                        'code_quality_score' => $scores['code_quality_score'],
                        'dependency_score' => $scores['dependency_score'],
                        'secret_score' => $scores['secret_score'],
                        'overall_health_score' => $scores['overall_health_score'],
                        'total_vulnerabilities' => count($findings),
                        'secret_leak_count' => $scores['secret_leak_count'],
                        'dependency_risk_count' => $scores['dependency_risk_count'],
                        'risk_level' => $scores['risk_level']->value,
                        'trend_direction' => $trendAnalyzer->trendDirection(array_values(array_filter([
                            $previousScan ? ['overall_health_score' => (int) $previousScan->overall_health_score] : null,
                            ['overall_health_score' => (int) $scores['overall_health_score']],
                        ]))),
                    ]
                );
            });
        } catch (\Throwable $throwable) {
            $scan->update([
                'status' => ScanStatus::Failed,
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ]);

            throw $throwable;
        } finally {
            File::deleteDirectory(dirname($clonePath));
        }
    }
}
