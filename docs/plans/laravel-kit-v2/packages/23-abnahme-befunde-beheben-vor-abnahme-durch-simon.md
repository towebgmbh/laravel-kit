<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 23: Abnahme-Befunde beheben vor Abnahme durch Simon

Kind: fix · Status: skipped · Model: default · Effort: default · Commits: none · Verdict: –

## Spec

The acceptance preparation found failing criteria. Fix each finding below; in your handoff name every finding you did not fix and why.

## Findings

1. AC-12: The name search `git grep -i -E "<name list>"` hits tracked files docs/plans/laravel-kit-v2/packages/10, 11, 12, 14, 17, 18, 20 and 22 (the name list inside their check commands and quoted review text). Outside docs/plans the tree has no hits, the commit messages have no hits, and `git log -p` outside docs/plans has no hits. Fix (Jarvis or owner, not a code package): remove docs/plans/laravel-kit-v2 (or at least the name list) from the branch and squash or rewrite the history so no commit carries it before the first push; then rerun `git grep` and `git log --format=%B | grep` with no output expected.

## Report

Finding 1 (AC-12) is not fixed. The customer names are still in tracked files under docs/plans/laravel-kit-v2/packages/ (specs 10, 11, 12, 14, 17, 18, 20, 22) and in 35 lines of the branch's patch history. The fix is outside what a code package may do. The code and the commit messages are clean. Pest (268 passed, 2 skipped) and Pint pass; I changed no code.

## Handoff

AC-12 is still open. I made no commit. Do not push or open a pull request until this is fixed.

State today:
- The earlier rewrite (the memory note says the list was replaced by "<name list>" through filter-branch) was undone. The plan sync wrote the name list back into the specs of packages 10, 11, 12, 14, 17, 18, 20 and 22. It appears there in the grep/log check commands and in the quoted review text.
- Searched from the vault client directory names, so no name went into a command or file. Tree: hits only in those 8 spec files, all for one client name, 35 lines in total. Commit messages main..HEAD: 0 hits. The patch history (git log -p main..HEAD) has the same 35 hits, all in docs/plans. Code outside docs/plans has none. This search covers only the client names in the vault. The longer list in the specs (project and product names) is not covered by it.
- Package 23 and 24 specs already contain only the placeholder "<name list>".

Why I did not fix it:
- The package rules say not to edit docs/plans.
- The finding itself names Jarvis or the owner as the fixer.
- A history rewrite of the branch is hard to reverse.
- The plan sync writes the specs again, so an edit by me would be overwritten.

What Jarvis or the owner needs to do, as the last step before the push:
1. Replace the name list in docs/plans/laravel-kit-v2 with "<name list>" in all commits (git filter-branch over main..HEAD, only docs/plans), or drop the folder from the branch.
2. Stop the plan sync from writing the real list back. The specs of packages 10 to 22 need the placeholder at the source. Otherwise the next "docs(plan)" commit brings the names back.
3. Rerun the searches with the real list: `git grep -i -E "<list>"`, `git log -p main..HEAD | grep -i -E "<list>"` and `git log --format=%B main..HEAD | grep -i -E "<list>"`. All three must print nothing.

Not verified locally: Laravel 12 and Boost 2.10.0 matrix cells and the GitHub workflows (they run only after a push). I did not run the smoke script; it needs network and no code changed since it last passed (package 22).

## Decisions

- Finding 1 (AC-12) not fixed: it needs a history rewrite and edits in docs/plans, which packages may not touch. The finding names Jarvis or the owner as the fixer. The plan sync would also overwrite a local edit.
- Searched using the client directory names from the vault, built at runtime in the shell, so no customer name went into a command, file or commit. This covers only the client names, not the longer list of project and product names in the specs.
- Made no commit and no code change.
- Reported outcome blocked rather than done, because the only finding is unresolved.
- The name-search results are in the summary and handoff, not in checks: the search commands were built from a shell variable and Jarvis could not match them.

## Checks

- `vendor/bin/pest --compact --colors=never`: exit 0, 268 passed, 2 skipped
- `vendor/bin/pint --test`: exit 0, passed
