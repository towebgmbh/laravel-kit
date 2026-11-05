#!/usr/bin/env bash
# End-to-end smoke test: installs the kit into fresh Laravel 13 apps (path repository)
# and checks the install and the v1 upgrade. Needs network access and Composer.
#
# Usage: bash scripts/smoke.sh [--keep] [--scenario=fresh|upgrade|all]
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
KEEP=0
SCENARIO=all

for arg in "$@"; do
    case "$arg" in
        --keep) KEEP=1 ;;
        --scenario=fresh|--scenario=upgrade|--scenario=all) SCENARIO="${arg#--scenario=}" ;;
        *) echo "Unknown option: $arg" >&2; exit 2 ;;
    esac
done

WORK="$(mktemp -d)"
cleanup() {
    if [ "$KEEP" = 1 ]; then
        echo "Work directory kept: $WORK"
    else
        rm -rf "$WORK"
    fi
}
trap cleanup EXIT

BOOST_VERSION="$(sed -n "s/.*const BOOST_VERSION = '\([^']*\)'.*/\1/p" "$REPO/src/Overrides.php")"
[ -n "$BOOST_VERSION" ] || { echo "FAIL: cannot read BOOST_VERSION"; exit 1; }

fail() { echo "FAIL: $*"; exit 1; }
contains() { grep -qF -- "$2" "$1" || fail "$1 does not contain: $2"; }
lacks() { if grep -qF -- "$2" "$1"; then fail "$1 contains: $2"; fi; }
exists() { [ -e "$1" ] || fail "missing: $1"; }
absent() { [ ! -e "$1" ] || fail "should not exist: $1"; }

# The sentences come from the PHP class, so the list cannot drift.
CONTRADICTIONS=()
while IFS= read -r line; do CONTRADICTIONS+=("$line"); done < <(
    php -r 'require $argv[1]; echo implode("\n", Tests\Support\Contradictions::SENTENCES), "\n";' "$REPO/tests/Support/Contradictions.php"
)
[ "${#CONTRADICTIONS[@]}" -gt 0 ] || { echo "FAIL: cannot read contradictions"; exit 1; }

make_app() {
    local dir="$1"
    composer create-project laravel/laravel "$dir" --prefer-dist --no-interaction
    cd "$dir"
    [ -f CLAUDE.md ] || : > CLAUDE.md
    composer config repositories.kit "{\"type\":\"path\",\"url\":\"$REPO\",\"options\":{\"symlink\":true}}"
    composer require --dev "laravel/boost:$BOOST_VERSION" "towebgmbh/laravel-kit:*@dev" --no-interaction -W
    echo '{"agents":["claude_code","codex"],"guidelines":true,"skills":["laravel-best-practices","testing-best-practices"]}' > boost.json
    php artisan --version | grep -q '^Laravel Framework 13' || fail "not a Laravel 13 app"
}

scenario_fresh() {
    make_app "$WORK/fresh"
    cd "$WORK/fresh"

    php artisan kit:install --stack=livewire --new --no-interaction
    contains CLAUDE.md 'Stack: Livewire 4 with Flux'
    lacks CLAUDE.md 'Stack: Blade with Alpine'

    php artisan boost:update --no-discover

    local file sentence
    for file in CLAUDE.md AGENTS.md; do
        contains "$file" 'towebgmbh/laravel-kit/core rules'
        contains "$file" 'towebgmbh/laravel-kit/stack rules'
        contains "$file" 'Stack: Livewire 4 with Flux'
        contains "$file" 'Follow the Laravel Kit test line:'
        for sentence in "${CONTRADICTIONS[@]}"; do lacks "$file" "$sentence"; done
        if grep -q '^# Pest' "$file"; then fail "$file contains a Pest heading"; fi
        lacks "$file" 'This project uses Pest'
    done

    exists .ai/guidelines/enforce-tests.blade.php
    absent .ai/guidelines/pest/core.blade.php

    local skill
    for skill in kit-onboard kit-spec kit-review kit-brief kit-docs-split; do exists ".claude/skills/$skill/SKILL.md"; done
    absent .claude/skills/kit-test
    absent .claude/skills/kit-migrate

    # The only JSON file in .claude of the fresh app holds the Claude hooks.
    local hooks_file
    hooks_file="$(ls .claude/*.json)"
    contains "$hooks_file" 'kit-docs-guard.sh'
    contains "$hooks_file" 'pint --dirty --format agent'
    php -r '$s = json_decode(file_get_contents($argv[1]), true);
        if (isset($s["hooks"]["PostToolUse"]) || count($s["hooks"]["Stop"] ?? []) < 1) { exit(1); }' "$hooks_file" \
        || fail "$hooks_file hooks are wrong"

    php artisan kit:doctor || fail "kit:doctor should exit 0"

    cp .ai/kit-manifest.json "$WORK/manifest.bak"
    php -r '$m = json_decode(file_get_contents(".ai/kit-manifest.json"), true);
        $k = array_key_first($m["overrides"]);
        $m["overrides"][$k]["boost_sha256"] = str_repeat("0", 64);
        file_put_contents(".ai/kit-manifest.json", json_encode($m, JSON_PRETTY_PRINT));'
    local out status=0
    out="$(php artisan kit:doctor)" || status=$?
    cp "$WORK/manifest.bak" .ai/kit-manifest.json
    [ "$status" = 1 ] || fail "kit:doctor should exit 1 on drift (got $status)"
    grep -qF '[FAIL] Override drift' <<<"$out" || fail "kit:doctor did not print [FAIL] Override drift"
    php artisan kit:doctor || fail "kit:doctor should exit 0 after restoring the manifest"

    echo "PASS: fresh"
}

tree_sums() {
    find . \( -path ./vendor -o -path ./node_modules -o -path ./storage \) -prune -o -type f -print0 \
        | sort -z | xargs -0 shasum
}

scenario_upgrade() {
    make_app "$WORK/upgrade"
    cd "$WORK/upgrade"

    local t="$REPO/resources/legacy/v1/templates"
    mkdir -p .claude/rules .claude/skills .claude/templates laravel-claude-kit docs
    local rule skill
    for rule in 01-architecture 02-laravel-conventions 05-quality 06-workflow; do
        cp "$t/.claude/rules/$rule.md" .claude/rules/
    done
    cp "$t/.claude/rules/variants/03-frontend-livewire.md" .claude/rules/03-frontend.md
    cp "$t/.claude/rules/04-testing.md" .claude/rules/04-testing.md
    printf '%s\n' '- Use datasets for currency rounding cases.' >> .claude/rules/04-testing.md
    printf '# Billing Rules\n\n- Store amounts as integer cents.\n' > .claude/rules/07-project-rules.md
    for skill in onboard migrate review test brief; do
        mkdir -p ".claude/skills/$skill"
        cp "$t/.claude/skills/$skill/SKILL.md" ".claude/skills/$skill/"
    done
    cp "$t/.claude/templates/FEATURE_TEMPLATE.md" .claude/templates/
    echo '1.0.0' > .claude/.kit-version
    cp "$REPO/resources/legacy/v1/install.sh" laravel-claude-kit/install.sh
    cp "$t/docs/GUIDELINES.md" docs/GUIDELINES.md
    php -r '$f = "docs/GUIDELINES.md"; $c = file_get_contents($f);
        $hint = "> If this project intentionally differs from `.claude/rules/`, document it here so Claude follows the project'"'"'s conventions instead.";
        $n = str_replace($hint."\n", $hint."\n\n- Uses Service classes instead of Actions for legacy reasons - do NOT refactor to Actions\n", $c);
        if ($n === $c) { exit(1); } file_put_contents($f, $n);' || fail "cannot prepare docs/GUIDELINES.md"

    tree_sums > "$WORK/before.sums"
    local out
    out="$(php artisan kit:upgrade --dry-run)"
    echo "$out"
    grep -qF 'archive' <<<"$out" || fail "dry run does not mention archive"
    grep -qF '.claude/rules/04-testing.md' <<<"$out" || fail "dry run does not mention 04-testing.md"
    grep -qF 'move' <<<"$out" || fail "dry run does not mention move"
    grep -qF '07-project-rules.md' <<<"$out" || fail "dry run does not mention 07-project-rules.md"
    tree_sums > "$WORK/after.sums"
    diff -q "$WORK/before.sums" "$WORK/after.sums" >/dev/null || fail "dry run changed files"

    cp docs/GUIDELINES.md "$WORK/GUIDELINES.bak"
    php artisan kit:upgrade --no-interaction

    absent .claude/rules/01-architecture.md
    absent laravel-claude-kit
    exists .ai/kit-upgrade/v1/.claude/rules/04-testing.md
    exists .ai/kit-upgrade/v1/REPORT.md
    exists .ai/guidelines/project/07-project-rules.md
    cmp -s docs/GUIDELINES.md "$WORK/GUIDELINES.bak" || fail "docs/GUIDELINES.md changed"
    contains config/kit.php "'architecture' => 'services'"
    contains CLAUDE.md 'towebgmbh/laravel-kit/core rules'
    contains CLAUDE.md 'This project keeps its Service classes.'

    echo "PASS: upgrade"
}

case "$SCENARIO" in
    fresh) scenario_fresh ;;
    upgrade) scenario_upgrade ;;
    all) scenario_fresh; scenario_upgrade ;;
esac
