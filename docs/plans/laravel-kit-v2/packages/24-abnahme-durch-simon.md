<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 24: Abnahme durch Simon

Kind: acceptance · Status: done · Model: opus · Effort: medium · Commits: none · Verdict: pass

## Spec

## Goal

Prepare Simon's acceptance of Laravel Kit v2 and give him a test guide. The package is a Composer library, so there is no Herd site and no browser (D-17); Simon works in a terminal on the task branch's checkout.

## Context to read

- The whole concept, especially Acceptance AC-1 to AC-14.
- Handoffs of packages 17 (smoke) and 22 (final re-review: checks and verdict).
- `README.md`, `scripts/smoke.sh`, `.github/workflows/tests.yml`, `.github/workflows/boost-drift.yml`, `resources/boost/guidelines/core.blade.php`, `resources/boost/skills/kit-spec/FEATURE_TEMPLATE.md`.

Tooling: if `php` or `composer` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. Demo user and login: none (a package, no web app).
2. Demo data: none to seed; `scripts/smoke.sh --keep` builds two fresh Laravel 13 apps in `<work>/fresh` and `<work>/upgrade` (the latter with a generated v1 layout) and prints the work directory `<work>`.
3. Start page: a terminal in the repository checkout of the task branch.
4. Run the checks below yourself first and report each with its exit code; a failing check is a blocking finding naming the AC.
5. Write the test guide for Simon with these steps, in this order, each with what he does and what he sees:
   1. AC-1: `composer install && vendor/bin/pest && vendor/bin/pint --test` -> all tests green, Pint reports no issues; on GitHub the latest run of "tests" on the pull request is green for all matrix jobs and the `smoke` job.
   2. AC-2, AC-9, AC-13: `vendor/bin/pest tests/Feature/Guidelines tests/Feature/Skills` -> green, including `ContextBudgetTest`.
   3. AC-3: `bash scripts/smoke.sh --keep --scenario=fresh` -> `PASS: fresh`; open `<work>/fresh/CLAUDE.md` and `<work>/fresh/AGENTS.md` and see the sections `=== towebgmbh/laravel-kit/core rules ===`, "Stack: Livewire 4 with Flux" and the test line "Follow the Laravel Kit test line:", and no `# Pest` section (the fresh app uses PHPUnit); `ls <work>/fresh/.claude/skills` lists the five `kit-*` skills.
   4. AC-14: in `<work>/fresh/CLAUDE.md` read the size rule (small, medium, large); open `.claude/skills/kit-spec/FEATURE_TEMPLATE.md` and see "Open Questions" and "Acceptance Criteria" with `AC-1`.
   5. AC-4, AC-6: in `<work>/fresh`, look at `.claude/settings.json` (two `Stop` hooks: Pint and the docs guard, no `PostToolUse`) and run `php artisan kit:install --stack=livewire --new --no-interaction` again -> the managed files are `unchanged`, `config/kit.php` and the docs are `exists`. Optional: open Claude Code in `<work>/fresh`, ask it to add a new Action file without docs and see it being sent back once; ask it to change one line in an existing file and see no interruption.
   6. AC-5: edit `.ai/guidelines/enforce-tests.blade.php` in `<work>/fresh` and run `php artisan kit:update` -> a diff and the question whether to replace it; answer no.
   7. AC-8: `php artisan kit:doctor` in `<work>/fresh` -> only `[OK]` lines (the smoke script restored the manifest after its drift check, and Boost is pinned to `Overrides::BOOST_VERSION`); the smoke output already showed `[FAIL] Override drift` after the manifest hash change.
   8. AC-7: `bash scripts/smoke.sh --keep --scenario=upgrade` -> `PASS: upgrade`; open `<work>/upgrade/.ai/kit-upgrade/v1/REPORT.md` (diff of `04-testing.md`), see `.ai/guidelines/project/07-project-rules.md`, and see "This project keeps its Service classes." in `<work>/upgrade/CLAUDE.md`.
   9. AC-10: `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` -> green locally; `.github/workflows/boost-drift.yml` has the triggers `schedule` and `workflow_dispatch`. On GitHub the workflow can be started by hand only after the merge into the default branch.
   10. AC-11: read `README.md` sections "Installation", "Commands", "Updating", "Upgrading from v1".
   11. AC-12: Jarvis checks the branch for customer names (tree, every commit and the commit messages) right before the push and the pull request, and reports the result to Simon. Name it in the guide as Jarvis's step; Simon does nothing here.
   12. Afterwards: delete the work directories (`rm -rf <work>`). Tag `v2.0.0` and Packagist are Simon's own steps (D-17).

## AC-12 and docs/plans

`docs/plans/` is Jarvis's copy of this plan. Jarvis overwrites it before every package, and it quotes earlier review text; Jarvis replaces any customer names in it, across the whole branch history, before the push. Do not run name searches in this package and do not report anything under `docs/plans/` as a finding. The code outside `docs/plans/` was searched clean by packages 18 to 22. Never write a customer name into a command, the report or the guide.

## Acceptance criteria

Serves: AC-1, AC-2, AC-3, AC-4, AC-5, AC-6, AC-7, AC-8, AC-9, AC-10, AC-11, AC-12, AC-13, AC-14

- Verdict `pass` or `blocking` with findings naming the AC, the checks with exit codes, and the test guide. Evidence: the report.

## Tests to write

None.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`
- `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift`
- `bash scripts/smoke.sh`

## Out of scope

- Code changes of any kind.
- Anything under `docs/plans/` (Jarvis's plan copy, see above).
- Tag, release and Packagist registration (D-17).

## Report

Acceptance prepared. Pest 268 passed / 2 skipped, Pint clean, drift tests 2 passed, smoke PASS fresh and upgrade (Laravel 13, Boost 2.10.4). Checked the guide steps by hand in the kept smoke apps: CLAUDE.md/AGENTS.md sections, no "# Pest", the five kit-* skills, the size rule, FEATURE_TEMPLATE with Open Questions and AC-1, settings.json with two Stop hooks and no PostToolUse, second kit:install with unchanged/exists, kit:update with a diff and the local edit kept, kit:doctor all OK, upgrade report, project rule moved, services mode in CLAUDE.md. Also checked the README sections and the workflow triggers. No tracked files changed; the work directory is deleted.

## Handoff

No changes. Code is accepted locally on PHP 8.5 / Laravel 13 / Boost 2.10.4. Open before the push: Jarvis cleans docs/plans and the history of customer names (AC-12) and runs the name search. The GitHub matrix (Laravel 12, Boost 2.10.0, PHP 8.3/8.4), the smoke CI job and boost-drift.yml only run after the push.

## Decisions

- Ran the smoke script as `bash scripts/smoke.sh --keep` with its output in a log file, so I could check the guide steps in the kept apps. Then I deleted the work directory.
- Ran kit:update with --no-interaction instead of answering 'no' interactively: the local edit was kept (skipped) after the diff was shown. The interactive question is covered by UpdateCommandTest.
- Ran no name search and reported nothing under docs/plans, as the spec says; AC-12 is Jarvis's step before the push.
- Ran Pest a second time without a pipe so the exit code is recorded; the result was the same.

## Checks

- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pest --compact --colors=never`: exit 0, 268 passed, 2 skipped (929 assertions)
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pint --test`: exit 0, pint passed
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift --colors=never`: exit 0, 2 passed
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; bash scripts/smoke.sh --keep > /tmp/kit-smoke-acceptance.log 2>&1; echo "exit=$?"; grep -nE 'PASS|FAIL|WARN|Work directory' /tmp/kit-smoke-acceptance.log`: exit 0, exit=0, PASS: fresh, PASS: upgrade

## Test guide

# Abnahme Laravel Kit v2

Kein Login, keine URL und kein Herd: Es ist ein Composer-Paket (D-17). Du arbeitest im Terminal im Checkout des Branches `jarvis/laravel-kit-v2`. Falls `php` oder `composer` fehlen: `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

Der Smoke-Test braucht Netz und Composer. Mit `--keep` gibt er am Ende `Work directory kept: <work>` aus. `<work>` steht in den Schritten unten für diesen Pfad.

## 1. AC-1: Tests und Stil
`composer install && vendor/bin/pest && vendor/bin/pint --test`
-> alle Tests grün (268 passed, 2 skipped), Pint meldet `passed`. Auf GitHub ist nach dem Push der letzte Lauf "tests" im PR grün, für alle Matrix-Jobs (PHP 8.3/8.4, Laravel 12/13, Boost lowest/latest) und für den Job `smoke`.
**Lokal geprüft** (PHP 8.5, Laravel 13, Boost 2.10.4). Die Matrix auf GitHub läuft erst nach dem Push.

## 2. AC-2, AC-9, AC-13: Guidelines, Skills, Kontextbudget
`vendor/bin/pest tests/Feature/Guidelines tests/Feature/Skills`
-> grün, auch `ContextBudgetTest`. **Geprüft** (Teil der ganzen Suite).

## 3. AC-3: frische App
`bash scripts/smoke.sh --keep --scenario=fresh` -> `PASS: fresh`.
Öffne `<work>/fresh/CLAUDE.md` und `<work>/fresh/AGENTS.md`. Dort stehen `=== towebgmbh/laravel-kit/core rules ===`, "Stack: Livewire 4 with Flux" und "Follow the Laravel Kit test line:". Es gibt keinen Abschnitt `# Pest`, weil die frische App PHPUnit nutzt.
`ls <work>/fresh/.claude/skills` zeigt kit-brief, kit-docs-split, kit-onboard, kit-review und kit-spec, daneben Boosts eigene Skills. **Geprüft.**

## 4. AC-14: Grössenregel und Spec-Template
In `<work>/fresh/CLAUDE.md` steht "Classify every piece of work as small, medium or large…" mit den Stufen Small, Medium und Large. In `.claude/skills/kit-spec/FEATURE_TEMPLATE.md` stehen "## Open Questions" und "## Acceptance Criteria" mit `AC-1`. **Geprüft.**

## 5. AC-4, AC-6: Hooks und kein Überschreiben
In `<work>/fresh/.claude/settings.json` stehen zwei `Stop`-Hooks (`vendor/bin/pint --dirty --format agent` und `.claude/hooks/kit-docs-guard.sh`) und kein `PostToolUse`.
`php artisan kit:install --stack=livewire --new --no-interaction` noch einmal ausführen -> die verwalteten Dateien sind `unchanged`, `config/kit.php` und die drei Doku-Dateien `exists`, `pest/core` ist `not needed`. **Geprüft.**
Optional: Claude Code in `<work>/fresh` öffnen und eine neue Action-Datei ohne Doku anlegen lassen. Der Agent wird einmal zurückgeschickt. Ändert er nur eine Zeile in einer bestehenden Datei, läuft er ohne Unterbrechung durch. **Nicht von Hand geprüft**, das deckt `DocsGuardTest` ab.

## 6. AC-5: kit:update
In `<work>/fresh` eine Zeile an `.ai/guidelines/enforce-tests.blade.php` anhängen und `php artisan kit:update` ausführen -> ein Diff (yours/kit) und die Frage, ob die Datei ersetzt werden soll. Mit "no" antworten: Deine Änderung bleibt, danach läuft `boost:update`.
**Geprüft** mit `--no-interaction` (Diff, `skipped`, die Änderung blieb). Die interaktive Frage deckt `UpdateCommandTest` ab.

## 7. AC-8: kit:doctor
`php artisan kit:doctor` in `<work>/fresh` (vorher deine Änderung aus Schritt 6 zurücknehmen) -> nur `[OK]`-Zeilen: Boost 2.10.4, Paket aktiv, Manifest, Config, keine v1-Reste, Override passt. Die Smoke-Ausgabe hat vorher mit einem manipulierten Manifest `[FAIL] Override drift` mit Exit 1 geprüft. **Geprüft.**

## 8. AC-7: Upgrade von v1
`bash scripts/smoke.sh --keep --scenario=upgrade` -> `PASS: upgrade`.
- `<work>/upgrade/.ai/kit-upgrade/v1/REPORT.md` enthält den Diff von `04-testing.md`.
- `.ai/guidelines/project/07-project-rules.md` ist da.
- `<work>/upgrade/CLAUDE.md` enthält "This project keeps its Service classes."
- `laravel-claude-kit/` und die v1-Skills sind weg, `docs/GUIDELINES.md` ist unverändert.

**Geprüft.**

## 9. AC-10: wöchentlicher Drift-Lauf
`KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` -> 2 passed. `.github/workflows/boost-drift.yml` hat `schedule` (montags 06:00) und `workflow_dispatch`. Auf GitHub lässt sich der Lauf erst nach dem Merge in `main` von Hand starten. **Lokal geprüft.**

## 10. AC-11: README
In `README.md` gibt es die Abschnitte "Installation", "Commands", "Updating" und "Upgrading from v1". **Geprüft** (die Abschnitte sind da; den Inhalt liest du selbst).

## 11. AC-12: keine Kundennamen
Das ist Jarvis' Schritt: Direkt vor Push und PR prüft Jarvis den Branch (Dateien, jeden Commit, die Commit-Nachrichten) und bereinigt `docs/plans` samt Historie. Jarvis meldet dir das Ergebnis. Du musst hier nichts tun.

## 12. Aufräumen
`rm -rf <work>`. Den Tag `v2.0.0` und die Registrierung auf Packagist machst du selbst (D-17).

## Grenzen
- Lokal lief nur PHP 8.5, Laravel 13 und Boost 2.10.4. Laravel 12, Boost 2.10.0, PHP 8.3/8.4 und die GitHub-Workflows laufen erst nach dem Push.
- Den Doku-Wächter in einer echten Claude-Code-Sitzung habe ich nicht von Hand geprüft.

## Offene Befunde
Keine blockierenden. Aus dem Review ist ein kleiner Punkt bekannt, aber nicht blockierend: `kit:doctor` empfiehlt Projekten ohne v1-Versionsdatei, eigene `.claude/rules`-Dateien zu entfernen ("Remove them"). Das ist nur eine Warnung mit Exit 0.
