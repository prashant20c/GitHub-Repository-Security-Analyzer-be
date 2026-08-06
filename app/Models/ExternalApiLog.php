<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ExternalApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider',
        'operation',
        'endpoint',
        'status',
        'http_status',
        'duration_ms',
        'request_payload',
        'response_payload',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}
