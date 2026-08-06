<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ScannerResult
{
    /**
     * @param array<NormalizedFinding> $findings
     */
    public function __construct(
        public string $tool,
        public bool $success,
        public array $findings = [],
        public ?string $rawOutput = null,
        public ?string $errorMessage = null,
    ) {
    }
}
