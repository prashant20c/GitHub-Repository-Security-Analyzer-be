<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\ScannerResult;
use App\Enums\Severity;
use App\Services\Scanning\ScanResultNormalizer;
use PHPUnit\Framework\TestCase;

final class ScanResultNormalizerTest extends TestCase
{
    public function test_it_normalizes_composer_audit_advisories(): void
    {
        $normalizer = new ScanResultNormalizer();

        $result = new ScannerResult(
            tool: 'composer-audit',
            success: true,
            rawOutput: json_encode([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-1234',
                            'title' => 'Example advisory',
                            'severity' => 'high',
                            'cve' => 'CVE-2026-0001',
                            'link' => 'https://example.test/advisory',
                            'affectedVersions' => '<2.0.0',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $findings = $normalizer->normalize($result);

        $this->assertCount(1, $findings);
        $this->assertSame('composer-audit', $findings[0]->tool);
        $this->assertSame('vendor/package: Example advisory', $findings[0]->title);
        $this->assertSame(Severity::High, $findings[0]->severity);
        $this->assertSame('composer.lock', $findings[0]->filePath);
        $this->assertSame('CVE-2026-0001', $findings[0]->cweId);
    }

    public function test_it_normalizes_npm_audit_vulnerabilities(): void
    {
        $normalizer = new ScanResultNormalizer();

        $result = new ScannerResult(
            tool: 'npm-audit',
            success: true,
            rawOutput: json_encode([
                'vulnerabilities' => [
                    'lodash' => [
                        'severity' => 'critical',
                        'title' => 'Prototype Pollution',
                        'range' => '<4.17.21',
                        'via' => [
                            [
                                'title' => 'Prototype Pollution',
                                'source' => 1337,
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $findings = $normalizer->normalize($result);

        $this->assertCount(1, $findings);
        $this->assertSame('npm-audit', $findings[0]->tool);
        $this->assertSame('lodash: Prototype Pollution', $findings[0]->title);
        $this->assertSame(Severity::Critical, $findings[0]->severity);
        $this->assertSame('package-lock.json', $findings[0]->filePath);
        $this->assertSame('1337', $findings[0]->cweId);
    }

    public function test_it_normalizes_openai_code_scan_findings(): void
    {
        $normalizer = new ScanResultNormalizer();

        $result = new ScannerResult(
            tool: 'openai-code-scan',
            success: true,
            rawOutput: json_encode([
                'findings' => [
                    [
                        'title' => 'SQL injection through user input',
                        'description' => 'A request parameter reaches a query without parameter binding.',
                        'severity' => 'high',
                        'file_path' => 'app/Http/Controllers/UserController.php',
                        'line_number' => 42,
                        'code_snippet' => '$query = "...";',
                        'owasp_category' => 'A03 Injection',
                        'cwe_id' => 'CWE-89',
                        'risk_score' => 84,
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $findings = $normalizer->normalize($result);

        $this->assertCount(1, $findings);
        $this->assertSame('openai-code-scan', $findings[0]->tool);
        $this->assertSame(Severity::High, $findings[0]->severity);
        $this->assertSame('CWE-89', $findings[0]->cweId);
        $this->assertSame(84, $findings[0]->riskScore);
    }
}
