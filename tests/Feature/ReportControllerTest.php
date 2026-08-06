<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ScanFrequency;
use App\Enums\ScanStatus;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_an_existing_report_for_the_same_scan(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => 'security-analyzer',
            'owner' => 'openai',
            'url' => 'https://github.com/openai/security-analyzer',
            'default_branch' => 'main',
            'scan_frequency' => ScanFrequency::Manual,
            'is_scheduled' => false,
        ]);

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $user->id,
            'status' => ScanStatus::Completed,
            'commit_hash' => 'abc123',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);
        Storage::fake('local');

        $firstResponse = $this->postJson("/api/scans/{$scan->id}/report");
        $firstResponse->assertCreated();

        $secondResponse = $this->postJson("/api/scans/{$scan->id}/report");
        $secondResponse->assertOk();

        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseHas('reports', [
            'scan_id' => $scan->id,
            'user_id' => $user->id,
            'file_path' => 'reports/scan-' . $scan->id . '.pdf',
        ]);
        Storage::disk('local')->assertExists('reports/scan-' . $scan->id . '.pdf');
    }

    public function test_it_returns_the_latest_report_for_a_scan(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => 'security-analyzer',
            'owner' => 'openai',
            'url' => 'https://github.com/openai/security-analyzer',
            'default_branch' => 'main',
            'scan_frequency' => ScanFrequency::Manual,
            'is_scheduled' => false,
        ]);

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $user->id,
            'status' => ScanStatus::Completed,
            'commit_hash' => 'abc123',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);
        Storage::fake('local');

        $createResponse = $this->postJson("/api/scans/{$scan->id}/report");
        $createResponse->assertCreated();
        $reportId = $createResponse->json('id');

        $response = $this->getJson("/api/scans/{$scan->id}/report");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'scan_id' => $scan->id,
                'user_id' => $user->id,
                'file_path' => 'reports/scan-' . $scan->id . '.pdf',
                'download_url' => '/api/reports/' . $reportId . '/download',
            ],
        ]);
    }

    public function test_it_exposes_report_state_on_the_scan_payload(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => 'security-analyzer',
            'owner' => 'openai',
            'url' => 'https://github.com/openai/security-analyzer',
            'default_branch' => 'main',
            'scan_frequency' => ScanFrequency::Manual,
            'is_scheduled' => false,
        ]);

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $user->id,
            'status' => ScanStatus::Completed,
            'commit_hash' => 'abc123',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);
        Storage::fake('local');

        $createResponse = $this->postJson("/api/scans/{$scan->id}/report");
        $createResponse->assertCreated();
        $reportId = $createResponse->json('id');

        $response = $this->getJson("/api/scans/{$scan->id}");

        $response->assertOk();
        $response->assertJson([
            'has_report' => true,
            'report_id' => $reportId,
            'report_download_url' => '/api/reports/' . $reportId . '/download',
        ]);
        $response->assertJsonStructure([
            'report_generated_at',
        ]);
    }

    public function test_it_includes_download_urls_in_the_report_archive(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => 'security-analyzer',
            'owner' => 'openai',
            'url' => 'https://github.com/openai/security-analyzer',
            'default_branch' => 'main',
            'scan_frequency' => ScanFrequency::Manual,
            'is_scheduled' => false,
        ]);

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $user->id,
            'status' => ScanStatus::Completed,
            'commit_hash' => 'abc123',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);
        Storage::fake('local');

        $createResponse = $this->postJson("/api/scans/{$scan->id}/report");
        $createResponse->assertCreated();
        $reportId = $createResponse->json('id');

        $response = $this->getJson('/api/reports');

        $response->assertOk();
        $response->assertJsonPath('0.download_url', '/api/reports/' . $reportId . '/download');
    }

    public function test_it_returns_a_404_when_a_scan_has_no_report(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => 'security-analyzer',
            'owner' => 'openai',
            'url' => 'https://github.com/openai/security-analyzer',
            'default_branch' => 'main',
            'scan_frequency' => ScanFrequency::Manual,
            'is_scheduled' => false,
        ]);

        $scan = Scan::create([
            'repository_id' => $repository->id,
            'user_id' => $user->id,
            'status' => ScanStatus::Completed,
            'commit_hash' => 'abc123',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/scans/{$scan->id}/report")->assertNotFound();
    }
}
