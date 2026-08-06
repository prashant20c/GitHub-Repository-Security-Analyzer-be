<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\ScannerResult;
use Illuminate\Support\Facades\File;

final class NpmAuditScanTool extends AbstractProcessScanTool
{
    public function name(): string
    {
        return 'npm-audit';
    }

    public function scan(string $repositoryPath): ScannerResult
    {
        if (! File::exists($repositoryPath . '/package-lock.json')) {
            return new ScannerResult(tool: $this->name(), success: true);
        }

        return parent::scan($repositoryPath);
    }

    protected function command(string $repositoryPath): array
    {
        return ['npm', 'audit', '--json', '--prefix', $repositoryPath];
    }
}
