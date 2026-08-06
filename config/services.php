<?php

declare(strict_types=1);

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5174'),
    'backend_url' => env('BACKEND_URL', env('APP_URL', 'http://localhost:8080')),
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
    'mailgun' => [
        'key' => env('MAILGUN_API_KEY'),
        'domain' => env('MAILGUN_DOMAIN'),
        'from_email' => env('MAILGUN_FROM_EMAIL'),
        'from_name' => env('MAILGUN_FROM_NAME', env('APP_NAME', 'Security Analyzer')),
        'base_url' => env('MAILGUN_BASE_URL', 'https://api.mailgun.net'),
        'verification_template' => env('MAILGUN_VERIFICATION_TEMPLATE', 'verify-email'),
    ],
];
