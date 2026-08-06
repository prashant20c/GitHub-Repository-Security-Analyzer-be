<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class UnauthenticatedRequestTest extends TestCase
{
    public function test_api_requests_return_a_json_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
