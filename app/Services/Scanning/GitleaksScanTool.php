<?php

declare(strict_types=1);

namespace App\Services\Scanning;

final class GitleaksScanTool extends AbstractProcessScanTool
{
    public function name(): string
    {
        return 'gitleaks';
    }

    protected function command(string $repositoryPath): array
    {
        return ['gitleaks', 'detect', '--source', $repositoryPath, '--report-format', 'json'];
    }
}
