#!/usr/bin/env bash
# Laravel Kit docs guard: asks for docs after structural changes. Managed by kit:install and kit:update.
dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"

if [ ! -x "$dir/vendor/bin/kit-docs-guard" ]; then
    exit 0
fi

exec "$dir/vendor/bin/kit-docs-guard"
