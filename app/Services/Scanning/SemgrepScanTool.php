<?php

declare(strict_types=1);

namespace App\Services\Scanning;

final class SemgrepScanTool extends AbstractProcessScanTool
{
    public function name(): string
    {
        return 'semgrep';
    }

    protected function command(string $repositoryPath): array
    {
        return ['semgrep', 'scan', '--config', 'auto', '--json', $repositoryPath];
    }
}
