<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 13: Review-Befunde von Paket 10 beheben

Kind: fix · Status: done · Model: default · Effort: default · Commits: none · Verdict: –

## Spec

The review of package 12 found blocking issues. Address each finding below. In your handoff, name every finding you did not address and why.

## Findings

1. [BLOCKING, AC-12 / D-1, data exposure, carried over from package 10 finding 1] The tracked plan specs hold the full list of customer and project names in the AC-12 search command. Files and lines:
   - docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md:50-51, 82-83, 87
   - docs/plans/laravel-kit-v2/packages/11-review-befunde-von-paket-10-beheben.md:13
   - docs/plans/laravel-kit-v2/packages/12-paket-10-erneut-reviewen.md:50-51
   - docs/plans/laravel-kit-v2/packages/15-rauchtest-an-frischer-laravel-13-app-install-und-v.md:52-53
   - docs/plans/laravel-kit-v2/packages/16-schluss-review-gegen-alle-abnahmekriterien.md:52-53

   `git grep -n -i -E "<pattern>"` exits 0 with 11 hits, all in these files. The commits "docs(plan): update laravel-kit-v2" commit them to the branch of the public repo, so a push would publish the names. Outside docs/plans, `git grep` finds nothing, and commit messages are clean.

   Fix (Jarvis or the owner, not a package agent, because docs/plans must not be edited by packages):
   - Keep the pattern out of every tracked file, for example in an untracked file outside the worktree or in Jarvis-only context.
   - Remove docs/plans from the branch or strip the list from it.
   - Rewrite or squash the branch history before the first push.

   Afterwards both `git grep` and `git log -p | grep -i -E "<pattern>"` must exit 1.

Non-blocking findings (no change needed for the verdict):
2. tests/Feature/Commands/UpdateCommandTest.php:93-94: `expectsOutputToContain('+++ … (kit)')` proves the diff is printed in the interactive run. Laravel's PendingCommand does not enforce order against `expectsConfirmation`, so a regression that prints the diff after the question would still pass. The code prints it first.
3. src/Hooks/DocsGuard.php:248-254: `git log --since` uses the committer date. When the agent rebases or amends during a turn, files from commits rewritten in the turn count as new, and the guard can block once. This fails safe (one extra block per turn, stop_hook_active lets the agent through) and is acceptable.

## Report

Der einzige blockierende Befund (AC-12: Liste der Kunden- und Projektnamen in den getrackten Plan-Specs unter docs/plans und damit in der Branch-Historie) lässt sich in einem Paket nicht beheben. docs/plans darf kein Paket editieren, und eine Historie darf ich nicht umschreiben. Ich habe deshalb keinen Code geändert. Pest läuft mit 196 passed, Pint ist sauber. Ausserhalb von docs/plans gibt es keinen Namenstreffer. Der einzige Treffer ist 'toweb GmbH' als Copyright-Inhaber in LICENSE:3, der Herausgeber des Pakets towebgmbh/laravel-kit.

## Handoff

Marked done by the owner

## Decisions

- Befund 1 (AC-12) nicht behoben: docs/plans darf kein Paket editieren und Historie umschreiben ist nicht Aufgabe eines Pakets; braucht Jarvis oder den Owner.
- Befund 2 nicht behoben: PendingCommand erzwingt keine Reihenfolge, ein Test dafür wäre nicht aussagekräftig; der Code gibt den Diff vor der Frage aus.
- Befund 3 nicht geändert: laut Review fail-safe und akzeptabel.
- Kein Commit in diesem Paket, weil es nichts zu ändern gab.
- LICENSE enthält 'toweb GmbH' als Copyright-Inhaber, das ist der Herausgeber des Pakets (Vendor towebgmbh) und kein Kundenname.

## Checks

- `vendor/bin/pest --compact`: exit 0, 196 passed (688 assertions)
- `vendor/bin/pint --test`: exit 0, passed
