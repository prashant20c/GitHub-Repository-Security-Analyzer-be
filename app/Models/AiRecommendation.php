<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_id',
        'plain_english_summary',
        'business_impact',
        'technical_explanation',
        'recommended_fix',
        'secure_code_example',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }
}
