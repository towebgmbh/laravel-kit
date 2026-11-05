<?php

namespace Towebgmbh\LaravelKit\Support;

use stdClass;

/**
 * Adds the kit's Stop hooks to .claude/settings.json, keeping every
 * other key and hook. Entries are matched by their command text.
 */
final class ClaudeSettings
{
    public const PATH = '.claude/settings.json';

    public const PINT_COMMAND = 'vendor/bin/pint --dirty --format agent';

    public const DOCS_GUARD_COMMAND = '"$CLAUDE_PROJECT_DIR"/.claude/hooks/kit-docs-guard.sh';

    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const UNCHANGED = 'unchanged';

    public const INVALID = 'invalid';

    public function merge(string $basePath): string
    {
        $file = rtrim($basePath, '/').'/'.self::PATH;
        $exists = is_file($file);
        $settings = new stdClass;

        if ($exists) {
            // Objects stay stdClass so that empty objects survive the round trip.
            $settings = json_decode((string) file_get_contents($file));

            if (! $settings instanceof stdClass
                || isset($settings->hooks) && ! $settings->hooks instanceof stdClass
                || isset($settings->hooks->Stop) && ! is_array($settings->hooks->Stop)) {
                return self::INVALID;
            }
        }

        $stop = $settings->hooks->Stop ?? [];
        $changed = false;

        if (! $this->hasCommand($stop, fn (string $command): bool => $command === self::PINT_COMMAND)) {
            $stop[] = $this->entry(self::PINT_COMMAND);
            $changed = true;
        }

        if (! $this->hasCommand($stop, fn (string $command): bool => str_contains($command, 'kit-docs-guard.sh'))) {
            $stop[] = $this->entry(self::DOCS_GUARD_COMMAND);
            $changed = true;
        }

        if (! $changed) {
            return self::UNCHANGED;
        }

        $settings->hooks ??= new stdClass;
        $settings->hooks->Stop = $stop;

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        return $exists ? self::UPDATED : self::CREATED;
    }

    /**
     * @param  array<int, mixed>  $entries
     * @param  callable(string): bool  $matches
     */
    private function hasCommand(array $entries, callable $matches): bool
    {
        foreach ($entries as $entry) {
            $hooks = $entry instanceof stdClass && is_array($entry->hooks ?? null) ? $entry->hooks : [];

            foreach ($hooks as $hook) {
                if ($hook instanceof stdClass && is_string($hook->command ?? null) && $matches($hook->command)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array{hooks: array<int, array{type: string, command: string}>} */
    private function entry(string $command): array
    {
        return ['hooks' => [['type' => 'command', 'command' => $command]]];
    }
}
