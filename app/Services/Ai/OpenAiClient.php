<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Finding;
use App\Models\Scan;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final class OpenAiClient
{
    /**
     * @return array<string, string>
     */
    public function generateFindingRecommendation(Finding $finding): array
    {
        $fallback = $this->fallbackRecommendation($finding);

        return $this->generateJson(
            systemPrompt: 'You are a cybersecurity code review assistant. Return only valid JSON with the keys plain_english_summary, business_impact, technical_explanation, recommended_fix, secure_code_example.',
            userPrompt: implode("\n", [
                'Analyze this security finding and generate a junior-friendly remediation response.',
                '',
                'Finding:',
                "Title: {$finding->title}",
                "Severity: {$finding->severity}",
                "Tool: {$finding->tool}",
                'File: ' . ($finding->file_path ?? 'N/A'),
                'Line: ' . ($finding->line_number ?? 'N/A'),
                'Description: ' . ($finding->description ?: 'N/A'),
                'Code Snippet:',
                $finding->code_snippet ?: 'N/A',
            ]),
            fallback: $fallback
        );
    }

    /**
     * @return array<string, string>
     */
    public function summarizeScan(Scan $scan): array
    {
        $scan->loadMissing(['findings', 'repository']);

        $fallback = $this->fallbackReportSummary($scan);
        $topFindings = $scan->findings->take(5)->map(static function (Finding $finding): array {
            return [
                'title' => $finding->title,
                'severity' => (string) $finding->severity,
                'tool' => $finding->tool,
                'file_path' => $finding->file_path ?? 'N/A',
            ];
        })->values()->all();

        return $this->generateJson(
            systemPrompt: 'You are a senior security analyst. Return only valid JSON with the keys executive_summary, top_risks, remediation_focus, trend_commentary, and next_steps.',
            userPrompt: implode("\n", [
                'Create a concise executive summary for a repository security scan report.',
                '',
                'Repository:',
                $scan->repository->owner . '/' . $scan->repository->name,
                '',
                'Scores:',
                'Security: ' . ($scan->security_score ?? 'N/A'),
                'Code quality: ' . ($scan->code_quality_score ?? 'N/A'),
                'Dependency: ' . ($scan->dependency_score ?? 'N/A'),
                'Secret: ' . ($scan->secret_score ?? 'N/A'),
                'Overall health: ' . ($scan->overall_health_score ?? 'N/A'),
                '',
                'Counts:',
                'Critical: ' . ($scan->critical_count ?? 0),
                'High: ' . ($scan->high_count ?? 0),
                'Medium: ' . ($scan->medium_count ?? 0),
                'Low: ' . ($scan->low_count ?? 0),
                'Secrets: ' . ($scan->secret_leak_count ?? 0),
                'Dependency risks: ' . ($scan->dependency_risk_count ?? 0),
                '',
                'Top findings:',
                json_encode($topFindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]),
            fallback: $fallback
        );
    }

    /**
     * @param array<string, string> $fallback
     * @return array<string, string>
     */
    private function generateJson(string $systemPrompt, string $userPrompt, array $fallback): array
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            return $fallback;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->post(rtrim((string) config('services.openai.base_url'), '/') . '/chat/completions', [
                    'model' => (string) config('services.openai.model'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (! is_string($content) || $content === '') {
                return $fallback;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            foreach (array_keys($fallback) as $field) {
                if (isset($decoded[$field]) && is_string($decoded[$field]) && $decoded[$field] !== '') {
                    $fallback[$field] = $decoded[$field];
                }
            }

            return $fallback;
        } catch (JsonException|Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array<string, string>
     */
    private function fallbackRecommendation(Finding $finding): array
    {
        $summary = trim(sprintf(
            '%s was detected in %s on line %s.',
            $finding->title,
            $finding->file_path ?? 'an unknown file',
            $finding->line_number ?? 'unknown'
        ));

        return [
            'plain_english_summary' => $summary,
            'business_impact' => 'This issue could expose data, weaken trust, or enable attacker-controlled behavior if exploited.',
            'technical_explanation' => $finding->description ?: 'The scanner reported a security issue that should be reviewed and corrected.',
            'recommended_fix' => 'Refactor the affected code, validate input, remove the unsafe pattern, and add a regression test.',
            'secure_code_example' => $finding->code_snippet ? "Review and replace the vulnerable snippet:\n\n" . $finding->code_snippet : 'Provide a safer implementation that removes the vulnerable behavior.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function fallbackReportSummary(Scan $scan): array
    {
        return [
            'executive_summary' => sprintf(
                'This scan completed with an overall health score of %s and %d total findings.',
                $scan->overall_health_score ?? 'N/A',
                $scan->total_findings ?? $scan->findings->count()
            ),
            'top_risks' => 'Focus first on critical and high severity findings, exposed secrets, and vulnerable dependencies.',
            'remediation_focus' => 'Prioritize the highest-risk issues in the most sensitive files and add regression coverage after fixing them.',
            'trend_commentary' => 'Review the latest run alongside prior scans to determine whether the repository is improving or regressing.',
            'next_steps' => 'Assign owners, fix the highest-risk issues, regenerate the report, and rerun a follow-up scan.',
        ];
    }
}
