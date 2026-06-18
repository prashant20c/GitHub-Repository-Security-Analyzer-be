<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ScanAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'scan_id',
        'scan_number',
        'security_score',
        'code_quality_score',
        'dependency_score',
        'secret_score',
        'overall_health_score',
        'total_vulnerabilities',
        'secret_leak_count',
        'dependency_risk_count',
        'risk_level',
        'trend_direction',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
