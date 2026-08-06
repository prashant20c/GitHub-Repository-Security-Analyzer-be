<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Severity;

final readonly class NormalizedFinding
{
    public function __construct(
        public string $tool,
        public string $title,
        public string $description,
        public Severity $severity,
        public ?string $filePath,
        public ?int $lineNumber,
        public ?string $codeSnippet,
        public ?string $owaspCategory,
        public ?string $cweId,
        public int $riskScore,
    ) {
    }
}
