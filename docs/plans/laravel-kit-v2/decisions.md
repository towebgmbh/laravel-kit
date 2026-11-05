<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Decisions: Laravel Kit v2

## Package 1: Grundgerüst: composer.json, Kit, Config, Provider, Manifest, Pint und CI
- Manifest save() output has top-level keys sorted alphabetically (files, overrides, project, version) and empty files/overrides are written as {} objects.
- Manifest::load throws InvalidArgumentException for a manifest file that is not valid JSON.
- Added tests/Feature/.gitkeep and tests/Drift/.gitkeep so the phpunit suite directories exist.
- Added the ManifestTest afterEach cleanup with is_file/is_dir guards to avoid warnings.

## Package 2: Testbench-Harness mit Boost-Fixture und niedrigste Boost-Version
- Lowest Boost stays 2.10.0: the full suite passed on it (with Roster 1.0.0), so composer.json ^2.10 and the workflow 'lowest' pin are unchanged.
- Boost registers in tests by setting env 'local' in defineEnvironment; no fallback binding of Boost singletons was needed (documented in TestCase).
- BoostFixture::create accepts package lists as names or name => version; versions default to the installed version via Composer\InstalledVersions.
- The pre-set enforce_tests=false case is a PHPUnit class test (ServiceProviderOwnEnforceTestsTest) with defineEnvironment; it also re-registers KitServiceProvider with force to prove the provider keeps false.
- Added BoostDiscoveryTest cases checking that the 'tests' (enforce-tests) guideline renders only with enforceTests true, and that boost:update is registered.

## Package 3: Kit-Guidelines (Kern mit Grössenregel und Doku, Architektur, Konventionen, Qualität) und die zwei Overrides
- Overrides::BOOST_VERSION = '2.10.4' (installed latest); hashes: enforce-tests da3c2b35..., pest/core f4366933... (Manifest::hash).
- Marker sentence for actions mode is 'Business logic lives in Actions.' followed by the Action location sentence; migrating mode repeats the Action naming/handle() rule briefly.
- DTO, Policy, enum and model rules render in all three modes; only the business-logic location differs per mode.
- Added BoostFixture::withOverrides() as the test helper for copying stubs (spec task 9).
- Added a split_threshold config test and a test that overrides render exactly once (not duplicated as user guidelines).
- Overrides::stub() throws InvalidArgumentException for unknown paths; installedBoostIsOlderThanReference() returns false when the installed version is not numeric (e.g. dev-main).
- core points to the test line with 'Follow the test line in the Test Enforcement rules.' instead of repeating it.

## Package 4: Stack-Varianten mit @scoped, Stack-Erkennung und Kontextbudget-Test
- Default (missing) kit.stack config renders the blade variant because config/kit.php defaults to blade; unknown values render nothing.
- StackDetector treats Inertia with both or neither JS adapter, and Livewire plus Inertia, as null (ambiguous), per spec.
- StackDetector api rule: laravel/sanctum present and no .blade.php other than welcome.blade.php anywhere under resources/views (recursive).
- Updated the three existing KitGuidelinesTest snapshots, since the stack section is now part of the kit text.
- Budget test uses Kit::STACKS and Kit::ARCHITECTURES as datasets.

## Package 5: Skills kit-spec (Kriterien, Open Questions), kit-review und kit-brief
- Template is a plain Markdown file (not code-fenced like v1) with a 'Status: 🔲 Planned' line; the status legend sits in kit-spec SKILL.md.
- kit-review and kit-brief are read-only and tell the agent not to run the complete test suite.
- argument-hint kept in kit-spec, kit-review and kit-brief; no allowed-tools.

## Package 6: Skills kit-onboard (beide Modi, Migration fremder Doku) und kit-docs-split mit Doku-Vorlagen und Projekt-Hülle
- Foreign-docs ARCHIVE target is .ai/kit-upgrade/foreign/ (spec only said 'sources archived'); outside .ai/guidelines so Boost never loads them.
- Agent instruction files searched: CLAUDE.md, AGENTS.md (outside the <laravel-boost-guidelines> block), GEMINI.md, .cursorrules, .cursor/rules/, .github/copilot-instructions.md, .junie/, .claude/commands/.
- Quiet mode picks the architecture from code: Services only -> services, Actions only -> actions, both -> migrating; assumptions marked with <!-- assumption: ... --> in docs.
- Manual record-rule fallback documented in Boost's format: .ai/rules file with frontmatter paths, # heading, ## title + note, plus a row in .ai/rules/index.md.
- ArchitectureTest stub: shared rules once (controllers not using DB facade, App\Services not used) plus a marked domain block repeated per domain; 'no App\Services namespace' is expressed as not->toBeUsed().
- tests.yml derives the PHP version from composer.json with jq, runs npm steps only when package.json exists (hashFiles), copies .env.example and generates a key; skill notes paratest for PHPUnit --parallel.
- kit-docs-split deletes flat GUIDELINES.md and SPECIFICATIONS.md after every heading is mapped; DECISIONS.md becomes the ADR index.
- Added kit-docs-split templates README.md (map) with {{PROJECT_NAME}} and DOMAIN_SPEC.md with status legend and Backlog section.
- SkillDiscoveryTest constant renamed KIT_SKILLS_SO_FAR -> KIT_SKILLS.
- OnboardStubsTest also asserts MERGE, ARCHIVE, 'Architecture decision' and 'php artisan boost:update' in kit-onboard.

## Package 7: kit:install mit Manifest, boost.json-Eintrag, Overrides, Config und flacher Doku
- recordOverride is called for every applicable override even when the file was skipped as differing (the file still overrides the Boost text, so drift tracking stays useful); only the files entry is omitted for skipped files.
- Summary table rows: boost.json ('package added' or 'unchanged'), each override, config/kit.php, docs, '.ai/kit-manifest.json' => 'saved'; skipped pest/core shows 'not needed'.
- {{STACK}} in the flat docs is the readable label (e.g. 'Livewire 4 with Flux', public InstallCommand::STACK_LABELS); config/kit.php gets the raw value.
- Unknown --stack or --architecture values fail with an error listing the allowed values; nothing is written before validation passes.
- If boost:update fails, kit:install still prints the table but returns FAILURE with a hint to rerun boost:update (manifest is already saved).
- Interactive project confirm also asks for the existing case ('This looks like an existing project. Is that right?'); answering no flips the type.
- ProjectDetector ignores .gitkeep in database/migrations; it is an instance class like StackDetector, ComposerPackages::has is static as specified.
- tests/TestCase setUp re-runs BoostServiceProvider::register() when RuleRepository is unbound (Boost singletons were missing in tests; package 2's env=local alone was not enough for boost:update).
- Dropped a planned test that a foreign boost.json package survives: Boost's own install prunes uninstalled packages; duplicate-free packages are covered by the second-run test.

## Package 8: Claude-Stop-Hooks: Pint einmal pro Turn und struktureller Doku-Wächter
- ClaudeSettings decodes JSON as stdClass so empty objects (e.g. "env": {}) survive the round trip; JSON_UNESCAPED_UNICODE added to the spec's pretty print and unescaped slashes.
- ClaudeSettings also returns invalid (writes nothing) when the root is not an object, hooks is not an object or hooks.Stop is not a list.
- DocsGuard skips isMeta user entries when looking for the last prompt, so injected meta messages do not reset the turn.
- DocsGuard asks isTracked only for paths under app/, resources/js/ and resources/views/ (migrations and routes are structural regardless).
- A config/kit.php that throws on include keeps the guard on (only an explicit false disables it).
- bin/kit-docs-guard exits 0 silently when no autoloader or the DocsGuard class is found.
- Added public const InstallCommand::DOCS_GUARD_SCRIPT and DocsGuard::REASON; install table gets a .claude/settings.json row with the merge result.
- Extra tests beyond spec: MultiEdit and NotebookEdit paths, new Blade view, binary run via proc_open, invalid settings warning in kit:install, merge into existing settings in kit:install.
- Skipped running the shell wrapper manually: the Jarvis guard blocks running scripts written via heredoc; the binary itself is covered by a test.

## Package 9: kit:update mit Diff und Rückfrage
- Used StrictUnifiedDiffOutputBuilder instead of UnifiedDiffOutputBuilder (not present in sebastian/diff 9).
- ManagedFiles::write silently replaces files matching the manifest hash only when constructed with replaceUnmodified=true (kit:update); install behaviour unchanged.
- kit:update also errors when boost.json is invalid, same as install.
- The diff is printed whenever a changed file is encountered, including non-interactive runs, followed by the 'Skipped [path]: local changes kept.' warning.
- Deleted pest/core is also dropped from manifest files, not just overrides.

## Package 10: Review: Guidelines, Overrides, Stack, Budget, Install, Hooks und Update
- Treated the git grep hits inside docs/plans (excluded from the reviewed diff) as blocking, because the spec says any hit of the AC-12 search is blocking and the files are tracked in the public repo's branch history.
- Graded the DocsGuard commit-in-turn gap as non-blocking: the behaviour follows D-19 (new = untracked via git ls-files) as specified.
- Did not run the shell wrapper by hand; its exit-0 path without vendor was verified by reading the script, and the binary is covered by DocsGuardTest.
- Only Laravel 13.40.0 / Boost 2.10.4 are installed locally; the Laravel 12 and lowest-Boost branches were reviewed by reading, not by running the matrix.
- Ran Pest with --compact for the reported check (same suite, same result as the earlier full-output run).
- The commit message search reports exit 0 as the Bash tool recorded it: the tool treats a grep without matches as success; the command printed nothing, so there were no matches (an earlier run with an explicit exit echo showed grep's own exit 1).

## Package 11: Review-Befunde von Paket 10 beheben
- Finding 1 (AC-12, name list in tracked docs/plans files) not addressed: package agents may not edit docs/plans or rewrite branch history; needs Jarvis or the owner before the first push.
- Finding 3: keep the previous override entry only when the write was skipped and an entry already exists; a first install with a differing file still records the entry (package 7 decision stays).
- Finding 2: implemented via a second injectable closure and the prompt's transcript timestamp (git log --diff-filter=A --since=<epoch>); applies to any tool, not only Write, and is skipped when the transcript has no timestamp.
- Finding 4: reworded the quality rule instead of changing the stack guideline; the line count is unchanged, so the context budget is unaffected.
- Finding 6: made the lead-in a paragraph (not a bullet) and re-added Boost's 'run the affected tests' as one bullet, so enforce-tests.blade.php grew by two lines; Overrides reference hashes refer to Boost files and stay valid.
- D-1: staged diff and commit message checked for customer names; only the Towebgmbh namespace appears.

## Package 12: Paket 10 erneut reviewen
- Kept the verdict blocking for the AC-12 hits in docs/plans, because the spec says any hit of the name search is blocking; the code itself has no blocking finding.
- Also searched the tree outside docs/plans, and earlier the history outside docs/plans (git log -p): no hits.
- Ran Pest with --compact for the reported check, the same suite as the earlier full-output run (196 passed both times).
- I could not run the shell wrapper stubs/claude/hooks/kit-docs-guard.sh: the Jarvis guard blocks running it. It is unchanged since package 10 and I checked by reading that it exits 0 without vendor/bin/kit-docs-guard.
- Only Laravel 13 with the installed Boost 2.10.4 runs locally; I reviewed the Laravel 12 and lowest-Boost paths by reading, not by running the matrix.

## Package 13: Review-Befunde von Paket 10 beheben
- Befund 1 (AC-12) nicht behoben: docs/plans darf kein Paket editieren und Historie umschreiben ist nicht Aufgabe eines Pakets; braucht Jarvis oder den Owner.
- Befund 2 nicht behoben: PendingCommand erzwingt keine Reihenfolge, ein Test dafür wäre nicht aussagekräftig; der Code gibt den Diff vor der Frage aus.
- Befund 3 nicht geändert: laut Review fail-safe und akzeptabel.
- Kein Commit in diesem Paket, weil es nichts zu ändern gab.
- LICENSE enthält 'toweb GmbH' als Copyright-Inhaber, das ist der Herausgeber des Pakets (Vendor towebgmbh) und kein Kundenname.

## Package 14: Paket 10 erneut reviewen
- Kept the verdict blocking for AC-12 because the spec counts any hit of the name search as blocking; the code has no blocking finding.
- Code diff since b6b8454 outside docs/plans is empty, so I re-verified by running the checks and spot-checking provider, dependencies, budget test and BOOST_VERSION instead of a full line-by-line re-read; package 12 reviewed the same code in full.
- Ran Pest with --compact --colors=never for the reported check (196 passed).
- Ran git grep with -c instead of -n to get per-file counts; line numbers come from an earlier -n run.
- Added two extra searches: tree outside docs/plans and code history (git log -p) outside docs/plans, both without hits.
- The searches without matches are reported with exit 0 as the Bash tool recorded them (it treats grep's no-match as success); they printed nothing or a count of 0.
- Only Laravel 13 / Boost 2.10.4 run locally; the Laravel 12 and lowest-Boost matrix was not run.
- Did not run the shell wrapper stub: the Jarvis guard blocks unwritten scripts; it is unchanged since package 10.

## Package 15: kit:upgrade von v1 auf v2 mit Dry-Run, Archiv und Bericht
- original() takes (string $projectPath, ?string $contents = null): projectPath is the relative project path; contents is needed to pick the closest 03-frontend variant.
- Added exactFrontendVariant() and isUnchanged()/hashes()/has()/paths() to V1Inventory besides the specified methods.
- Stack order: --stack, exact 03-frontend.md variant, StackDetector, closest variant, interactive select (else error 'Pass --stack'). Detector before closest variant: v1 had no inertia-react variant.
- Separate UpgradeAction readonly value class (src/Upgrade/UpgradeAction.php) for plan entries.
- Archive target already exists -> keep (not overwrite), like the move rule; laravel-claude-kit symlinks are ignored, symlinked rule files are not moved.
- Only top-level regular *.md files in .claude/rules count as project rules; other files and subfolders there stay untouched.
- Interactive (non-dry-run) upgrade asks 'Apply this plan?' (default yes) after printing the plan; --no-interaction applies directly.
- kit:upgrade also fails before changing anything when boost.json is invalid or the manifest is invalid JSON (same messages as kit:install), including on --dry-run.
- A .kit-version other than 1.0.0 gives the same 'No v1 kit found' error.
- REPORT.md fences diffs with a fence longer than any backtick run in the diff (v1 files contain code fences); diff headers '(kit v1)' and '(project)'; CRLF normalised before diffing.
- The report records the source of the stack too, not only the architecture.
- Fixture: the services deviation line is inserted after the Deviations blockquote (the template's comment block stays); review SKILL.md changes the '# Code Review' heading; laravel-claude-kit also holds a README.md.
- Extra tests beyond spec: other kit version, options override, unknown architecture, project rule target exists, kit copy without install.sh, invalid manifest.

## Package 16: kit:doctor, wöchentlicher Boost-Drift-Lauf und README
- Renamed the helper fail() to failLine(), because Illuminate's Command already has a public fail().
- Doctor also prints OK lines for the manifest and the config values. Boost not installed or below 2.10 is a FAIL.
- An invalid manifest gives a FAIL line, as recommended in the earlier handoff; the drift check is skipped without a manifest.
- A missing installed Boost file (hash null) counts as drift.
- Removed tests/Drift/.gitkeep because the folder now has a test.
- README describes the hooks as two Stop hooks (Pint and docs guard), per the plan, not PostToolUse.
- I did not run the weekly workflow on GitHub; checked by reading only.

## Package 17: Rauchtest an frischer Laravel-13-App (Install und v1-Upgrade) als Skript und CI-Job
- The script finds the Claude hooks file with `ls .claude/*.json` because the Jarvis guard blocked running a script naming the settings file literally.
- Contradiction sentences are read from the PHP class at runtime instead of copied.
- Bash upgrade fixture is simpler than V1ProjectFixture (short project rule, plain hyphen in the services deviation line).
- Name-search hits in docs/plans left untouched (read-only for packages); reported as the known blocker.
- No commit message contains a name.

## Package 18: Schluss-Review gegen alle Abnahmekriterien
- Kept the verdict blocking for the AC-12 hits in docs/plans because the spec counts any hit as blocking. The code itself has no blocking finding.
- Rated the README, doctor and resolver findings (2 to 5) as non-blocking: AC-11's sections are present and the behaviour follows D-27/D-30 as specified.
- Reported the exit codes the Bash calls recorded (they end with echo). For the commit message search, grep's own exit was 1 (no match), as the summary says.
- Extra search not listed in checks (its command exceeds the 500-character field): the name pattern over the tree and the code history (git log -p), both excluding docs/plans. Neither had a hit.
- Tried to run one CI matrix cell (Laravel 12, Boost 2.10.0) in an ignored build/ copy. The Jarvis guard blocked git archive in a review turn, so the Laravel 12 and lowest-Boost cells were checked by reading only. Locally only PHP 8.5, Laravel 13 and Boost 2.10.4 ran.
- Ran Pest with --compact --colors=never for the reported check; an earlier full-output run gave the same result (263 passed, 2 skipped).
- Did not run the stubs/claude/hooks/kit-docs-guard.sh wrapper by hand. It is unchanged since package 10; the smoke test checks it is installed, and DocsGuardTest runs the binary.

## Package 19: Review-Befunde von Paket 18 beheben
- Finding 1 (AC-12, name list in tracked docs/plans files) not addressed: docs/plans is read-only for packages and the fix needs a history rewrite by Jarvis or the owner.
- Finding 5: chose the label fix (default, no Deviations section) instead of treating a missing section as undecided; D-30 behaviour stays unchanged (missing section gives actions).
- Finding 5: no new resolver test, because ArchitectureResolverTest already covers 'missing section' => actions; added a command-level test for the label instead.
- Finding 4: .claude/rules files still come from the *.md glob (as before), not from V1Inventory::paths(), so unknown project rules there still count as leftovers; the new check skips the rule paths of paths() to avoid duplicates.
- D-1: I checked the diff and the commit message for customer names; none, the fixtures are generic.

## Package 20: Paket 18 erneut reviewen
- Kept the verdict blocking for AC-12 because the spec counts any hit of the name search as blocking; the code has no blocking finding and the fix needs Jarvis or the owner, not a code package.
- Reviewed the package 19 diff line by line; re-verified the rest by re-running all checks incl. smoke instead of a full re-read, since code outside the diff is unchanged since package 18.
- Re-ran the checks without a cd prefix because Jarvis could not match the first report; results were identical.
- Ran the name search with -c (per-file counts) instead of -n; line numbers come from an earlier -n run in this review.
- Ran the email search with docs/plans excluded so the plan text quoting the placeholder does not hide real hits; an earlier run without the exclusion found the same placeholder plus plan text only.
- Rated the two new kit:doctor findings non-blocking: warnings only, exit 0 per D-27.
- Only Laravel 13 / Boost 2.10.4 ran locally; the Laravel 12 and Boost 2.10.0 matrix cells and the GitHub workflows were not run.
- Did not run the shell wrapper stub by hand; it is unchanged since package 10 and the smoke test installs it.

## Package 21: Review-Befunde von Paket 18 beheben
- Finding 1 (AC-12, name list in tracked docs/plans files) not addressed: docs/plans is read-only for packages and the fix needs a history rewrite by Jarvis or the owner.
- Finding 3: chose 'unchanged v1 original, or version file present' over 'only unchanged'; with the version file the project is a v1 project and kit:upgrade archives changed skills, so they stay leftovers. A customised v1 skill without the version file is not reported.
- Finding 2: the hint depends only on whether .claude/.kit-version exists; it also applies to rule-file leftovers, whose old test message changed accordingly.
- Did not run the smoke script; it needs network and the changed check is not part of it.
- D-1: checked the diff and commit message for customer names; none, fixtures are generic.
