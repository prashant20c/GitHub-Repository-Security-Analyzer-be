<?php

declare(strict_types=1);

return [
    'scan_timeout_seconds' => (int) env('SCAN_TIMEOUT_SECONDS', 900),
    'max_repository_size_mb' => (int) env('MAX_REPOSITORY_SIZE_MB', 250),
    'github_scan_base_path' => env('GITHUB_SCAN_BASE_PATH', storage_path('app/scans')),
];
