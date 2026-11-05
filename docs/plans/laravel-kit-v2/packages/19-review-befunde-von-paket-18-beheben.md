<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 19: Review-Befunde von Paket 18 beheben

Kind: fix · Status: running · Model: default · Effort: default · Commits: – · Verdict: –

## Spec

The review of package 18 found blocking issues. Address each finding below. In your handoff, name every finding you did not address and why.

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -n -i -E "<name list>"` finds 23 lines, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (5), 11-review-befunde-von-paket-10-beheben.md (1), 12-paket-10-erneut-reviewen.md (5), 14-paket-10-erneut-reviewen.md (6), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (4), 18-schluss-review-gegen-alle-abnahmekriterien.md (2). These are the name list inside the check commands of the specs, plus review text that quotes it. The tree outside docs/plans, the code history outside docs/plans and all commit messages have no hits. Package agents cannot fix this because docs/plans is read-only for them. Before the first push or pull request, Jarvis or the owner must take the name list out of the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history so no commit carries it. Then rerun both searches and expect no output.
2. [non-blocking, README accuracy] README.md:77 describes `kit-brief` as "Turn the spec and backlog into a brief for a larger piece of work". The skill does something else (resources/boost/skills/kit-brief/SKILL.md:3): it summarises the current project state for session orientation and is read-only, as in v1. Change the row to match, for example "Summarise the project state at the start of a session (read-only)".
3. [non-blocking, README accuracy] README.md:119 says "Boost writes the guidelines to `CLAUDE.md` when that file exists and to `AGENTS.md` otherwise". That is true only for the claude_code agent (ClaudeCode::guidelinesPath()). Codex and other agents always write AGENTS.md. Suggested wording: "For Claude Code, Boost writes to CLAUDE.md when it exists, otherwise to AGENTS.md; Codex and other agents use their own files such as AGENTS.md."
4. [non-blocking, AC-8 completeness] src/Console/DoctorCommand.php:118-137 only counts `.claude/rules/*.md` and `.claude/.kit-version` as v1 leftovers. It misses the five v1 skills (`.claude/skills/{onboard,migrate,review,test,brief}/SKILL.md`), `.claude/templates/FEATURE_TEMPLATE.md` and `laravel-claude-kit/install.sh`. A project that kept those files but has no `.kit-version` shows "[OK] No v1 leftovers". Use V1Inventory::paths() plus UpgradePlan::KIT_COPY and add a test case.
5. [non-blocking, D-30 edge] In src/Upgrade/ArchitectureResolver.php:20-27 a docs/GUIDELINES.md without the "## Deviations from Kit Rules" section (`deviations()` returns null) silently gives `actions`. src/Console/UpgradeCommand.php:180-182 then labels the source as 'derived from "Deviations from Kit Rules" in docs/GUIDELINES.md', although that section does not exist. D-30 only covers an empty section. Either label it "default, no Deviations section in docs/GUIDELINES.md" or treat a missing section as undecided (ask, or require --architecture without interaction), and add a resolver test for that case.
