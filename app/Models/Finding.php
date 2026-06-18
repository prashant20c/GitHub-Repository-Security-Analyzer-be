<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Severity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Finding extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'tool',
        'title',
        'description',
        'severity',
        'file_path',
        'line_number',
        'code_snippet',
        'owasp_category',
        'cwe_id',
        'risk_score',
    ];

    protected $casts = [
        'severity' => Severity::class,
        'line_number' => 'integer',
        'risk_score' => 'integer',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(AiRecommendation::class);
    }
}
