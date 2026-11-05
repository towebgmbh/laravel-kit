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
