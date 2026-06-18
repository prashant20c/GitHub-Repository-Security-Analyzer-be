<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Finding;
use App\Models\AiRecommendation;

final class RecommendationService
{
    public function __construct(private readonly OpenAiClient $client)
    {
    }

    public function generate(Finding $finding): AiRecommendation
    {
        $payload = $this->client->generateFindingRecommendation($finding);

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
}
