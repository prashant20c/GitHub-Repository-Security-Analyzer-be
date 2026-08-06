<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ScanStatus;
use App\Http\Controllers\Controller;
use App\Jobs\RunRepositoryScanJob;
use App\Models\Repository;
use App\Models\Scan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScanController extends Controller
{
    public function store(Request $request, Repository $repository): JsonResponse
    {
        abort_unless($repository->user_id === $request->user()->id, 403);

        $activeScan = $repository->scans()
            ->whereIn('status', [ScanStatus::Pending, ScanStatus::Running])
            ->latest()
            ->first();

        if ($activeScan) {
            return response()->json([
                'message' => 'A scan is already running for this repository.',
                'scan' => $activeScan,
            ], 409);
        }

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $request->user()->id,
            'status' => ScanStatus::Pending,
        ]);

        RunRepositoryScanJob::dispatch($scan->id);

        return response()->json($scan, 201);
    }

    public function indexByRepository(Request $request, Repository $repository): JsonResponse
    {
        abort_unless($repository->user_id === $request->user()->id, 403);

        return response()->json($repository->scans()->with('reports')->latest()->get());
    }

    public function show(Request $request, Scan $scan): JsonResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        return response()->json($scan->load(['findings.recommendation', 'repository', 'analytics', 'reports']));
    }

    public function findings(Request $request, Scan $scan): JsonResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        return response()->json($scan->findings()->with('recommendation')->get());
    }
}
