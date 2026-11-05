@switch(config('kit.stack'))
@case('blade')
@scoped(['resources/views/**', 'resources/js/**', 'resources/css/**'])
## Stack: Blade with Alpine

- Pages are Blade views in `resources/views/{domain}/`; shared UI is anonymous Blade components in `resources/views/components/`.
- Declare every component's inputs with `@props` at the top of the file; no logic beyond presentation.
- Use Alpine for client-side behaviour that needs no server round trip. Keep the Alpine code in the view or a module in `resources/js/`.
- Style with Tailwind 4 utility classes; extract repeated markup into components, not into custom CSS.
- Assets go through Vite: entry points in `resources/js/app.js` and `resources/css/app.css`.
@endscoped
@break
@case('livewire')
@scoped(['app/Livewire/**', 'resources/views/**'])
## Stack: Livewire 4 with Flux

- Components live in `app/Livewire/{Domain}/`, their views in `resources/views/livewire/{domain}/`. Do not use Volt.
- Follow the Livewire 4 component format that Boost's Livewire guidelines describe.
- A component validates input, calls Actions and sets state; business logic stays in the domain.
- Build the interface with Flux UI components; wrap repeated Flux markup in Blade components in `resources/views/components/`.
- Use Alpine only for interactions that need no server round trip.
@endscoped
@break
@case('inertia-vue')
@scoped(['resources/js/**'])
## Stack: Inertia 3 with Vue

- Pages are in `resources/js/pages/{Domain}/`, shared components in `resources/js/components/`, composables in `resources/js/composables/`, layouts in `resources/js/layouts/`.
- Write every component with `<script setup lang="ts">`. Type page props and shared data in `resources/js/types/`.
- Reference routes only through the Wayfinder imports; never hardcode URLs.
- Controllers return `Inertia::render()` with the data of a DTO or an API Resource, not Eloquent models.
- Style with Tailwind utility classes.
@endscoped
@break
@case('inertia-react')
@scoped(['resources/js/**'])
## Stack: Inertia 3 with React

- Pages are in `resources/js/pages/{Domain}/`, shared components in `resources/js/components/`, hooks in `resources/js/hooks/`, layouts in `resources/js/layouts/`.
- Write every component as a TypeScript function component in a `.tsx` file. Type page props and shared data in `resources/js/types/`.
- Reference routes only through the Wayfinder imports; never hardcode URLs.
- Controllers return `Inertia::render()` with the data of a DTO or an API Resource, not Eloquent models.
- Style with Tailwind utility classes.
@endscoped
@break
@case('api')
@scoped(['routes/api.php', 'app/Http/**'])
## Stack: API only

- Return API Resources from controllers, never Eloquent models or raw arrays.
- Authenticate with Sanctum tokens; authorize with Policies.
- Version the routes under `/api/v1` in `routes/api.php`; a breaking change gets a new version prefix and a new Resource namespace.
- Use one error format for all failures (validation, authorization, not found, server): the same JSON keys everywhere, documented in `docs/`.
- Form Requests validate input; the thin controller calls an Action and returns the Resource.
@endscoped
@break
@endswitch
