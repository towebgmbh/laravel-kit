<?php

use Towebgmbh\LaravelKit\Hooks\DocsGuard;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-guard-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg($this->dir));
});

function guardPrompt(string $text = 'Do the work'): array
{
    return ['type' => 'user', 'message' => ['role' => 'user', 'content' => $text]];
}

function guardToolUse(string $tool, string $path): array
{
    $key = $tool === 'NotebookEdit' ? 'notebook_path' : 'file_path';

    return ['type' => 'assistant', 'message' => ['role' => 'assistant', 'content' => [
        ['type' => 'text', 'text' => 'Working on it.'],
        ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => $tool, 'input' => [$key => $path]],
    ]]];
}

function guardToolResult(): array
{
    return ['type' => 'user', 'message' => ['role' => 'user', 'content' => [
        ['type' => 'tool_result', 'tool_use_id' => 'toolu_1', 'content' => 'ok'],
    ]]];
}

/** Writes the transcript and returns the hook input for it. */
function guardInput(object $test, array $entries, array $extra = []): string
{
    $transcript = $test->dir.'/transcript.jsonl';
    file_put_contents($transcript, implode("\n", array_map(fn (array $e) => json_encode($e), $entries))."\n");

    return json_encode([
        'session_id' => 'session-1',
        'transcript_path' => $transcript,
        'cwd' => $test->dir,
        'hook_event_name' => 'Stop',
        'stop_hook_active' => false,
        ...$extra,
    ]);
}

/** A turn with the given edits after one prompt; paths are relative to the project. */
function guardTurn(object $test, array $edits): array
{
    $entries = [guardPrompt()];

    foreach ($edits as [$tool, $path]) {
        $entries[] = guardToolUse($tool, $test->dir.'/'.$path);
        $entries[] = guardToolResult();
    }

    return $entries;
}

/** Nothing is tracked except the given paths. */
function guardTracked(array $tracked = []): Closure
{
    return fn (string $cwd, string $path): ?bool => in_array($path, $tracked, true);
}

function expectBlock(?string $output): void
{
    expect($output)->not->toBeNull()
        ->and(json_decode($output, true))->toBe(['decision' => 'block', 'reason' => DocsGuard::REASON]);
}

it('blocks structural changes without a docs edit', function (string $tool, string $path, array $tracked) {
    $guard = new DocsGuard(guardTracked($tracked));

    expectBlock($guard->handle(guardInput($this, guardTurn($this, [[$tool, $path]]))));
})->with([
    'new app file' => ['Write', 'app/Billing/Actions/CreateInvoice.php', []],
    'migration edit' => ['Edit', 'database/migrations/2026_01_01_000000_create_invoices_table.php', ['database/migrations/2026_01_01_000000_create_invoices_table.php']],
    'routes edit' => ['Edit', 'routes/web.php', ['routes/web.php']],
    'new page' => ['Write', 'resources/js/pages/Invoices/Index.vue', []],
    'new view' => ['Write', 'resources/views/invoices/index.blade.php', []],
    'multi edit of a routes file' => ['MultiEdit', 'routes/api.php', ['routes/api.php']],
]);

it('passes an edit of a tracked app file', function () {
    $guard = new DocsGuard(guardTracked(['app/Billing/Actions/CreateInvoice.php']));

    expect($guard->handle(guardInput($this, guardTurn($this, [['Edit', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
});

it('passes edits of tracked frontend files and files outside the structural paths', function () {
    $guard = new DocsGuard(guardTracked(['resources/js/pages/Invoices/Index.vue']));

    expect($guard->handle(guardInput($this, guardTurn($this, [
        ['Edit', 'resources/js/pages/Invoices/Index.vue'],
        ['Write', 'tests/Feature/InvoiceTest.php'],
        ['Write', 'config/services.php'],
    ]))))->toBeNull();
});

it('counts only Write as new outside a git work tree', function () {
    $guard = new DocsGuard(fn (): ?bool => null);

    expectBlock($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]))));
    expect($guard->handle(guardInput($this, guardTurn($this, [['Edit', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
});

it('passes when the turn also changed docs', function () {
    $guard = new DocsGuard(guardTracked());

    expect($guard->handle(guardInput($this, guardTurn($this, [
        ['Write', 'app/Billing/Actions/CreateInvoice.php'],
        ['Write', 'docs/SPECIFICATIONS.md'],
    ]))))->toBeNull();
});

it('passes when the stop hook is already active', function () {
    $guard = new DocsGuard(guardTracked());

    expect($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]), ['stop_hook_active' => true])))->toBeNull();
});

it('passes when the project switched the guard off', function () {
    mkdir($this->dir.'/config');
    file_put_contents($this->dir.'/config/kit.php', "<?php return ['hooks' => ['docs_guard' => false]];\n");
    $guard = new DocsGuard(guardTracked());

    expect($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
});

it('stays on with a config that enables it or cannot be loaded', function (string $config) {
    mkdir($this->dir.'/config');
    file_put_contents($this->dir.'/config/kit.php', $config);
    $guard = new DocsGuard(guardTracked());

    expectBlock($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]))));
})->with([
    'enabled' => "<?php return ['hooks' => ['docs_guard' => true]];\n",
    'throws' => "<?php throw new RuntimeException('broken');\n",
]);

it('ignores code changes of earlier turns', function () {
    $entries = [
        ...guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]),
        ['type' => 'assistant', 'message' => ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => 'Done.']]]],
        guardPrompt('Explain the action'),
        ['type' => 'assistant', 'message' => ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => 'It creates an invoice.']]]],
    ];

    expect((new DocsGuard(guardTracked()))->handle(guardInput($this, $entries)))->toBeNull();
});

it('does not treat tool results, meta messages or broken lines as a new prompt', function () {
    $entries = [
        ...guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]),
        ['type' => 'user', 'isMeta' => true, 'message' => ['role' => 'user', 'content' => 'Reminder']],
        ['type' => 'system', 'content' => 'Something happened'],
    ];
    $input = guardInput($this, $entries);
    file_put_contents($this->dir.'/transcript.jsonl', "not json\n", FILE_APPEND);

    expectBlock((new DocsGuard(guardTracked()))->handle($input));
});

it('reads notebook paths', function () {
    $guard = new DocsGuard(guardTracked());

    expectBlock($guard->handle(guardInput($this, guardTurn($this, [['NotebookEdit', 'routes/notebook.ipynb']]))));
});

it('fails open on bad input', function (string $input) {
    expect((new DocsGuard(guardTracked()))->handle($input))->toBeNull();
})->with([
    'invalid json' => '{not json',
    'missing transcript' => json_encode(['transcript_path' => '/nonexistent/transcript.jsonl', 'stop_hook_active' => false]),
    'no transcript path' => json_encode(['stop_hook_active' => false]),
]);

it('fails open when the git check throws', function () {
    $guard = new DocsGuard(fn (): ?bool => throw new RuntimeException('git failed'));

    expect($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
});

it('asks git whether a file is new', function () {
    exec('git -C '.escapeshellarg($this->dir).' init -q');
    mkdir($this->dir.'/app/Billing/Actions', 0755, true);
    file_put_contents($this->dir.'/app/Billing/Actions/CreateInvoice.php', "<?php\n");
    exec('git -C '.escapeshellarg($this->dir).' add app');
    file_put_contents($this->dir.'/app/Billing/Actions/SendInvoice.php', "<?php\n");
    $guard = new DocsGuard;

    expect($guard->handle(guardInput($this, guardTurn($this, [['Edit', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
    expectBlock($guard->handle(guardInput($this, guardTurn($this, [['Edit', 'app/Billing/Actions/SendInvoice.php']]))));
});

it('falls back to Write outside a git repository', function () {
    $guard = new DocsGuard;

    expect($guard->handle(guardInput($this, guardTurn($this, [['Edit', 'app/Billing/Actions/CreateInvoice.php']]))))->toBeNull();
    expectBlock($guard->handle(guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']]))));
});

it('runs as a binary that always exits 0', function () {
    $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/bin/kit-docs-guard'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], guardInput($this, guardTurn($this, [['Write', 'app/Billing/Actions/CreateInvoice.php']])));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    expect(proc_close($process))->toBe(0);
    expectBlock(trim($output));
});
