<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Data\ScannerResult;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\Facades\File;

final class OpenAiCodeScanTool implements ScanTool
{
    private const ALLOWED_EXTENSIONS = [
        'php', 'js', 'jsx', 'ts', 'tsx', 'vue', 'py', 'rb', 'go', 'java', 'kt',
        'cs', 'c', 'h', 'cpp', 'cxx', 'rs', 'swift', 'sql', 'sh', 'bash', 'zsh',
        'yaml', 'yml', 'json', 'xml', 'toml', 'ini', 'conf', 'config', 'html', 'css',
    ];

    private const IGNORED_DIRECTORY_NAMES = [
        '.git', '.github', 'node_modules', 'vendor', 'dist', 'build', 'coverage',
        'storage', 'bootstrap', '__pycache__', '.venv', 'venv',
    ];

    private const IGNORED_FILE_NAMES = [
        '.env', '.env.example', '.env.local', '.env.production', 'composer.lock',
        'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'Gemfile.lock',
    ];

    public function __construct(private readonly OpenAiClient $client)
    {
    }

    public function name(): string
    {
        return 'openai-code-scan';
    }

    public function scan(string $repositoryPath): ScannerResult
    {
        $maxFiles = (int) config('security.openai_code_scan_max_files', 60);
        $maxFileBytes = (int) config('security.openai_code_scan_max_file_bytes', 12000);
        $maxTotalBytes = (int) config('security.openai_code_scan_max_total_bytes', 120000);
        $files = [];

        foreach (File::allFiles($repositoryPath) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $parts = explode('/', $relativePath);
            $extension = strtolower($file->getExtension());

            if (array_intersect($parts, self::IGNORED_DIRECTORY_NAMES) !== []) {
                continue;
            }

            if (in_array($file->getFilename(), self::IGNORED_FILE_NAMES, true)
                || str_contains(strtolower($file->getFilename()), 'secret')
                || str_ends_with(strtolower($file->getFilename()), '.pem')
                || str_ends_with(strtolower($file->getFilename()), '.key')
                || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $files[] = [$relativePath, $file->getPathname()];
        }

        usort($files, static fn (array $left, array $right): int => strcmp($left[0], $right[0]));

        $context = [];
        $totalBytes = 0;

        foreach (array_slice($files, 0, $maxFiles) as [$relativePath, $absolutePath]) {
            $contents = @file_get_contents($absolutePath);

            if (! is_string($contents) || str_contains($contents, "\0")) {
                continue;
            }

            $contents = mb_substr($contents, 0, $maxFileBytes);
            $bytes = strlen($contents);

            if ($totalBytes + $bytes > $maxTotalBytes) {
                break;
            }

            $totalBytes += $bytes;
            $context[] = "### {$relativePath}\n{$contents}";
        }

        if ($context === []) {
            return new ScannerResult(
                tool: $this->name(),
                success: true,
                rawOutput: json_encode(['findings' => []], JSON_THROW_ON_ERROR),
            );
        }

        return $this->client->scanCode(implode("\n\n", $context));
    }
}
