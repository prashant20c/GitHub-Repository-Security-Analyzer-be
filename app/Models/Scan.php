<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Scan extends Model
{
    use HasFactory;

    protected $appends = [
        'has_report',
        'report_id',
        'report_generated_at',
        'report_download_url',
    ];

    protected $fillable = [
        'repository_id',
        'user_id',
        'status',
        'commit_hash',
        'security_score',
        'code_quality_score',
        'dependency_score',
        'secret_score',
        'overall_health_score',
        'total_findings',
        'critical_count',
        'high_count',
        'medium_count',
        'low_count',
        'secret_leak_count',
        'dependency_risk_count',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'status' => ScanStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(ScanAnalytics::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class)->orderByDesc('generated_at')->orderByDesc('id');
    }

    public function getHasReportAttribute(): bool
    {
        return $this->reportModel() !== null;
    }

    public function getReportIdAttribute(): ?int
    {
        return $this->reportModel()?->id;
    }

    public function getReportGeneratedAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->reportModel()?->generated_at;
    }

    public function getReportDownloadUrlAttribute(): ?string
    {
        $report = $this->reportModel();

        return $report ? sprintf('/api/reports/%d/download', $report->id) : null;
    }

    private function reportModel(): ?Report
    {
        if ($this->relationLoaded('reports')) {
            return $this->getRelation('reports')->first();
        }

        return $this->reports()->first();
    }
}
