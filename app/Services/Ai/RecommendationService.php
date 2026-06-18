<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Finding;
use App\Models\AiRecommendation;

final class RecommendationService
{
    public function generate(Finding $finding): AiRecommendation
    {
        $payload = $this->buildPrompt($finding);

        return $finding->recommendation()->updateOrCreate(
            ['finding_id' => $finding->id],
            [
                'plain_english_summary' => $payload['plain_english_summary'],
                'business_impact' => $payload['business_impact'],
                'technical_explanation' => $payload['technical_explanation'],
                'recommended_fix' => $payload['recommended_fix'],
                'secure_code_example' => $payload['secure_code_example'],
            ]
        );
    }

    /**
     * The first implementation is deterministic and safe.
     * Swap this method for an OpenAI call once API credentials are configured.
     *
     * @return array<string, string>
     */
    private function buildPrompt(Finding $finding): array
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
}
