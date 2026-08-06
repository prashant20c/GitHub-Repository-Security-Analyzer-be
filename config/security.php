<?php

declare(strict_types=1);

return [
    'scan_timeout_seconds' => (int) env('SCAN_TIMEOUT_SECONDS', 900),
    'max_repository_size_mb' => (int) env('MAX_REPOSITORY_SIZE_MB', 250),
    'github_scan_base_path' => env('GITHUB_SCAN_BASE_PATH', storage_path('app/scans')),
    'python_binary' => env('PYTHON_BINARY', 'python3'),
    'python_analytics_timeout_seconds' => (int) env('PYTHON_ANALYTICS_TIMEOUT_SECONDS', 60),
    'openai_code_scan_enabled' => (bool) env('OPENAI_CODE_SCAN_ENABLED', true),
    'openai_code_scan_max_files' => (int) env('OPENAI_CODE_SCAN_MAX_FILES', 60),
    'openai_code_scan_max_file_bytes' => (int) env('OPENAI_CODE_SCAN_MAX_FILE_BYTES', 12000),
    'openai_code_scan_max_total_bytes' => (int) env('OPENAI_CODE_SCAN_MAX_TOTAL_BYTES', 120000),
    'openai_code_scan_timeout_seconds' => (int) env('OPENAI_CODE_SCAN_TIMEOUT_SECONDS', 90),
    'external_api_logging' => [
        'enabled' => (bool) env('EXTERNAL_API_LOGGING_ENABLED', true),
        'store_request_payloads' => (bool) env('EXTERNAL_API_LOG_REQUEST_PAYLOADS', false),
        'store_response_payloads' => (bool) env('EXTERNAL_API_LOG_RESPONSE_PAYLOADS', true),
        'max_payload_bytes' => (int) env('EXTERNAL_API_LOG_MAX_PAYLOAD_BYTES', 10000),
    ],
];
