<?php

declare(strict_types=1);

namespace App\Services\Repository;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

final class RepositoryCloner
{
    public function cloneRepository(string $url, string $targetPath, int $timeoutSeconds = 900): string
    {
        File::ensureDirectoryExists($targetPath);

        $process = new Process(['git', 'clone', '--depth', '1', $url, $targetPath]);
        $process->setTimeout($timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: 'Repository clone failed.');
        }

        return trim((string) $process->getOutput());
    }

    public function commitHash(string $repositoryPath): ?string
    {
        $process = new Process(['git', '-C', $repositoryPath, 'rev-parse', 'HEAD']);
        $process->setTimeout(30);
        $process->run();

        return $process->isSuccessful() ? trim((string) $process->getOutput()) : null;
    }

    public function deleteClone(string $targetPath): void
    {
        File::deleteDirectory($targetPath);
    }
}
