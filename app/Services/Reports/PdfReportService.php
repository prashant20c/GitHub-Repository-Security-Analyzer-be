<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Scan;
use App\Services\Ai\OpenAiClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class PdfReportService
{
    public function __construct(private readonly OpenAiClient $client)
    {
    }

    public function generate(Scan $scan): string
    {
        $aiSummary = $this->client->summarizeScan($scan);

        $pdf = Pdf::loadView('reports.scan', [
            'scan' => $scan->load(['findings.recommendation', 'repository']),
            'aiSummary' => $aiSummary,
        ]);

        $path = sprintf('reports/scan-%d.pdf', $scan->id);
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
