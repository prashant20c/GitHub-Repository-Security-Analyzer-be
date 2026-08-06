<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\URL;

final class AuthEmailService
{
    public function __construct(private readonly MailgunClient $mailgun)
    {
    }

    public function sendVerification(User $user): void
    {
        // Sign the path/query rather than the request host. This keeps links valid
        // when the API is behind Docker, a reverse proxy, or a different public port.
        $path = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
            false,
        );
        $url = rtrim((string) config('services.backend_url'), '/') . $path;

        $name = e($user->name);
        $link = e($url);

        $this->mailgun->send(
            [['email' => $user->email, 'name' => $user->name]],
            'Verify your Security Analyzer email',
            "Hello {$user->name},\n\nVerify your email address: {$url}\n\nThis link expires in 60 minutes.",
            "<p>Hello {$name},</p><p>Verify your email address to activate your account:</p><p><a href=\"{$link}\">Verify email address</a></p><p>This link expires in 60 minutes.</p>",
            (string) config('services.mailgun.verification_template'),
            ['verification_url' => $url],
        );
    }

    public function sendPasswordReset(User $user, string $token): void
    {
        $frontendUrl = rtrim((string) config('services.frontend_url'), '/');
        $url = $frontendUrl . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($user->email);
        $name = e($user->name);
        $link = e($url);

        $this->mailgun->send(
            [['email' => $user->email, 'name' => $user->name]],
            'Reset your Security Analyzer password',
            "Hello {$user->name},\n\nReset your password: {$url}\n\nThis link expires in 60 minutes. If you did not request this, you can ignore this email.",
            "<p>Hello {$name},</p><p>Reset your Security Analyzer password:</p><p><a href=\"{$link}\">Reset password</a></p><p>This link expires in 60 minutes. If you did not request this, you can ignore this email.</p>",
        );
    }
}
