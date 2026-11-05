# Frontend Conventions — Blade

## Blade Templates
- Use layouts with `@extends` and `@section` / `@yield`, or component-based layouts with `<x-layouts.app>`.
- Prefer Blade components (`<x-button>`, `<x-alert>`) over repetitive HTML. Extract early.
- Components live in `resources/views/components/`, organized by purpose.
- Use `@props` directive in components to declare expected data.
- Always escape output with `{{ }}`. Only use `{!! !!}` when deliberately rendering trusted HTML.

## Template Organization
- Views follow the domain structure: `resources/views/{domain}/` (e.g., `vehicles/index.blade.php`).
- Partials: `resources/views/{domain}/partials/` for reusable fragments within a domain.
- Shared partials across domains: `resources/views/components/` or `resources/views/partials/`.
- Layouts: `resources/views/layouts/` or `resources/views/components/layouts/`.

## Tailwind CSS
- Use Tailwind utility classes directly. No custom CSS unless absolutely necessary.
- Extract repeated patterns into Blade components, not `@apply` directives.
- Use consistent spacing scale (p-4, p-6, p-8 — not arbitrary values).
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints.

## Forms
- Use `@csrf` on every form. Use `@method('PUT')` / `@method('DELETE')` for non-POST methods.
- Display validation errors with `@error('field')` directive or `$errors->get('field')`.
- Repopulate fields with `old('field')` after validation failure.
- Flash messages via `session('success')`, `session('error')` — display in layout.

## JavaScript
- Use Alpine.js for interactive components (dropdowns, modals, tabs, toggles).
- Keep Alpine logic inline with `x-data` for simple cases. Extract to `Alpine.data()` for complex components.
- For heavier interactivity, use vanilla JS or a small library — not a full SPA framework.
- JS files go in `resources/js/`, compiled via Vite.

## Assets
- Compile with Vite: `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- Images and static assets in `resources/images/` or `public/images/`.
- Use `asset()` helper for public assets, `Vite::asset()` for compiled assets.
