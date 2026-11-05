<?php

use Towebgmbh\LaravelKit\Support\ProjectDetector;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-project-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg($this->dir));
});

function projectMigrations(string $dir, array $files): void
{
    @mkdir($dir.'/database/migrations', 0777, true);

    foreach ($files as $file) {
        file_put_contents($dir.'/database/migrations/'.$file, '<?php');
    }
}

it('treats a project without migrations folder as new', function () {
    expect((new ProjectDetector)->isNew($this->dir))->toBeTrue();
});

it('treats skeleton and starter kit migrations as new', function () {
    projectMigrations($this->dir, [
        '0001_01_01_000000_create_users_table.php',
        '0001_01_01_000001_create_cache_table.php',
        '2025_08_14_170933_add_two_factor_columns_to_users_table.php',
    ]);

    expect((new ProjectDetector)->isNew($this->dir))->toBeTrue();
});

it('treats any own migration as existing', function () {
    projectMigrations($this->dir, [
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_000000_create_invoices_table.php',
    ]);

    expect((new ProjectDetector)->isNew($this->dir))->toBeFalse();
});
