<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 4: Stack-Varianten mit @scoped, Stack-Erkennung und Kontextbudget-Test

Kind: implement · Status: pending · Model: sonnet · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Add the stack guideline that renders only the variant in `config('kit.stack')`, limited with `@scoped` to the variant's paths, a `StackDetector` that derives the stack from a project's `composer.json` and `package.json` (for `kit:install` in package 7 and `kit:upgrade` in package 11), and the context budget test over all combinations (D-35).

Public repository (D-1): no client names. Content in English (D-2). Each variant fixes folders and patterns only; Boost supplies the version APIs (D-4, D-12).

## Context to read

- Concept sections Design (Einklinken in Boost, Kontextbudget, Öffentliches Repo) and Decisions D-1, D-4, D-12, D-35.
- Package 3 handoff; `resources/boost/guidelines/*.blade.php`, `tests/Support/Guidelines.php` (`text(Collection $guidelines): string`), `tests/Support/BoostFixture.php`, `tests/Support/Contradictions.php`, `tests/Feature/Guidelines/KitGuidelinesTest.php`.
- v1 variants as source of folder conventions: `resources/legacy/v1/templates/.claude/rules/variants/03-frontend-blade.md`, `03-frontend-livewire.md`, `03-frontend-inertia-vue.md`.
- Boost: `vendor/laravel/boost/src/Concerns/RendersBladeGuidelines.php` (`markScopedBlocks()`, `extractScopedBlocks()`: with default config the scoped body stays in the text and is also returned in the guideline's `scoped` array), `.ai/pest/core.blade.php` (example of `@scoped`). Boost's stack guidelines to avoid duplicating: `.ai/livewire/`, `.ai/fluxui-free/`, `.ai/inertia-vue/`, `.ai/inertia-react/`, `.ai/wayfinder/`, `.ai/tailwindcss/`.

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `resources/boost/guidelines/stack.blade.php` (new): `@switch(config('kit.stack'))`; each case is one `@scoped([...])` block with exactly one of these headings:
   - `## Stack: Blade with Alpine`, scoped to `['resources/views/**', 'resources/js/**', 'resources/css/**']`: Blade components with props (write `@props` so Boost's placeholder handling keeps it literal), Alpine, Tailwind 4, Vite; folders `resources/views/components`, per-domain views.
   - `## Stack: Livewire 4 with Flux`, scoped to `['app/Livewire/**', 'resources/views/**']`: Livewire 4 component format, Flux UI, no Volt; folder per domain.
   - `## Stack: Inertia 3 with Vue`, scoped to `['resources/js/**']`: pages in `resources/js/pages`, `<script setup lang="ts">`, TypeScript, routes only via Wayfinder imports, components and composables folders.
   - `## Stack: Inertia 3 with React`, scoped to `['resources/js/**']`: same for React with `.tsx`.
   - `## Stack: API only`, scoped to `['routes/api.php', 'app/Http/**']`: API Resources, Sanctum, versioned routes `/api/v1`, one error format.
   Unknown values render nothing (kit:doctor reports them, package 12). Keep each variant short (target 30 lines or less).
2. `src/Support/StackDetector.php` (new): `final class StackDetector` with `public function detect(string $basePath): ?string` reading `composer.json` (`require` + `require-dev`) and `package.json` (`dependencies` + `devDependencies`): `livewire/livewire` -> `livewire`; `inertiajs/inertia-laravel` with `@inertiajs/react` -> `inertia-react`; with `@inertiajs/vue3` -> `inertia-vue`; both Livewire and Inertia, or Inertia with both or neither JS adapter -> `null` (ambiguous); `laravel/sanctum` present and `resources/views` holds no Blade file other than `welcome.blade.php` -> `api`; otherwise `blade`. Missing or invalid JSON files count as empty.
3. `tests/Feature/Guidelines/ContextBudgetTest.php` (new): for every combination of the five stacks, three architecture modes and two docs layouts (flat, split), `Guidelines::lineCount(Guidelines::text(Guidelines::kitOnly(Guidelines::compose($fixture))))` is at most 200; the failure message names the combination and the count. If a combination exceeds the budget, shorten the text (first the stack variant, then repetitions in package 3's guidelines) and never raise the limit.

## Acceptance criteria

Serves: AC-2, AC-12, AC-13

- For each of the five stacks the composed guidelines contain `towebgmbh/laravel-kit/stack` with exactly its heading and none of the other four, and the entry's `scoped` array holds one block with the listed paths. Evidence: `StackGuidelineTest`.
- An unknown `kit.stack` renders no stack heading. Evidence: `StackGuidelineTest`.
- `StackDetector` returns the documented value for every rule including the ambiguous cases. Evidence: `StackDetectorTest`.
- Every combination stays at or below 200 lines. Evidence: `ContextBudgetTest`.
- No client name, customer data or real project name in the files of this package or its commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Guidelines/StackGuidelineTest.php`: dataset of five stacks plus unknown; scoped paths; contradiction sentences absent.
- `tests/Unit/StackDetectorTest.php`: dataset with temp directories for livewire, inertia-vue, inertia-react, ambiguous (livewire + inertia), api, blade default, missing files.
- `tests/Feature/Guidelines/ContextBudgetTest.php` (task 3).

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- Using `StackDetector` in commands (packages 7 and 11).
- Hints for Livewire 3 or Inertia 1/2 (concept Out of scope).
