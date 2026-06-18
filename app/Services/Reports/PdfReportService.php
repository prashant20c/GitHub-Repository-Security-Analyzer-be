<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Scan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class PdfReportService
{
    public function generate(Scan $scan): string
    {
        $pdf = Pdf::loadView('reports.scan', [
            'scan' => $scan->load(['findings.recommendation', 'repository']),
        ]);

        $path = sprintf('reports/scan-%d.pdf', $scan->id);
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
