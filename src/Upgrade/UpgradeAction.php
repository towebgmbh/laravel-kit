<?php

namespace Towebgmbh\LaravelKit\Upgrade;

/** One step of the v1 upgrade: what happens to a project path and why. */
final readonly class UpgradeAction
{
    public const DELETE = 'delete';

    public const ARCHIVE = 'archive';

    public const MOVE = 'move';

    public const REMOVE_DIR = 'remove-dir';

    public const KEEP = 'keep';

    public function __construct(
        public string $type,
        public string $path,
        public ?string $target,
        public string $reason,
    ) {}
}
