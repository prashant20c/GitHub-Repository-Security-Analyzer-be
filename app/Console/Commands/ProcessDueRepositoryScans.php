<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ScanStatus;
use App\Jobs\RunRepositoryScanJob;
use App\Models\Repository;
use App\Models\Scan;
use Illuminate\Console\Command;

final class ProcessDueRepositoryScans extends Command
{
    protected $signature = 'scans:process-due';
    protected $description = 'Create and queue scans for repositories whose schedule is due.';

    public function handle(): int
    {
        $repositories = Repository::query()
            ->where('is_scheduled', true)
            ->whereNotNull('next_scan_at')
            ->where('next_scan_at', '<=', now())
            ->get();

        foreach ($repositories as $repository) {
            $scan = Scan::create([
                'repository_id' => $repository->id,
                'user_id' => $repository->user_id,
                'status' => ScanStatus::Pending,
            ]);

            RunRepositoryScanJob::dispatch($scan->id);

            $repository->forceFill([
                'last_scan_at' => now(),
                'next_scan_at' => match ((string) $repository->scan_frequency->value) {
                    'daily' => now()->addDay(),
                    'weekly' => now()->addWeek(),
                    'monthly' => now()->addMonth(),
                    default => null,
                },
            ])->save();
        }

        return self::SUCCESS;
    }
}
