<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScanFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'owner',
        'url',
        'default_branch',
        'scan_frequency',
        'is_scheduled',
        'next_scan_at',
        'last_scan_at',
    ];

    protected $casts = [
        'is_scheduled' => 'boolean',
        'next_scan_at' => 'datetime',
        'last_scan_at' => 'datetime',
        'scan_frequency' => ScanFrequency::class,
    ];

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }
}
