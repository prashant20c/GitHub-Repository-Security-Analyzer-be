<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CodeQualityAnalyzer
{
    public function score(string $repositoryPath): int
    {
        $files = 0;
        $todoCount = 0;
        $complexityHits = 0;
        $basenames = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repositoryPath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
                continue;
            }

            $files++;
            $basenames[$file->getBasename()] = ($basenames[$file->getBasename()] ?? 0) + 1;

            $contents = @file_get_contents($path) ?: '';
            $todoCount += preg_match_all('/TODO|FIXME/i', $contents) ?: 0;
            $complexityHits += preg_match_all('/\b(if|else if|switch|catch|for|foreach|while)\b/i', $contents) ?: 0;
        }

        $score = 100;
        $score -= $files > 250 ? 10 : 0;
        $score -= $todoCount > 10 ? 5 : 0;
        $score -= $complexityHits > 50 ? 10 : 0;
        $score -= count(array_filter($basenames, static fn (int $count): bool => $count > 1)) > 10 ? 10 : 0;

        return max(0, $score);
    }
}
