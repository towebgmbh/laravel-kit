<?php

namespace Tests\Support;

/** Sentences from Boost or kit v1 that contradict the kit and must never be rendered. */
final class Contradictions
{
    public const SENTENCES = [
        'Every Action gets at least one unit test',
        'ask the user to run the complete suite',
        'Run `php artisan test` after each phase',
        'Each Action has exactly one public method: `execute()`',
        'AuthServiceProvider',
    ];
}
