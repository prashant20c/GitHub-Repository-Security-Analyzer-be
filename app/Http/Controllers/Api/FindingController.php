<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Models\Finding;
use App\Services\Ai\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FindingController extends Controller
{
    public function show(Request $request, Finding $finding): JsonResponse
    {
        abort_unless($finding->scan->user_id === $request->user()->id, 403);

        return response()->json($finding->load(['recommendation', 'scan.repository']));
    }

    public function recommendation(Request $request, Finding $finding): JsonResponse
    {
        abort_unless($finding->scan->user_id === $request->user()->id, 403);

        return response()->json($finding->recommendation);
    }

    public function generateRecommendation(Request $request, Finding $finding, RecommendationService $service): JsonResponse
    {
        abort_unless($finding->scan->user_id === $request->user()->id, 403);

        $recommendation = $service->generate($finding);

        return response()->json($recommendation);
    }
}
