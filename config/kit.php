<?php

return [

    // Frontend stack: blade, livewire, inertia-vue, inertia-react or api.
    'stack' => 'blade',

    // Architecture mode: actions, services or migrating.
    'architecture' => 'actions',

    'docs' => [
        // Number of domains from which the docs switch from the flat to the split layout.
        'split_threshold' => 4,
    ],

    'hooks' => [
        // Claude Code Stop hook that asks for docs after structural changes: true or false.
        'docs_guard' => true,
    ],

];
