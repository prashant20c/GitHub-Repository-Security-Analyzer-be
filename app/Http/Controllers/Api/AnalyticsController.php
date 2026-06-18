<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticsController extends Controller
{
    public function showRepositoryAnalytics(Request $request, Repository $repository): JsonResponse
    {
        abort_unless($repository->user_id === $request->user()->id, 403);

        return response()->json($repository->scans()->with('analytics')->latest()->get());
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
