<?php

use Tests\Support\BoostFixture;
use Tests\Support\Contradictions;
use Tests\Support\Guidelines;

const KIT_STACK_HEADINGS = [
    'blade' => '## Stack: Blade with Alpine',
    'livewire' => '## Stack: Livewire 4 with Flux',
    'inertia-vue' => '## Stack: Inertia 3 with Vue',
    'inertia-react' => '## Stack: Inertia 3 with React',
    'api' => '## Stack: API only',
];

const KIT_STACK_SCOPES = [
    'blade' => ['resources/views/**', 'resources/js/**', 'resources/css/**'],
    'livewire' => ['app/Livewire/**', 'resources/views/**'],
    'inertia-vue' => ['resources/js/**'],
    'inertia-react' => ['resources/js/**'],
    'api' => ['routes/api.php', 'app/Http/**'],
];

beforeEach(function () {
    $this->fixture = BoostFixture::create()->withOverrides();
});

afterEach(function () {
    $this->fixture->destroy();
});

it('renders only the active stack variant, limited to its paths', function (string $stack) {
    config(['kit.stack' => $stack]);

    $guidelines = Guidelines::compose($this->fixture);
    $entry = $guidelines['towebgmbh/laravel-kit/stack'];
    $text = Guidelines::text($guidelines);

    expect($entry['content'])->toContain(KIT_STACK_HEADINGS[$stack])
        ->and($entry['scoped'])->toHaveCount(1)
        ->and($entry['scoped'][0]['paths'] ?? $entry['scoped'][0])->toEqual(KIT_STACK_SCOPES[$stack]);

    foreach (KIT_STACK_HEADINGS as $other => $heading) {
        if ($other !== $stack) {
            expect($text)->not->toContain($heading);
        }
    }

    foreach (Contradictions::SENTENCES as $sentence) {
        expect($text)->not->toContain($sentence);
    }
})->with(array_keys(KIT_STACK_HEADINGS));

it('renders no stack heading for an unknown stack', function () {
    config(['kit.stack' => 'unknown']);

    $text = Guidelines::text(Guidelines::compose($this->fixture));

    expect($text)->not->toContain('## Stack:');
});
