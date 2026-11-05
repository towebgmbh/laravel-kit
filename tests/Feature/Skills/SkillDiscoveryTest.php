<?php

use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Support\SkillParseFailures;
use Laravel\Roster\ProjectManager;
use Tests\Support\BoostFixture;
use Towebgmbh\LaravelKit\Kit;

const KIT_SKILLS_SO_FAR = ['kit-brief', 'kit-review', 'kit-spec'];

beforeEach(function () {
    $this->fixture = BoostFixture::create();
});

afterEach(function () {
    $this->fixture->destroy();
});

function kitSkills(BoostFixture $fixture)
{
    $fixture->activate(app());

    $config = new GuidelineConfig;
    $config->aiGuidelines = [Kit::PACKAGE];

    return (new SkillComposer(app(ProjectManager::class), $config))->skills()
        ->filter(fn ($skill, string $key): bool => str_starts_with($key, 'kit-'));
}

it('discovers the kit skills with descriptions', function () {
    $skills = kitSkills($this->fixture);

    expect($skills->keys()->sort()->values()->all())->toBe(KIT_SKILLS_SO_FAR);

    foreach ($skills as $skill) {
        expect($skill->description)->not->toBeEmpty();
    }
});

it('records no skill parse failure', function () {
    kitSkills($this->fixture);

    expect(app(SkillParseFailures::class)->all())->toBeEmpty();
});

it('names every skill like its folder', function () {
    foreach (KIT_SKILLS_SO_FAR as $folder) {
        $text = file_get_contents(Kit::path("resources/boost/skills/{$folder}/SKILL.md"));

        expect($text)->toContain("\nname: {$folder}\n");
    }
});
