<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\NormalizedFinding;
use App\Data\ScannerResult;
use App\Enums\Severity;

final class ScanResultNormalizer
{
    /**
     * @return array<NormalizedFinding>
     */
    public function normalize(ScannerResult $result): array
    {
        if (! $result->rawOutput) {
            return $result->findings;
        }

        $decoded = json_decode($result->rawOutput, true);

        if (! is_array($decoded)) {
            return $result->findings;
        }

        if ($result->tool === 'semgrep' && isset($decoded['results']) && is_array($decoded['results'])) {
            return array_map(function (array $item): NormalizedFinding {
                return new NormalizedFinding(
                    tool: 'semgrep',
                    title: (string) ($item['check_id'] ?? $item['extra']['message'] ?? 'Semgrep finding'),
                    description: (string) ($item['extra']['message'] ?? ''),
                    severity: $this->mapSeverity((string) ($item['extra']['severity'] ?? 'medium')),
                    filePath: $item['path'] ?? null,
                    lineNumber: isset($item['start']['line']) ? (int) $item['start']['line'] : null,
                    codeSnippet: $item['extra']['lines'] ?? null,
                    owaspCategory: $item['extra']['metadata']['owasp'] ?? null,
                    cweId: $item['extra']['metadata']['cwe'] ?? null,
                    riskScore: $this->riskScoreForSeverity((string) ($item['extra']['severity'] ?? 'medium')),
                );
            }, $decoded['results']);
        }

        if ($result->tool === 'gitleaks' && isset($decoded['leaks']) && is_array($decoded['leaks'])) {
            return array_map(function (array $item): NormalizedFinding {
                return new NormalizedFinding(
                    tool: 'gitleaks',
                    title: (string) ($item['RuleID'] ?? 'Secret exposure'),
                    description: (string) ($item['Description'] ?? 'Potential secret detected.'),
                    severity: Severity::High,
                    filePath: $item['File'] ?? null,
                    lineNumber: isset($item['StartLine']) ? (int) $item['StartLine'] : null,
                    codeSnippet: null,
                    owaspCategory: 'A02 Cryptographic Failures',
                    cweId: $item['CWE'] ?? null,
                    riskScore: 70,
                );
            }, $decoded['leaks']);
        }

        return $result->findings;
    }

    private function mapSeverity(string $severity): Severity
    {
        return match (strtolower($severity)) {
            'critical' => Severity::Critical,
            'high' => Severity::High,
            'low' => Severity::Low,
            'info' => Severity::Info,
            default => Severity::Medium,
        };
    }

    private function riskScoreForSeverity(string $severity): int
    {
        return match (strtolower($severity)) {
            'critical' => 90,
            'high' => 70,
            'low' => 20,
            'info' => 5,
            default => 40,
        };
    }
}
