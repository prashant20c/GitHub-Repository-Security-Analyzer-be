<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use App\Services\Analytics\PythonAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticsController extends Controller
{
    public function showRepositoryAnalytics(
        Request $request,
        Repository $repository,
        PythonAnalyticsService $pythonAnalytics
    ): JsonResponse
    {
        abort_unless($repository->user_id === $request->user()->id, 403);

        $history = $repository->scans()
            ->with(['analytics', 'reports'])
            ->orderBy('created_at')
            ->get();

        $trend = $pythonAnalytics->trendPayload(
            $history->map(static fn ($scan): array => [
                'created_at' => (string) $scan->created_at,
                'security_score' => (int) $scan->security_score,
                'code_quality_score' => (int) $scan->code_quality_score,
                'dependency_score' => (int) $scan->dependency_score,
                'secret_score' => (int) $scan->secret_score,
                'overall_health_score' => (int) $scan->overall_health_score,
            ])->all()
        );

        return response()->json([
            'history' => $history,
            'trend' => $trend,
        ]);
    }

    public function securityTrend(Request $request, Repository $repository): JsonResponse
    {
        return $this->trendResponse($request, $repository, 'security_score');
    }

    public function secretTrend(Request $request, Repository $repository): JsonResponse
    {
        return $this->trendResponse($request, $repository, 'secret_score');
    }

    public function dependencyTrend(Request $request, Repository $repository): JsonResponse
    {
        return $this->trendResponse($request, $repository, 'dependency_score');
    }

    public function qualityTrend(Request $request, Repository $repository): JsonResponse
    {
        return $this->trendResponse($request, $repository, 'code_quality_score');
    }

    private function trendResponse(Request $request, Repository $repository, string $field): JsonResponse
    {
        abort_unless($repository->user_id === $request->user()->id, 403);

        return response()->json(
            $repository->scans()->select(['id', 'created_at', $field])->orderBy('created_at')->get()
        );
    }
}
