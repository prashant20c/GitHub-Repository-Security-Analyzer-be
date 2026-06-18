<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

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
            Report::query()->where('user_id', $request->user()->id)->latest()->get()
        );
    }

    public function store(Request $request, Scan $scan, PdfReportService $service): JsonResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $path = $service->generate($scan);

        $report = Report::create([
            'scan_id' => $scan->id,
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'generated_at' => now(),
        ]);

        return response()->json($report, 201);
    }

    public function download(Request $request, Report $report)
    {
        abort_unless($report->user_id === $request->user()->id, 403);

        return Storage::disk('local')->download($report->file_path);
    }
}
