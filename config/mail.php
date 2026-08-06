<?php

declare(strict_types=1);

return [
    'default' => 'mailgun',
    'mailers' => [
        'mailgun' => [
            'transport' => 'mailgun',
            'timeout' => (int) env('MAILGUN_TIMEOUT_SECONDS', 20),
        ],
    ],
    'from' => [
        'address' => env('MAILGUN_FROM_EMAIL'),
        'name' => env('MAILGUN_FROM_NAME', env('APP_NAME', 'Security Analyzer')),
    ],
];
