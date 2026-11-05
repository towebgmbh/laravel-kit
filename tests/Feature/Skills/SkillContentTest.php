<?php

use Tests\Support\Contradictions;
use Towebgmbh\LaravelKit\Kit;

function skillFiles(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Kit::path('resources/boost/skills'), FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $files[$file->getPathname()] = $file->getPathname();
    }

    return $files;
}

it('has acceptance criteria and open questions in the feature template', function () {
    $template = file_get_contents(Kit::path('resources/boost/skills/kit-spec/FEATURE_TEMPLATE.md'));

    expect($template)->toContain('## Open Questions')
        ->toContain('## Acceptance Criteria')
        ->toContain('AC-1')
        ->toContain('Do not implement while a blocking question is open; ask the user instead of guessing.')
        ->toContain('blocking')
        ->not->toContain('Done When');
});

it('checks acceptance criteria coverage in kit-review', function () {
    $review = file_get_contents(Kit::path('resources/boost/skills/kit-review/SKILL.md'));

    expect($review)->toContain('acceptance criterion')
        ->toContain('laravel-best-practices')
        ->toContain('testing-best-practices');
});

it('contains no contradiction sentence in any skill file', function (string $path) {
    $text = file_get_contents($path);

    foreach ([...Contradictions::SENTENCES, 'full suite after each phase'] as $sentence) {
        expect($text)->not->toContain($sentence);
    }
})->with(fn () => array_values(skillFiles()));
