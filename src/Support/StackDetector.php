<?php

namespace Towebgmbh\LaravelKit\Support;

/** Derives the kit stack of a project from its composer.json, package.json and views. */
final class StackDetector
{
    /**
     * @return string|null a value of Kit::STACKS, or null when the project is ambiguous
     */
    public function detect(string $basePath): ?string
    {
        $php = $this->packages($basePath.'/composer.json', ['require', 'require-dev']);
        $js = $this->packages($basePath.'/package.json', ['dependencies', 'devDependencies']);

        $livewire = isset($php['livewire/livewire']);
        $inertia = isset($php['inertiajs/inertia-laravel']);

        if ($livewire && $inertia) {
            return null;
        }

        if ($livewire) {
            return 'livewire';
        }

        if ($inertia) {
            $react = isset($js['@inertiajs/react']);
            $vue = isset($js['@inertiajs/vue3']);

            return match (true) {
                $react && ! $vue => 'inertia-react',
                $vue && ! $react => 'inertia-vue',
                default => null,
            };
        }

        if (isset($php['laravel/sanctum']) && ! $this->hasBladeViews($basePath.'/resources/views')) {
            return 'api';
        }

        return 'blade';
    }

    /**
     * @param  array<int, string>  $sections
     * @return array<string, mixed>
     */
    private function packages(string $file, array $sections): array
    {
        if (! is_file($file)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            return [];
        }

        $packages = [];

        foreach ($sections as $section) {
            if (is_array($data[$section] ?? null)) {
                $packages += $data[$section];
            }
        }

        return $packages;
    }

    private function hasBladeViews(string $directory): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php') && $file->getFilename() !== 'welcome.blade.php') {
                return true;
            }
        }

        return false;
    }
}
