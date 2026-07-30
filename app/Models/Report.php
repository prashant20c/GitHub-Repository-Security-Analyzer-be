<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Report extends Model
{
    use HasFactory;

    protected $appends = [
        'download_url',
    ];

    protected $fillable = [
        'scan_id',
        'user_id',
        'file_path',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return sprintf('/api/reports/%d/download', $this->id);
    }
}
