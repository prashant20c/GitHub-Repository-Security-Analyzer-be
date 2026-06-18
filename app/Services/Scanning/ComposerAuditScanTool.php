<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\ScannerResult;
use Illuminate\Support\Facades\File;

final class ComposerAuditScanTool extends AbstractProcessScanTool
{
    public function name(): string
    {
        return 'composer-audit';
    }

    public function scan(string $repositoryPath): ScannerResult
    {
        if (! File::exists($repositoryPath . '/composer.lock')) {
            return new ScannerResult(tool: $this->name(), success: true);
        }

        return parent::scan($repositoryPath);
    }

    protected function command(string $repositoryPath): array
    {
        return ['composer', 'audit', '--format=json', '--working-dir=' . $repositoryPath];
    }
}
