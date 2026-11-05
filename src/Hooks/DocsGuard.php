<?php

namespace Towebgmbh\LaravelKit\Hooks;

use Closure;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Claude Code Stop hook: sends the agent back once when the turn changed
 * the project structure but not docs/. Fails open on every error.
 */
final class DocsGuard
{
    public const REASON = 'This turn changed the project structure (new files, migrations or routes), but docs/ did not. Update the affected docs (specification status, domain map, decisions, pitfalls) or state briefly why no documentation change is needed.';

    private const EDIT_TOOLS = ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'];

    /** Paths where any change is structural. */
    private const ALWAYS_STRUCTURAL = ['database/migrations/', 'routes/'];

    /** Paths where only a new file is structural. */
    private const NEW_FILE_STRUCTURAL = ['app/', 'resources/js/', 'resources/views/'];

    /** @var Closure(string, string): ?bool */
    private readonly Closure $isTracked;

    /**
     * @param  (Closure(string $cwd, string $path): ?bool)|null  $isTracked  true when git tracks the path, false when not, null outside a git work tree
     */
    public function __construct(?Closure $isTracked = null)
    {
        $this->isTracked = $isTracked ?? self::gitIsTracked(...);
    }

    /** Returns the hook output, or null to let the agent stop. */
    public function handle(string $stdin): ?string
    {
        try {
            return $this->evaluate($stdin);
        } catch (Throwable) {
            return null;
        }
    }

    private function evaluate(string $stdin): ?string
    {
        $input = json_decode($stdin, true);

        if (! is_array($input) || ($input['stop_hook_active'] ?? false) === true) {
            return null;
        }

        $cwd = is_string($input['cwd'] ?? null) && $input['cwd'] !== '' ? $input['cwd'] : (string) getcwd();
        $cwd = rtrim($cwd, '/');

        if (! $this->enabled($cwd)) {
            return null;
        }

        $transcript = $input['transcript_path'] ?? null;

        if (! is_string($transcript) || $transcript === '' || ! is_file($transcript) || ! is_readable($transcript)) {
            return null;
        }

        $lines = file($transcript, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return null;
        }

        $edits = $this->editsOfLastTurn($lines, $cwd);
        $structural = false;

        foreach ($edits as $path => $tools) {
            if (str_starts_with($path, 'docs/')) {
                return null;
            }

            $structural = $structural || $this->isStructural($cwd, $path, $tools);
        }

        return $structural ? json_encode(['decision' => 'block', 'reason' => self::REASON]) : null;
    }

    private function enabled(string $cwd): bool
    {
        $file = $cwd.'/config/kit.php';

        if (! is_file($file)) {
            return true;
        }

        try {
            $config = (static fn (string $file): mixed => include $file)($file);
        } catch (Throwable) {
            return true;
        }

        return ! (is_array($config) && ($config['hooks']['docs_guard'] ?? true) === false);
    }

    /**
     * Paths edited after the last user prompt, with the tools that edited them.
     *
     * @param  array<int, string>  $lines
     * @return array<string, array<int, string>>
     */
    private function editsOfLastTurn(array $lines, string $cwd): array
    {
        $edits = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (! is_array($entry)) {
                continue;
            }

            $type = $entry['type'] ?? null;
            $content = $entry['message']['content'] ?? null;

            if ($type === 'user' && $this->isPrompt($entry, $content)) {
                $edits = [];

                continue;
            }

            if ($type !== 'assistant' || ! is_array($content)) {
                continue;
            }

            foreach ($content as $block) {
                if (! is_array($block) || ($block['type'] ?? null) !== 'tool_use' || ! in_array($block['name'] ?? null, self::EDIT_TOOLS, true)) {
                    continue;
                }

                $path = $block['input']['file_path'] ?? $block['input']['notebook_path'] ?? null;

                if (is_string($path) && $path !== '') {
                    $edits[$this->relative($path, $cwd)][] = $block['name'];
                }
            }
        }

        return $edits;
    }

    /** A user entry is a prompt unless it carries tool results or is a meta message. */
    private function isPrompt(array $entry, mixed $content): bool
    {
        if (($entry['isMeta'] ?? false) === true) {
            return false;
        }

        if (is_string($content)) {
            return true;
        }

        if (! is_array($content)) {
            return false;
        }

        foreach ($content as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'tool_result') {
                return false;
            }
        }

        return true;
    }

    private function relative(string $path, string $cwd): string
    {
        if ($cwd !== '' && str_starts_with($path, $cwd.'/')) {
            $path = substr($path, strlen($cwd) + 1);
        }

        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        return $path;
    }

    /** @param  array<int, string>  $tools */
    private function isStructural(string $cwd, string $path, array $tools): bool
    {
        foreach (self::ALWAYS_STRUCTURAL as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        foreach (self::NEW_FILE_STRUCTURAL as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $this->isNew($cwd, $path, $tools);
            }
        }

        return false;
    }

    /** @param  array<int, string>  $tools */
    private function isNew(string $cwd, string $path, array $tools): bool
    {
        $tracked = ($this->isTracked)($cwd, $path);

        if ($tracked === null) {
            return in_array('Write', $tools, true);
        }

        return $tracked === false;
    }

    private static function gitIsTracked(string $cwd, string $path): ?bool
    {
        $inside = new Process(['git', '-C', $cwd, 'rev-parse', '--is-inside-work-tree']);
        $inside->run();

        if (! $inside->isSuccessful() || trim($inside->getOutput()) !== 'true') {
            return null;
        }

        $ls = new Process(['git', '-C', $cwd, 'ls-files', '--error-unmatch', '--', $path]);
        $ls->run();

        return $ls->isSuccessful();
    }
}
