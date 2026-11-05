<?php

namespace Towebgmbh\LaravelKit;

use InvalidArgumentException;

final class Manifest
{
    public const PATH = '.ai/kit-manifest.json';

    private ?string $version = null;

    private ?string $project = null;

    /** @var array<string, array{sha256: string}> */
    private array $files = [];

    /** @var array<string, array{boost_file: string, boost_sha256: string}> */
    private array $overrides = [];

    private function __construct(private readonly string $basePath, private bool $exists) {}

    public static function load(string $basePath): self
    {
        $file = rtrim($basePath, '/').'/'.self::PATH;

        if (! is_file($file)) {
            return new self($basePath, false);
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('The kit manifest is not valid JSON: '.self::PATH);
        }

        $manifest = new self($basePath, true);
        $manifest->version = $data['version'] ?? null;
        $manifest->project = $data['project'] ?? null;
        $manifest->files = $data['files'] ?? [];
        $manifest->overrides = $data['overrides'] ?? [];

        return $manifest;
    }

    public static function hash(string $contents): string
    {
        return hash('sha256', str_replace("\r\n", "\n", $contents));
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function project(): ?string
    {
        return $this->project;
    }

    public function setProject(string $project): void
    {
        if (! in_array($project, ['new', 'existing'], true)) {
            throw new InvalidArgumentException("Project must be 'new' or 'existing', got '{$project}'.");
        }

        $this->project = $project;
    }

    public function recordFile(string $relativePath, string $contents): void
    {
        $this->files[$relativePath] = ['sha256' => self::hash($contents)];
    }

    public function fileHash(string $relativePath): ?string
    {
        return $this->files[$relativePath]['sha256'] ?? null;
    }

    public function forgetFile(string $relativePath): void
    {
        unset($this->files[$relativePath]);
    }

    /** @return array<string, array{sha256: string}> */
    public function files(): array
    {
        return $this->files;
    }

    public function recordOverride(string $relativePath, string $boostFile, string $boostSha256): void
    {
        $this->overrides[$relativePath] = ['boost_file' => $boostFile, 'boost_sha256' => $boostSha256];
    }

    public function forgetOverride(string $relativePath): void
    {
        unset($this->overrides[$relativePath]);
    }

    /** @return array<string, array{boost_file: string, boost_sha256: string}> */
    public function overrides(): array
    {
        return $this->overrides;
    }

    public function save(): void
    {
        $file = rtrim($this->basePath, '/').'/'.self::PATH;

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        $files = $this->files;
        $overrides = $this->overrides;
        ksort($files);
        ksort($overrides);

        $data = [
            'files' => (object) $files,
            'overrides' => (object) $overrides,
            'project' => $this->project,
            'version' => $this->version ?? Kit::VERSION,
        ];

        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        );

        $this->exists = true;
    }
}
