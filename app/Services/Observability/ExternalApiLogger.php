<?php

declare(strict_types=1);

namespace App\Services\Observability;

use App\Models\ExternalApiLog;
use Throwable;

final class ExternalApiLogger
{
    public function record(
        string $provider,
        string $operation,
        string $endpoint,
        string $status,
        ?int $httpStatus,
        int $durationMs,
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?string $errorMessage = null,
    ): void {
        if (! config('security.external_api_logging.enabled', true)) {
            return;
        }

        try {
            ExternalApiLog::create([
                'provider' => $provider,
                'operation' => $operation,
                'endpoint' => $endpoint,
                'status' => $status,
                'http_status' => $httpStatus,
                'duration_ms' => $durationMs,
                'request_payload' => config('security.external_api_logging.store_request_payloads', false)
                    ? $this->serialize($requestPayload)
                    : null,
                'response_payload' => config('security.external_api_logging.store_response_payloads', true)
                    ? $this->serialize($responsePayload)
                    : null,
                'error_message' => $this->truncate($errorMessage),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // API logging must never make an authentication or scan request fail.
        }
    }

    private function serialize(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $serialized = is_string($payload)
            ? $payload
            : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->truncate(is_string($serialized) ? $serialized : null);
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $maxBytes = (int) config('security.external_api_logging.max_payload_bytes', 10000);

        return strlen($value) > $maxBytes
            ? substr($value, 0, $maxBytes) . '\n[truncated]'
            : $value;
    }
}
