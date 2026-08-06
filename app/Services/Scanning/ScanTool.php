<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\ScannerResult;

interface ScanTool
{
    public function name(): string;

    public function scan(string $repositoryPath): ScannerResult;
}
