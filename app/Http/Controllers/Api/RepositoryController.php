<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ScanFrequency;
use App\Http\Controllers\Controller;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RepositoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Repository::query()->where('user_id', $request->user()->id)->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'regex:#^https://github\.com/#'],
            'scan_frequency' => ['required', 'in:manual,daily,weekly,monthly'],
        ]);

        $parts = (new \App\Services\Repository\RepositoryUrlParser())->parse($validated['url']);

        $repository = Repository::create([
            'user_id' => $request->user()->id,
            'name' => $parts['name'],
            'owner' => $parts['owner'],
            'url' => $validated['url'],
            'default_branch' => $parts['default_branch'],
            'scan_frequency' => ScanFrequency::from($validated['scan_frequency']),
            'is_scheduled' => $validated['scan_frequency'] !== 'manual',
            'next_scan_at' => $this->nextScanAtForFrequency($validated['scan_frequency']),
        ]);

        return response()->json($repository, 201);
    }

    public function show(Request $request, Repository $repository): JsonResponse
    {
        $this->authorizeOwnership($request, $repository);

        return response()->json($repository->load(['scans.reports']));
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        $this->authorizeOwnership($request, $repository);

        $validated = $request->validate([
            'scan_frequency' => ['sometimes', 'in:manual,daily,weekly,monthly'],
        ]);

        if (isset($validated['scan_frequency'])) {
            $repository->scan_frequency = ScanFrequency::from($validated['scan_frequency']);
            $repository->is_scheduled = $validated['scan_frequency'] !== 'manual';
            $repository->next_scan_at = $this->nextScanAtForFrequency($validated['scan_frequency']);
        }

        $repository->save();

        return response()->json($repository);
    }

    public function destroy(Request $request, Repository $repository)
    {
        $this->authorizeOwnership($request, $repository);
        $repository->delete();

        return response()->noContent();
    }

    public function updateSchedule(Request $request, Repository $repository): JsonResponse
    {
        return $this->update($request, $repository);
    }

    private function authorizeOwnership(Request $request, Repository $repository): void
    {
        abort_unless($repository->user_id === $request->user()->id, 403);
    }

    private function nextScanAtForFrequency(string $frequency): ?\Illuminate\Support\Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => null,
        };
    }
}
