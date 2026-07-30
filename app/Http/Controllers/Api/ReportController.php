<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ScanStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Scan;
use App\Services\Reports\PdfReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Report::query()
                ->where('user_id', $request->user()->id)
                ->with(['scan.repository'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, Scan $scan, PdfReportService $service): JsonResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);
        abort_unless($scan->status === ScanStatus::Completed, 409, 'Reports can only be generated for completed scans.');

        $path = $service->generate($scan);

        $report = Report::updateOrCreate(
            [
                'scan_id' => $scan->id,
                'user_id' => $request->user()->id,
            ],
            [
                'file_path' => $path,
                'generated_at' => now(),
            ]
        );

        return response()->json(
            $report->load(['scan.repository']),
            $report->wasRecentlyCreated ? 201 : 200
        );
    }

    public function show(Request $request, Scan $scan): JsonResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $report = $scan->reports()
            ->latest('generated_at')
            ->latest('id')
            ->first();

        abort_unless($report !== null, 404);

        return response()->json([
            'data' => $report->load(['scan.repository']),
        ]);
    }

    public function download(Request $request, Report $report)
    {
        abort_unless($report->user_id === $request->user()->id, 403);

        return Storage::disk('local')->download($report->file_path);
    }
}
