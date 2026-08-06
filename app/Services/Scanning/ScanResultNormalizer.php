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
                $severity = (string) ($item['extra']['severity'] ?? 'medium');

                return new NormalizedFinding(
                    tool: 'semgrep',
                    title: (string) ($item['check_id'] ?? $item['extra']['message'] ?? 'Semgrep finding'),
                    description: (string) ($item['extra']['message'] ?? ''),
                    severity: $this->mapSeverity($severity),
                    filePath: $item['path'] ?? null,
                    lineNumber: isset($item['start']['line']) ? (int) $item['start']['line'] : null,
                    codeSnippet: $item['extra']['lines'] ?? null,
                    owaspCategory: $this->normalizeMetadataValue($item['extra']['metadata']['owasp'] ?? null),
                    cweId: $this->normalizeMetadataValue($item['extra']['metadata']['cwe'] ?? null),
                    riskScore: $this->riskScoreForSeverity($severity),
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

        if ($result->tool === 'composer-audit') {
            $findings = $this->normalizeComposerAudit($decoded);

            if ($findings !== []) {
                return $findings;
            }
        }

        if ($result->tool === 'npm-audit') {
            $findings = $this->normalizeNpmAudit($decoded);

            if ($findings !== []) {
                return $findings;
            }
        }

        if ($result->tool === 'openai-code-scan' && isset($decoded['findings']) && is_array($decoded['findings'])) {
            return array_values(array_filter(array_map(function (mixed $item): ?NormalizedFinding {
                if (! is_array($item) || ! isset($item['title'], $item['description'])) {
                    return null;
                }

                $severity = (string) ($item['severity'] ?? 'medium');
                $riskScore = max(0, min(100, (int) ($item['risk_score'] ?? $this->riskScoreForSeverity($severity))));

                return new NormalizedFinding(
                    tool: 'openai-code-scan',
                    title: (string) $item['title'],
                    description: (string) $item['description'],
                    severity: $this->mapSeverity($severity),
                    filePath: isset($item['file_path']) ? (string) $item['file_path'] : null,
                    lineNumber: isset($item['line_number']) ? max(1, (int) $item['line_number']) : null,
                    codeSnippet: isset($item['code_snippet']) ? (string) $item['code_snippet'] : null,
                    owaspCategory: isset($item['owasp_category']) ? (string) $item['owasp_category'] : null,
                    cweId: isset($item['cwe_id']) ? (string) $item['cwe_id'] : null,
                    riskScore: $riskScore,
                );
            }, $decoded['findings'])));
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

    private function normalizeMetadataValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && $item !== '') {
                    return $item;
                }

                if (is_int($item) || is_float($item)) {
                    return (string) $item;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<NormalizedFinding>
     */
    private function normalizeComposerAudit(array $decoded): array
    {
        if (! isset($decoded['advisories']) || ! is_array($decoded['advisories'])) {
            return [];
        }

        $findings = [];

        foreach ($decoded['advisories'] as $package => $advisories) {
            if (! is_array($advisories)) {
                continue;
            }

            foreach ($advisories as $advisory) {
                if (! is_array($advisory)) {
                    continue;
                }

                $severity = (string) ($advisory['severity'] ?? 'medium');
                $title = trim((string) ($advisory['title'] ?? $advisory['advisoryId'] ?? 'Composer advisory'));
                $descriptionParts = array_filter([
                    isset($advisory['cve']) ? 'CVE: ' . $advisory['cve'] : null,
                    isset($advisory['link']) ? 'More info: ' . $advisory['link'] : null,
                    isset($advisory['affectedVersions']) ? 'Affected versions: ' . $advisory['affectedVersions'] : null,
                ]);

                $findings[] = new NormalizedFinding(
                    tool: 'composer-audit',
                    title: sprintf('%s: %s', $package, $title),
                    description: implode(' ', $descriptionParts) ?: 'Composer reported a dependency advisory that should be reviewed.',
                    severity: $this->mapSeverity($severity),
                    filePath: 'composer.lock',
                    lineNumber: null,
                    codeSnippet: null,
                    owaspCategory: 'A06 Vulnerable and Outdated Components',
                    cweId: isset($advisory['cve']) ? (string) $advisory['cve'] : null,
                    riskScore: $this->riskScoreForSeverity($severity),
                );
            }
        }

        return $findings;
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<NormalizedFinding>
     */
    private function normalizeNpmAudit(array $decoded): array
    {
        if (isset($decoded['vulnerabilities']) && is_array($decoded['vulnerabilities'])) {
            $findings = [];

            foreach ($decoded['vulnerabilities'] as $package => $vulnerability) {
                if (! is_array($vulnerability)) {
                    continue;
                }

                $severity = (string) ($vulnerability['severity'] ?? 'medium');
                $via = $vulnerability['via'] ?? [];
                $descriptionParts = array_filter([
                    isset($vulnerability['range']) ? 'Vulnerable range: ' . $vulnerability['range'] : null,
                    $this->npmAuditViaDescription($via),
                ]);

                $findings[] = new NormalizedFinding(
                    tool: 'npm-audit',
                    title: sprintf('%s: %s', $package, (string) ($vulnerability['title'] ?? 'npm advisory')),
                    description: implode(' ', $descriptionParts) ?: 'npm reported a dependency advisory that should be reviewed.',
                    severity: $this->mapSeverity($severity),
                    filePath: 'package-lock.json',
                    lineNumber: null,
                    codeSnippet: null,
                    owaspCategory: 'A06 Vulnerable and Outdated Components',
                    cweId: $this->npmAuditCweId($via),
                    riskScore: $this->riskScoreForSeverity($severity),
                );
            }

            return $findings;
        }

        if (! isset($decoded['advisories']) || ! is_array($decoded['advisories'])) {
            return [];
        }

        $findings = [];

        foreach ($decoded['advisories'] as $advisory) {
            if (! is_array($advisory)) {
                continue;
            }

            $package = (string) ($advisory['module_name'] ?? $advisory['name'] ?? 'npm-package');
            $severity = (string) ($advisory['severity'] ?? 'medium');

            $findings[] = new NormalizedFinding(
                tool: 'npm-audit',
                title: sprintf('%s: %s', $package, (string) ($advisory['title'] ?? 'npm advisory')),
                description: (string) ($advisory['overview'] ?? $advisory['recommendation'] ?? 'npm reported a dependency advisory that should be reviewed.'),
                severity: $this->mapSeverity($severity),
                filePath: 'package-lock.json',
                lineNumber: null,
                codeSnippet: null,
                owaspCategory: 'A06 Vulnerable and Outdated Components',
                cweId: isset($advisory['cves'][0]) ? (string) $advisory['cves'][0] : null,
                riskScore: $this->riskScoreForSeverity($severity),
            );
        }

        return $findings;
    }

    /**
     * @param mixed $via
     */
    private function npmAuditViaDescription(mixed $via): ?string
    {
        if (! is_array($via) || $via === []) {
            return null;
        }

        $messages = [];

        foreach ($via as $item) {
            if (is_string($item)) {
                $messages[] = $item;

                continue;
            }

            if (is_array($item) && isset($item['title']) && is_string($item['title'])) {
                $messages[] = $item['title'];
            }
        }

        $messages = array_values(array_filter(array_unique($messages)));

        return $messages === [] ? null : 'Via: ' . implode('; ', $messages);
    }

    /**
     * @param mixed $via
     */
    private function npmAuditCweId(mixed $via): ?string
    {
        if (! is_array($via)) {
            return null;
        }

        foreach ($via as $item) {
            if (is_array($item) && isset($item['source']) && is_scalar($item['source'])) {
                return (string) $item['source'];
            }
        }

        return null;
    }
}
