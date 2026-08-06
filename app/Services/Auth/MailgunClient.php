<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Observability\ExternalApiLogger;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MailgunClient
{
    public function __construct(private readonly ExternalApiLogger $apiLogger)
    {
    }

    /**
     * @param array<int, array{email:string,name?:string}> $recipients
     */
    public function send(
        array $recipients,
        string $subject,
        string $textPart,
        string $htmlPart,
        ?string $template = null,
        array $variables = [],
    ): void {
        $key = (string) config('services.mailgun.key');
        $domain = (string) config('services.mailgun.domain');
        $fromEmail = (string) config('services.mailgun.from_email');
        $fromName = (string) config('services.mailgun.from_name');

        if ($key === '' || $domain === '' || $fromEmail === '') {
            throw new RuntimeException('Mailgun email configuration is incomplete.');
        }

        $endpoint = rtrim((string) config('services.mailgun.base_url'), '/') . "/v3/{$domain}/messages";
        $from = $fromName !== '' ? "{$fromName} <{$fromEmail}>" : $fromEmail;
        $multipart = [
            ['name' => 'from', 'contents' => $from],
            ['name' => 'subject', 'contents' => $subject],
            ['name' => 'text', 'contents' => $textPart],
        ];

        if ($template !== null && $template !== '') {
            $multipart[] = ['name' => 'template', 'contents' => $template];
            $multipart[] = ['name' => 't:variables', 'contents' => json_encode($variables, JSON_THROW_ON_ERROR)];
        } else {
            $multipart[] = ['name' => 'html', 'contents' => $htmlPart];
        }

        foreach ($recipients as $recipient) {
            $to = ! empty($recipient['name'])
                ? "{$recipient['name']} <{$recipient['email']}>"
                : $recipient['email'];
            $multipart[] = ['name' => 'to', 'contents' => $to];
        }

        $startedAt = hrtime(true);

        try {
            $response = Http::withBasicAuth('api', $key)
                ->acceptJson()
                ->asMultipart()
                ->timeout((int) config('mail.mailers.mailgun.timeout', 20))
                ->post($endpoint, $multipart);

            $successful = $response->successful();
            $this->apiLogger->record(
                provider: 'mailgun',
                operation: 'send_email',
                endpoint: $endpoint,
                status: $successful ? 'success' : 'failure',
                httpStatus: $response->status(),
                durationMs: $this->durationMs($startedAt),
                requestPayload: $this->auditPayload($from, $subject, $textPart, $htmlPart, $recipients),
                responsePayload: $response->json() ?: $response->body(),
                errorMessage: $successful ? null : 'Mailgun rejected the email request.',
            );
        } catch (\Throwable $exception) {
            $this->apiLogger->record(
                provider: 'mailgun',
                operation: 'send_email',
                endpoint: $endpoint,
                status: 'error',
                httpStatus: null,
                durationMs: $this->durationMs($startedAt),
                requestPayload: $this->auditPayload($from, $subject, $textPart, $htmlPart, $recipients),
                errorMessage: $exception->getMessage(),
            );

            throw $exception;
        }

        if (! $response->successful()) {
            throw new RuntimeException('Mailgun rejected the email request (HTTP ' . $response->status() . ').');
        }
    }

    /**
     * Keep the raw multipart request out of the audit log while preserving useful diagnostics.
     *
     * @param array<int, array{email:string,name?:string}> $recipients
     * @return array<string, mixed>
     */
    private function auditPayload(string $from, string $subject, string $text, string $html, array $recipients): array
    {
        return [
            'from' => $from,
            'to' => array_map(static fn (array $recipient): string => $recipient['email'], $recipients),
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ];
    }

    private function durationMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
