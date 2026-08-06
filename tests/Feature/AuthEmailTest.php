<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AuthEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_mailgun_verification_email(): void
    {
        config()->set('services.mailgun.key', 'test-key');
        config()->set('services.mailgun.domain', 'mg.example.com');
        config()->set('services.mailgun.from_email', 'verified@example.com');
        Http::fake(['https://api.mailgun.net/*' => Http::response(['id' => '<message-id>'], 200)]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('user.email', 'new@example.com');
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'email_verified_at' => null,
        ]);
        $this->assertTrue(Hash::check('password123', User::where('email', 'new@example.com')->value('password')));
        $this->assertNotSame('password123', User::where('email', 'new@example.com')->value('password'));
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/v3/mg.example.com/messages'));
        $this->assertDatabaseHas('external_api_logs', [
            'provider' => 'mailgun',
            'operation' => 'send_email',
            'status' => 'success',
            'http_status' => 200,
        ]);
    }

    public function test_a_signed_verification_url_marks_the_user_verified(): void
    {
        $user = User::create([
            'name' => 'Verify User',
            'email' => 'verify@example.com',
            'password' => bcrypt('password123'),
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
            false,
        );

        $this->getJson($url)->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_an_invalid_verification_link_redirects_to_the_resend_screen(): void
    {
        $response = $this->get('/api/email/verify/999/' . str_repeat('a', 40));

        $response->assertRedirect(config('services.frontend_url') . '/verify-email?error=expired');
    }
}
