# Frontend Conventions — Inertia + Vue 3

## Inertia.js
- Pages live in `resources/js/Pages/{Domain}/` mirroring backend domains.
- Use `<Link>` component for navigation, never raw `<a>` tags for internal routes.
- Use `router.visit()`, `router.post()` etc. for programmatic navigation.
- Pass data from Controllers via `Inertia::render('Domain/PageName', [...])`.
- Use shared data (`HandleInertiaRequests` middleware) for auth user, flash messages, app config.
- Preserve scroll position with `preserveScroll` where appropriate.

## Vue 3
- Use `<script setup>` syntax exclusively. No Options API.
- Single File Components (SFC) with `<script setup>`, `<template>`, `<style scoped>` order.
- Use `defineProps()` and `defineEmits()` with TypeScript-style type declarations.
- Composables go in `resources/js/Composables/` with `use` prefix (`useFilters`, `useVehicleForm`).
- Extract reusable logic into composables early. If two components share logic, extract.
- Use `computed()` over methods for derived state. Use `watch()` sparingly.

## Component Organization
- Shared/generic components: `resources/js/Components/` (Button, Modal, DataTable, etc.).
- Domain-specific components: `resources/js/Pages/{Domain}/Partials/` or `Components/`.
- Layout components: `resources/js/Layouts/`.
- Name components PascalCase: `VehicleCard.vue`, `ReviewSummary.vue`.

## Tailwind CSS
- Use Tailwind utility classes directly. No custom CSS unless absolutely necessary.
- Extract repeated patterns into Vue components, not into @apply directives.
- Use consistent spacing scale (p-4, p-6, p-8 — not arbitrary values).
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints.

## Forms
- Use `useForm()` from `@inertiajs/vue3` for all form submissions.
- Display validation errors inline with `form.errors.fieldName`.
- Show loading state with `form.processing`.
- Use `form.reset()` and `form.clearErrors()` appropriately.

## TypeScript
- Use TypeScript (`.vue` with `<script setup lang="ts">`) when the project enables it.
- Define interfaces for page props and complex data structures.
- Shared types go in `resources/js/Types/`.
