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
