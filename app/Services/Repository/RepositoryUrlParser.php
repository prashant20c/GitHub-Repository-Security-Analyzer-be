<?php

declare(strict_types=1);

namespace App\Services\Repository;

final class RepositoryUrlParser
{
    public function parse(string $url): array
    {
        $pattern = '#^https://github\.com/(?P<owner>[A-Za-z0-9_.-]+)/(?P<name>[A-Za-z0-9_.-]+?)(?:\.git)?/?$#';

        if (! preg_match($pattern, $url, $matches)) {
            throw new \InvalidArgumentException('Only public GitHub repository URLs are supported.');
        }

        return [
            'owner' => $matches['owner'],
            'name' => $matches['name'],
            'default_branch' => 'main',
        ];
    }
}
