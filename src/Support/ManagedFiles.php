<?php

namespace Towebgmbh\LaravelKit\Support;

use Closure;
use Towebgmbh\LaravelKit\Manifest;

/**
 * Writes kit files into a project without overwriting changes unasked.
 * Managed files are recorded in the manifest; files written once belong
 * to the project afterwards.
 */
final class ManagedFiles
{
    public const CREATED = 'created';

    public const UNCHANGED = 'unchanged';

    public const REPLACED = 'replaced';

    public const SKIPPED = 'skipped';

    public const EXISTS = 'exists';

    /**
     * @param  Closure(string): bool  $confirm  asked before a differing file is replaced
     */
    public function __construct(
        private readonly string $basePath,
        private readonly Manifest $manifest,
        private readonly bool $force,
        private readonly Closure $confirm,
        private readonly bool $replaceUnmodified = false,
    ) {}

    public function manifest(): Manifest
    {
        return $this->manifest;
    }

    public function write(string $relativePath, string $contents, bool $managed = true): string
    {
        $file = $this->absolute($relativePath);

        if (! is_file($file)) {
            $this->put($file, $contents);
            $result = self::CREATED;
        } elseif (Manifest::hash((string) file_get_contents($file)) === Manifest::hash($contents)) {
            $result = self::UNCHANGED;
        } elseif ($this->force || $this->isUnmodified($relativePath, $file) || ($this->confirm)($relativePath)) {
            $this->put($file, $contents);
            $result = self::REPLACED;
        } else {
            return self::SKIPPED;
        }

        if ($managed) {
            $this->manifest->recordFile($relativePath, $contents);
        }

        return $result;
    }

    /** Deletes a file whose contents still match the manifest; returns false when it was kept. */
    public function deleteIfUnmodified(string $relativePath): bool
    {
        $file = $this->absolute($relativePath);

        if (is_file($file)) {
            if ($this->manifest->fileHash($relativePath) !== Manifest::hash((string) file_get_contents($file))) {
                return false;
            }

            unlink($file);
        }

        return true;
    }

    private function isUnmodified(string $relativePath, string $file): bool
    {
        return $this->replaceUnmodified
            && $this->manifest->fileHash($relativePath) === Manifest::hash((string) file_get_contents($file));
    }

    public function writeOnce(string $relativePath, string $contents): string
    {
        $file = $this->absolute($relativePath);

        if (file_exists($file)) {
            return self::EXISTS;
        }

        $this->put($file, $contents);

        return self::CREATED;
    }

    private function absolute(string $relativePath): string
    {
        return rtrim($this->basePath, '/').'/'.ltrim($relativePath, '/');
    }

    private function put(string $file, string $contents): void
    {
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $contents);
    }
}
