<?php

namespace Towebgmbh\LaravelKit\Upgrade;

/**
 * Derives kit.architecture from the "Deviations from Kit Rules" section of a
 * v1 docs/GUIDELINES.md (D-30).
 */
final class ArchitectureResolver
{
    public const GUIDELINES = 'docs/GUIDELINES.md';

    public const SECTION = '## Deviations from Kit Rules';

    /** @return string|null actions, services or migrating; null when the text needs a decision */
    public function resolve(string $basePath): ?string
    {
        $file = rtrim($basePath, '/').'/'.self::GUIDELINES;

        if (! is_file($file)) {
            return 'actions';
        }

        $text = $this->deviations((string) file_get_contents($file));

        return match (true) {
            $text === null, $text === '' => 'actions',
            str_contains(strtolower($text), 'migrat') => 'migrating',
            str_contains(strtolower($text), 'service') => 'services',
            default => null,
        };
    }

    /** The section text without HTML comments, blockquote lines and blank lines; null without the section. */
    public function deviations(string $contents): ?string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $contents));
        $start = null;

        foreach ($lines as $index => $line) {
            if (rtrim($line) === self::SECTION) {
                $start = $index + 1;

                break;
            }
        }

        if ($start === null) {
            return null;
        }

        $section = [];

        foreach (array_slice($lines, $start) as $line) {
            if (str_starts_with($line, '## ')) {
                break;
            }

            $section[] = $line;
        }

        $text = (string) preg_replace('/<!--.*?(-->|$)/s', '', implode("\n", $section));

        $kept = array_filter(
            explode("\n", $text),
            fn (string $line): bool => trim($line) !== '' && ! preg_match('/^\s*>/', $line),
        );

        return implode("\n", $kept);
    }
}
