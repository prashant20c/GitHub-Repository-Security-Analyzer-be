<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\ScannerResult;
use Symfony\Component\Process\Process;

abstract class AbstractProcessScanTool implements ScanTool
{
    abstract protected function command(string $repositoryPath): array;

    public function scan(string $repositoryPath): ScannerResult
    {
        $process = new Process($this->command($repositoryPath));
        $process->setTimeout((int) config('security.scan_timeout_seconds', 900));
        $process->run();

        return new ScannerResult(
            tool: $this->name(),
            success: $process->isSuccessful(),
            rawOutput: $process->getOutput(),
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
        );
    }
}
