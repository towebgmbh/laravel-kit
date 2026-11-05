<?php

namespace Tests\Support;

use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Towebgmbh\LaravelKit\Kit;

/** Renders guidelines for a fixture project through Boost's GuidelineComposer. */
final class Guidelines
{
    /**
     * @param  array<int, string>|null  $packages  third-party packages selected in boost.json, null for the kit only
     * @return Collection<string, array<string, mixed>>
     */
    public static function compose(BoostFixture $fixture, bool $enforceTests = true, ?array $packages = null): Collection
    {
        $fixture->activate(app());

        $config = new GuidelineConfig;
        $config->enforceTests = $enforceTests;
        $config->aiGuidelines = $packages ?? [Kit::PACKAGE];

        return app(GuidelineComposer::class)->config($config)->guidelines();
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $guidelines
     */
    public static function text(Collection $guidelines): string
    {
        return GuidelineComposer::composeGuidelines($guidelines);
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $guidelines
     * @return Collection<string, array<string, mixed>>
     */
    public static function kitOnly(Collection $guidelines): Collection
    {
        return $guidelines->filter(fn (mixed $guideline, string $key): bool => str_starts_with($key, Kit::PACKAGE.'/'));
    }

    public static function lineCount(string $text): int
    {
        $text = trim($text);

        return $text === '' ? 0 : substr_count($text, "\n") + 1;
    }
}
