<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Laravel Kit v2

Status: Running

## Concept

## Goal

Ein Laravel-Projekt mit Boost bekommt mit `composer require --dev towebgmbh/laravel-kit` und `php artisan kit:install` eine schlanke SDD-Vorlage (Struktur, Ablauf, Doku, Skills), die für jeden Agenten in `boost.json` gleich gilt, kleine Arbeit nicht ausbremst, bei `boost:update` nicht kollidiert und sich über Composer aktualisieren lässt. Bestehende v1-Projekte wechseln mit `kit:upgrade`, ohne projekteigene Regeln zu verlieren.

## Users and flows

Nutzer sind Entwickler, die Laravel-Projekte mit KI-Agenten bauen (Claude Code, Codex und die anderen Boost-Agenten). Die Agenten selbst sind die zweite Zielgruppe: Sie lesen die Guidelines und rufen die Skills auf.

1. **Neues Projekt (Kickoff):** `boost:install` ausführen, `composer require --dev towebgmbh/laravel-kit`, `php artisan kit:install` (erkennt Stack und "neu", fragt nach). Das Kit trägt sich in `boost.json` ein, legt die Overrides (`pest/core` nur mit Pest), `config/kit.php`, die flache Doku und (bei Claude Code) die Hooks an, ruft `boost:update` auf und schreibt das Manifest. Dann im Agenten `/kit-onboard`: Der Skill fragt die Basisdaten ab (und bietet Pest an, wenn es fehlt) und baut die Hülle (Domain-Ordner, Doku mit Backlog, ADR-001, Architekturtests mit Pest, `pint.json`, GitHub-Workflow).
2. **Bestehendes Projekt:** wie oben, `kit:install` erkennt "bestehend". `/kit-onboard` analysiert mit `infer-conventions`, stellt höchstens 6 bis 8 Fragen (immer mit dem Architekturentscheid) und schreibt Doku, `config/kit.php` und Abweichungen in `.ai/rules`. Findet es fremde Doku (zum Beispiel `PROJECT.md`, `CONVENTIONS.md`), bietet es die Migration ins Kit-Layout an, mit Dry-Run und Bestätigung.
3. **Arbeit im Alltag (Grössenregel):** Der Agent stuft jede Arbeit selbst ein, nennt die Stufe in einem Satz und legt ohne Rückfrage los; der User kann umstufen ("mach einfach", "erst eine Spec").
   - Klein (Fix, Anpassung, wenige Dateien): direkt umsetzen, nur betroffene Tests, keine Spec, Doku nur bei geändertem dokumentiertem Verhalten oder geänderter Struktur.
   - Mittel (mehrere Schichten oder bestehendes Verhalten): kurzer Plan im Chat, umsetzen, Spec-Eintrag und Doku nachführen.
   - Gross (neues Feature, neue Domain, Vorhaben): Spec mit `/kit-spec` mit nummerierten Kriterien und "Open Questions", Freigabe durch den User, dann die Phasen Daten, Logik, Routen, Frontend, Integration, ohne ganze Suite pro Phase. Solange eine blockierende offene Frage steht, wird nicht umgesetzt. Tests nennen das Kriterium, das sie abdecken; `/kit-review` prüft die Abdeckung.
4. **Update:** `composer update towebgmbh/laravel-kit`, dann `php artisan kit:update`. Unveränderte verwaltete Dateien werden ersetzt, bei geänderten zeigt das Kit den Diff und fragt, dann `boost:update`.
5. **Upgrade von v1:** `kit:upgrade --dry-run` zeigt den Plan, `kit:upgrade` räumt auf (Unverändertes löschen, angepasste Kit-Dateien archivieren mit Diff-Bericht, projekteigene Regeln nach `.ai/guidelines/project/`), leitet die Architektur ab und führt `kit:install` aus.
6. **Kontrolle:** `php artisan kit:doctor` meldet Boost-Version, aktives Paket, v1-Reste, Override-Drift und Doku über der Split-Schwelle.
7. **Claude Code:** Am Ende eines Turns formatiert Pint die geänderten Dateien einmal (`Stop`-Hook). Der Doku-Wächter schickt den Agenten höchstens einmal pro Turn zurück, wenn der Turn die Struktur geändert hat (neue Datei unter `app/`, neue oder geänderte Migration, Änderung in `routes/`, neue Page oder Komponente), aber nichts unter `docs/`. Edits an bestehenden Dateien gehen still durch. Abschaltbar über `config('kit.hooks.docs_guard')`.

## Scope

- Composer-Paket `towebgmbh/laravel-kit` (MIT, öffentlich): `composer.json`, `KitServiceProvider`, `config/kit.php`, Manifest `.ai/kit-manifest.json`.
- Immer geladene Kit-Guidelines unter `resources/boost/guidelines/`: `core` (Grössenregel, SDD-Ablauf, Doku in beiden Layouts), `architecture` (drei Modi), `conventions`, `quality`, `stack` (fünf Varianten, mit `@scoped` auf die Frontend- bzw. API-Pfade). Zusammen höchstens 200 Zeilen pro gerendertem Projekt.
- Zwei Boost-Overrides: `.ai/guidelines/enforce-tests.blade.php` (mit der Testlinie) und `.ai/guidelines/pest/core.blade.php` (nur in Projekten mit Pest).
- Fünf Skills unter `resources/boost/skills/`: `kit-onboard` (inklusive Migration fremder Doku), `kit-spec` (Template mit Kriterien AC-n und "Open Questions"), `kit-review` (inklusive Kriterien-Abdeckung), `kit-brief`, `kit-docs-split`, mit Doku-Vorlagen und den Stubs für die Hülle neuer Projekte.
- Befehle `kit:install`, `kit:update`, `kit:upgrade` (mit `--dry-run`), `kit:doctor`.
- Claude-Extras: `Stop`-Hooks für Pint und den Doku-Wächter, zusammengeführt mit einer bestehenden `.claude/settings.json`.
- Kollisionsschutz: Tests rendern die Guidelines über Boosts `GuidelineComposer` für jede Variante und jeden Modus, inklusive Kontextbudget-Test; CI-Matrix PHP 8.3/8.4, Laravel 12/13, Boost Mindest- und neueste Version; wöchentlicher Drift-Lauf.
- Rauchtest-Skript `scripts/smoke.sh` an einer frischen Laravel-13-App (Install und v1-Upgrade), auch als CI-Job.
- README (Installation, Befehle, Update, Upgrade von v1).

## Out of scope

- Die Migration der einzelnen Projekte auf v2: eigene Tasks pro Projekt nach dem Release.
- Eigene Hinweise für Livewire 3, Inertia 1/2 oder Laravel 11: Dort gelten nur Boosts versionsgenaue Guidelines.
- PHPStan/Larastan, Reviewer-Subagenten und Prüfungen im Browser: frühestens v2.1.
- Skills `kit-test` und `kit-migrate`: entfallen (Testlinie im Override und Boosts `testing-best-practices`, Migration in `kit-onboard`).
- Ein Override für Boosts `phpunit`-Guideline: PHPUnit-Projekte bekommen die Testlinie über `enforce-tests`, den Testlauf regelt Boost.
- Tag `v2.0.0` und Packagist-Registrierung: macht Simon selbst.
- Herd-Setup für die Abnahme (es ist ein Paket).
- Overrides weiterer Boost-Texte, insbesondere `foundation`.

## Design

**Paket.** Namespace `Towebgmbh\LaravelKit\`, Autoload `src/`. `Kit` (Konstanten `VERSION = '2.0.0'`, `PACKAGE`, `STACKS`, `ARCHITECTURES`, Pfad-Helfer), `KitServiceProvider` (merged `config/kit.php`, setzt `boost.enforce_tests` auf `true`, wenn das Projekt es nicht selbst setzt, registriert die Befehle), `Manifest`, `Overrides`, `Support\ComposerPackages`, `Support\StackDetector`, `Support\ProjectDetector`, `Support\ManagedFiles`, `Support\ClaudeSettings`, `Hooks\DocsGuard`, `Upgrade\*`, `Console\{Install,Update,Upgrade,Doctor}Command`. Binärdatei `bin/kit-docs-guard` (Composer `bin`). Laufzeit-Abhängigkeiten: `laravel/boost`, `illuminate/*`, `sebastian/diff` `^6.0|^7.0|^8.0|^9.0`, `symfony/process` `^7.2|^8.0`.

**Konfiguration (`config/kit.php`).** `stack` (`blade`, `livewire`, `inertia-vue`, `inertia-react`, `api`), `architecture` (`actions`, `services`, `migrating`), `docs.split_threshold` (Standard 4), `hooks.docs_guard` (Standard `true`). Das Projekt erhält eine eigene Kopie; sie gehört dem Projekt und wird von `kit:update` nicht ersetzt. Legt `kit:install` die Datei im selben Lauf neu an, setzt es die gewählten Werte auch in der laufenden Config, bevor es `boost:update` im selben Prozess aufruft (D-40).

**Einklinken in Boost (geprüft in Boost 2.10.3; neueste Version 2.10.4).** Boost liest bei jeder direkten Composer-Abhängigkeit `resources/boost/guidelines/` und `resources/boost/skills/` (`ThirdPartyPackage::boostDirectories`, `PackageRegistry::boostPath`). Bei jedem `boost:update` filtert Boost Drittanbieter-Guidelines und -Skills nach `packages` in `boost.json` (`InstallCommand::selectThirdPartyPackages`, `GuidelineComposer::getThirdPartyGuidelines`). Deshalb trägt `kit:install` das Paket dort ein. Die Kit-Guidelines erscheinen unter den Schlüsseln `towebgmbh/laravel-kit/<datei>`. Der Agent `claude_code` schreibt nach `CLAUDE.md` nur, wenn die Datei existiert, sonst nach `AGENTS.md` (`ClaudeCode::guidelinesPath()`); das Laravel-13-Skeleton liefert beide Dateien. Skill-Ordner kopiert Boost vollständig (`SkillWriter::copyDirectory`), `.blade.php` wird gerendert, `.md` formatiert, andere Dateien unverändert. Kit-Guidelines sind Blade und lesen `config('kit.*')` beim Rendern; `@include` und `@props` ersetzt Boost vor dem Rendern durch Platzhalter. `@scoped([...])`-Blöcke bleiben mit Boosts Standard-Config im Text; mit `BOOST_RULES_SCOPED_GUIDELINES` verschiebt Boost sie nach `.ai/rules/boost/`. `record-rule` ist ein Boost-MCP-Tool (`src/Mcp/Tools/RecordRule.php`) und nur mit eingerichtetem Boost-MCP verfügbar; ohne es meldet der Agent die Regel als Text oder schreibt sie von Hand nach `.ai/rules` (wie `infer-conventions`).

**Overrides und Testlinie.** Boost nimmt eine Datei unter demselben relativen Pfad in `.ai/guidelines/` statt des eigenen Texts (`GuidelineComposer::guidelinePath`). Gibt es keinen Boost-Text mit diesem Pfad, rendert Boost die Datei als eigene Guideline (`getUserGuidelines()`). Deshalb schreibt, verwaltet und prüft das Kit `pest/core` nur, wenn `pestphp/pest` in der `composer.json` des Projekts steht (das aktuelle Laravel-13-Skeleton nutzt PHPUnit). `enforce-tests` enthält die Testlinie und gilt für Pest und PHPUnit. Boost rendert `enforce-tests` nur, wenn `config('boost.enforce_tests')` wahr ist oder das Projekt mindestens 6 Tests hat (`InstallCommand::determineTestEnforcement`); deshalb setzt der `KitServiceProvider` `boost.enforce_tests` auf `true`, solange das Projekt keinen eigenen Wert hat. `pest/core` wird übernommen, aber "Running Tests" verlangt: betroffene Tests während der Arbeit, die ganze Suite (`--parallel --compact`) höchstens einmal am Ende eines Runs oder Tasks und nur bei geteilter Logik, Migrationen, Config oder Abhängigkeiten. Die Klasse `Overrides` kennt pro Override die Vorlage, die ersetzte Boost-Datei und deren Referenz-Prüfsumme, dazu die Konstante `BOOST_VERSION` der Boost-Version, gegen die die Overrides geschrieben sind (2.10.0 bis 2.10.3 haben denselben `pest/core`, 2.10.4 einen anderen). Liegt das installierte Boost unter `BOOST_VERSION`, warnt `kit:doctor` statt Drift zu melden (D-41).

**Kontextbudget.** Die Kit-Guidelines zusammen (wie `GuidelineComposer::composeGuidelines` sie für die Schlüssel `towebgmbh/laravel-kit/*` ausgibt) haben pro Stack, Architekturmodus und Doku-Layout höchstens 200 Zeilen; Details stehen in den Skills.

**Manifest `.ai/kit-manifest.json`.** `version`, `project` (`new` oder `existing`), `files` (verwaltete Datei mit `sha256`), `overrides` (Override-Pfad mit `boost_file` und `boost_sha256`). Verwaltet sind `enforce-tests`, `pest/core` (nur mit Pest) und `.claude/hooks/kit-docs-guard.sh`. Hook-Einträge in `.claude/settings.json` werden über den Befehlstext zusammengeführt, nicht gehasht.

**Doku-Layouts.** Flach: `docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md`, `docs/DECISIONS.md`. Gesplittet (erkannt an `docs/README.md`): `docs/README.md` als Map, `docs/domains/<domain>/README.md` (Indexkarte) und `SPECIFICATIONS.md`, `docs/cross-cutting/<concern>/`, `docs/decisions/ADR-0NN-*.md` mit `DECISIONS.md` als Index, `docs/_templates/`. Domain-Zählung für die Split-Schwelle: direkte Unterordner von `app/` mit einem Unterordner `Actions`, `Models` oder `Services`.

**Claude-Extras.** Zwei `Stop`-Hooks: `vendor/bin/pint --dirty --format agent` und `"$CLAUDE_PROJECT_DIR"/.claude/hooks/kit-docs-guard.sh`, ein dünner Wrapper um `vendor/bin/kit-docs-guard`. Kein `PostToolUse`-Hook. `DocsGuard` liest das Hook-JSON von stdin; es lässt durch bei `stop_hook_active`, bei `hooks.docs_guard` = `false` in `config/kit.php` und bei jedem Fehler. Sonst nimmt es aus dem Transcript (`transcript_path`) die Pfade der Edit/Write/MultiEdit/NotebookEdit-Aufrufe seit dem letzten User-Prompt. "Neu" ist eine Datei, die Git nicht kennt (`git ls-files --error-unmatch` schlägt fehl); ohne Git-Repo zählen nur `Write`-Pfade als neu. Strukturell sind: neue Datei unter `app/`, jede Datei unter `database/migrations/`, jede Datei unter `routes/`, neue Datei unter `resources/js/` oder `resources/views/`. Gibt es eine strukturelle Änderung und keinen Pfad unter `docs/`, gibt es `{"decision":"block","reason":…}` aus.

**Upgrade v1.** Erkennung über `.claude/.kit-version` = `1.0.0`. Vergleich per Prüfsumme (Zeilenenden normalisiert) mit `resources/legacy/v1/templates`: Regeln 01, 02, 04, 05, 06, `03-frontend.md` gegen die drei Varianten, die Skills `onboard`, `migrate`, `review`, `test`, `brief`, `.claude/templates/FEATURE_TEMPLATE.md`. Unverändert: löschen. Angepasst: nach `.ai/kit-upgrade/v1/` archivieren, Diff im Bericht `.ai/kit-upgrade/v1/REPORT.md`. Andere Dateien in `.claude/rules/`: nach `.ai/guidelines/project/`. `laravel-claude-kit/` im Root mit `install.sh`: löschen. `docs/` bleibt.

**Kollisionsschutz.** Testhilfe `BoostFixture` baut pro Test ein Mini-Projekt (composer.json, composer.lock, leere `CLAUDE.md` und `AGENTS.md`, ggf. package.json und Lockfile, Symlink `vendor/towebgmbh/laravel-kit` auf das Paket) und setzt den Base-Path der Testbench-App darauf. Liste der Widerspruchssätze in `tests/Support/Contradictions.php`. Boosts Provider registriert sich bei `runningUnitTests()` nicht; die Tests setzen die Umgebung auf `local`. Der Rauchtest pinnt `laravel/boost` auf `Overrides::BOOST_VERSION`, damit ein neues Boost-Release keine PRs bricht; neue Boost-Versionen meldet der wöchentliche Drift-Lauf.

**Öffentliches Repo.** Jedes Implement-Paket hält D-1 ein und prüft vor dem Commit seinen Diff und seine Commit-Nachricht auf Kundennamen, Kundendaten und echte Projektnamen. Paket 13 sucht zusätzlich mit `git grep` über das ganze Repo und alle Commit-Nachrichten; die Namensliste steht nur in den Specs, nie im Repo.

## Decisions

- D-1 (Simon) Repo `towebgmbh/laravel-kit` öffentlich, MIT. Keine Kundennamen, Kundendaten oder echten Projektnamen in Code, Beispielen, Tests, Doku oder Commits.
- D-2 (Simon) Kit-Inhalte, Befehlsausgaben und README auf Englisch.
- D-3 (Simon) Abhängigkeiten: `laravel/boost` ^2.10, Laravel 12 und 13, PHP ^8.3. Tests mit Pest und Orchestra Testbench, Stil mit Pint.
- D-4 (Simon) Grundprinzip: Das Kit regelt Struktur und Ablauf, Boost die Framework-APIs. Keine Laravel-, Livewire-, Inertia- oder Pest-Regeln wiederholen, die Boost liefert. Für Livewire 3, Inertia 1/2 gelten nur Boosts Guidelines.
- D-5 (Simon) Das Kit ist ein Boost-Drittanbieter-Paket; `kit:install` trägt es in `boost.json` ein.
- D-6 (Simon) Genau zwei Overrides: `enforce-tests` und `pest/core`. `foundation` wird nicht überschrieben; die Kit-Guideline erklärt das Nachführen von `docs/` als stehende Anforderung des Users und die Domain-Ordner aus der Doku als freigegeben.
- D-7 (Simon, Nachträge, ersetzt die Chat-Antwort) Die Testlinie steht im Override `enforce-tests`. Damit sie auch in neuen Projekten mit weniger als 6 Tests erscheint, setzt das Kit `boost.enforce_tests` auf `true`, wenn das Projekt keinen eigenen Wert hat. So gilt auch der Grund der Chat-Antwort, und die Testlinie zählt nicht zum Kontextbudget.
- D-8 (Simon, im Chat) `kit:upgrade` archiviert angepasste v1-Kit-Regeln, angepasste v1-Skills und das angepasste Feature-Template unter `.ai/kit-upgrade/v1/` mit Diff-Bericht. Nur projekteigene Regeln (nicht im v1-Satz, z. B. `00-*`, `07-*`, `08-*`) kommen nach `.ai/guidelines/project/`. Grund: Boost lädt alles unter `.ai/guidelines/` und brächte sonst die Widerspruchssätze zurück.
- D-9 (Simon) Architektur: DDD mit Actions (`app/{Domain}/Actions/`, `handle()`), dünne Controller im Singular, DTOs als `final readonly class` ab mehr als 3 Parametern, Form Requests, Policies über Auto-Discovery oder `#[UsePolicy]`, Models mit den Laravel-13-Attributen und `casts()` (auf Laravel 12 mit den Eigenschaften), nie unguarded. `config('kit.architecture')`: `actions`, `services`, `migrating`.
- D-10 (Simon) Testlinie wie im Brief (Fehler schnell finden oder Folgefehler ausschliessen, nichts doppelt, Pflichtbereiche, keine Tests für Framework-Verhalten, Getter oder HTML, Datasets, nur externe Dienste mocken, Verweis auf `testing-best-practices`).
- D-11 (Simon) Doku nachführen gehört zur Definition of Done jedes Runs und Tasks der Stufen mittel und gross; bei kleiner Arbeit nur, wenn sich dokumentiertes Verhalten oder Struktur ändert (D-32). Zwei Layouts, Split-Schwelle `config('kit.docs.split_threshold')` = 4, Erkennung an `docs/README.md`.
- D-12 (Simon) Fünf Stack-Varianten: `blade`, `livewire` (Livewire 4 + Flux, kein Volt), `inertia-vue`, `inertia-react` (Inertia 3, TS, Wayfinder), `api`. Nur die aktive wird gerendert, jede legt nur Ordner und Muster fest, mit `@scoped` auf ihre Pfade beschränkt.
- D-13 (Simon, Nachträge) Fünf Skills mit Präfix `kit-`: `kit-onboard` (bestehend und neu, Quiet-Modus, Migration fremder Doku mit Dry-Run und Bestätigung), `kit-spec`, `kit-review`, `kit-brief`, `kit-docs-split`. `kit-test` und `kit-migrate` entfallen.
- D-14 (Simon, Nachträge) Claude-Extras: Pint einmal im `Stop`-Hook statt `PostToolUse` pro Edit; Doku-Wächter im `Stop`-Hook; zusammengeführt mit bestehender `.claude/settings.json`.
- D-15 (Simon) Befehle `kit:install`, `kit:update`, `kit:upgrade`, `kit:doctor` mit Manifest; nie ungefragt überschreiben.
- D-16 (Simon) Kollisionsschutz: Render-Tests über `GuidelineComposer`, CI-Matrix, wöchentlicher Drift-Lauf.
- D-17 (Simon) Abnahme ohne Herd. Tag und Packagist macht Simon.
- D-18 (Planung) Namespace `Towebgmbh\LaravelKit`, Version als Konstante `Kit::VERSION = '2.0.0'`, weil ein Path-Repository nur `dev-main` liefert.
- D-19 (Planung) Der Doku-Wächter erkennt die Änderungen des Turns aus dem Transcript, "neu" über Git, und lässt bei jedem Fehler durch. Die Logik liegt in PHP (`DocsGuard`, `vendor/bin/kit-docs-guard`), das Shell-Skript ist nur ein Wrapper. Grund: Nur das Transcript kennt den Turn; PHP ist ohne `jq` testbar.
- D-20 (Planung) Claude-Extras schreibt das Kit nur, wenn `boost.json` den Agenten `claude_code` enthält.
- D-21 (Planung) `kit:install` setzt ein eingerichtetes Boost voraus (gültige `boost.json`) und bricht sonst mit Hinweis auf `boost:install` ab. Es ruft `boost:update --no-discover` auf.
- D-22 (Planung) Verwaltet (Manifest, `kit:update`) sind nur die Overrides (D-37) und `.claude/hooks/kit-docs-guard.sh`. `config/kit.php` und `docs/` legt das Kit einmal an (Ergebnis `created` oder `exists`), danach gehören sie dem Projekt.
- D-23 (Planung) "Neues Projekt": Es gibt nur die Migrationen von Skeleton und Starter Kits (Präfix `0001_01_01_` oder Endung `_add_two_factor_columns_to_users_table.php`). Interaktiv bestätigt, Optionen `--new` und `--existing`, gespeichert als `project` im Manifest; `kit-onboard` liest es.
- D-24 (Planung) Override-Drift: `Overrides` hält die Prüfsumme der Boost-Datei, gegen die der Override geschrieben ist, und die Konstante `BOOST_VERSION`; das Manifest speichert die Prüfsumme; `kit:doctor` vergleicht mit der installierten Boost-Datei (ausser bei älterem Boost, D-41), der wöchentliche Lauf mit der neuesten Boost-Version.
- D-25 (Planung) Render-Tests prüfen Marker-Sätze und Widerspruchssätze im gesamten Text; Pest-Snapshots nur der Kit-Guidelines und nur auf Laravel 13, weil sich Boosts eigener Text zwischen Versionen ändert.
- D-26 (Planung) Abnahme-Kriterien mit frischer App laufen über `scripts/smoke.sh` (Arbeitsordner `$WORK/fresh` und `$WORK/upgrade`, Boost gepinnt auf `Overrides::BOOST_VERSION`), auch als CI-Job `smoke` (PHP 8.4, Laravel 13).
- D-27 (Planung) `kit:doctor` endet mit Exit-Code 1 bei fehlender `boost.json` oder fehlendem Manifest, inaktivem Paket, ungültiger Config oder Drift; v1-Reste, ein `pest/core`-Override ohne Pest, ein Boost unter `Overrides::BOOST_VERSION` und Doku-Grösse sind Warnungen (Exit 0).
- D-28 (Planung, nach Plan-Review) Diffs in `kit:update` und im Upgrade-Bericht mit `sebastian/diff` `^6.0|^7.0|^8.0|^9.0` als Laufzeit-Abhängigkeit, damit auch Projekte mit Pest 5 und PHPUnit 13 (verlangt `sebastian/diff` ^9) installieren können.
- D-29 (Planung) `kit:upgrade` fasst nur die fünf v1-Skill-Ordner an; andere Skills in `.claude/skills` (Boost-Skills, eigene) bleiben.
- D-30 (Planung) `kit:upgrade` leitet die Architektur aus "## Deviations from Kit Rules" in `docs/GUIDELINES.md` ab (nach Entfernen von HTML-Kommentaren, Blockquote-Zeilen und Leerzeilen: leer -> `actions`; "migrat" -> `migrating`; "service" -> `services`), sonst fragt es; ohne Interaktion verlangt es dann `--architecture`. Das unveränderte v1-Template ergibt `actions`.
- D-31 (Planung) Der Drift-Test läuft nur mit `KIT_BOOST_DRIFT=1` (wöchentlicher Workflow), damit ein Boost-Release fremde PRs nicht blockiert.
- D-32 (Simon, Nachträge) Pragmatismus vor Zeremonie: Grössenregel klein, mittel, gross in der `core`-Guideline; der Agent stuft selbst ein, nennt die Stufe in einem Satz und legt ohne Rückfrage los; der User kann umstufen.
- D-33 (Simon, Nachträge) Nur Stufe gross: Das Feature-Template macht aus "Done When" nummerierte Kriterien AC-1, AC-2 …; Tests nennen das Kriterium; `/kit-review` prüft, ob jedes Kriterium einen Test oder eine benannte manuelle Prüfung hat.
- D-34 (Simon, Nachträge) Nur Stufe gross: Das Template hat "Open Questions"; solange dort eine blockierende Frage steht, wird nicht umgesetzt, der Agent fragt statt zu raten.
- D-35 (Simon, Nachträge) Kontextbudget: Die immer geladenen Kit-Guidelines haben pro gerendertem Projekt höchstens 200 Zeilen (Ziel 150 bis 200); ein Test misst es pro Variante und schlägt darüber fehl. Details gehören in die Skills; die Doku-Regeln stehen deshalb in `core` statt in einer eigenen Guideline.
- D-36 (Simon, Nachträge) Der Doku-Wächter greift nur bei strukturellen Änderungen (siehe Design), höchstens einmal pro Turn, und lässt sich über `config('kit.hooks.docs_guard')` abschalten. Planung: "neue Page oder Komponente" = neue Datei unter `resources/js/` oder `resources/views/`.
- D-37 (Planung, nach Plan-Review) Der Override `pest/core` wird nur geschrieben, verwaltet (Manifest, `kit:update`) und geprüft (`kit:doctor`), wenn `pestphp/pest` in `require` oder `require-dev` der Projekt-`composer.json` steht. Grund: Ohne Pest gäbe es sonst neben Boosts `phpunit`-Guideline eine eigene Guideline "This project uses Pest".
- D-38 (Planung, nach Plan-Review) Der Kickoff in `kit-onboard` bietet in seiner Fragerunde an, Pest zu installieren, wenn es fehlt (`composer require pestphp/pest --dev -W`, `vendor/bin/pest --init`), und schreibt den Pest-Architekturtest nur mit installiertem Pest.
- D-39 (Planung, nach Plan-Warnung) AC-12 ist ein Kriterium jedes Implement-Pakets: Jedes prüft vor dem Commit Diff und Commit-Nachricht; Paket 13 führt die Namens-Suche mit `git grep` über Repo und Commit-Nachrichten aus. Die Reviews prüfen es erneut.
- D-40 (Planung, nach Plan-Review) `kit:install` setzt `kit.stack` und `kit.architecture` in der laufenden Config (`config([...])`), wenn es `config/kit.php` im selben Lauf neu angelegt hat, bevor es `boost:update` aufruft. Grund: Die Config wurde beim Booten aus den Paket-Defaults geladen; ohne das stünde nach `kit:install --stack=livewire` "Stack: Blade with Alpine" in CLAUDE.md, und `kit:upgrade` gäbe den `services`-Modus nicht weiter. Eine bestehende `config/kit.php` gilt mit ihren beim Booten geladenen Werten.
- D-41 (Planung, nach Plan-Review) Liegt das installierte Boost unter `Overrides::BOOST_VERSION`, meldet `kit:doctor` statt der Drift-Prüfung `[WARN] Laravel Boost <version> is older than <BOOST_VERSION>, the version the kit overrides were written for. Run composer update laravel/boost.` Grund: `^2.10` erlaubt ältere Boost-Versionen, deren `pest/core` sich unterscheidet; "Update the kit" hilft dort nicht.
- D-42 (Planung, nach Plan-Review) Das Paketgerüst ist geteilt: Paket 1 legt Grundgerüst, Klassen und CI an, Paket 2 den Testbench-Harness mit Boost-Fixture und die Suche nach der niedrigsten Boost-Version.

## Risks

- **Boost 2.10.0 fehlt eine genutzte API** (Override-Pfad, Drittanbieter-Erkennung, `@scoped`): Paket 2 ermittelt die niedrigste 2.10.x, mit der die Tests grün sind, setzt die Constraint darauf (z. B. `^2.10.3`) und meldet es als Abweichung.
- **Roster erkennt das Fixture-Projekt nicht** (Versionsformat im Lockfile, Cache): Paket 2 passt das Fixture an; klappt es nicht, bindet der Test eine `ProjectManager`-Instanz, die das Fixture-Verzeichnis scannt (`ProjectManager::fresh($path)`), und dokumentiert das.
- **Boosts Provider in Tests:** `BoostServiceProvider::shouldRun()` ist bei `runningUnitTests()` falsch. Paket 2 setzt die Testumgebung auf `local`; reicht das nicht, bindet `TestCase` die Singletons aus `BoostServiceProvider::register()` und registriert `boost:update` selbst.
- **Kontextbudget reicht nicht:** Paket 3 kürzt zuerst Wiederholungen und verschiebt Details in `kit-spec` oder `kit-review`; der Grenzwert 200 bleibt.
- **Boost-Versionen unterscheiden sich bei `pest/core`:** Auf der Mindestversion warnt `kit:doctor` (D-41); die Hash-Prüfung in `OverridesTest` läuft nur bei `Overrides::BOOST_VERSION`, und `DoctorCommandTest` richtet seine Erwartung nach der installierten Version.
- **Transcript-Format von Claude Code ändert sich:** Der Doku-Wächter lässt dann durch (fail open); die Pflicht bleibt über die Guideline.
- **Boost ändert `enforce-tests` oder `pest/core`:** Der wöchentliche Lauf schlägt fehl; ein Fix-Task passt Override, Referenz-Prüfsumme und `BOOST_VERSION` an.
- **Pint ohne `--format agent`** in alten Projekten: Der Stop-Hook meldet einen Fehler; README nennt Pint ^1.27 als Voraussetzung.
- **Rauchtest braucht Netz und Composer:** läuft in CI; lokal führt Simon ihn in der Abnahme aus.

## Acceptance

- AC-1 Die Paket-Tests sind grün und Pint ist sauber, in der Matrix PHP 8.3/8.4, Laravel 12/13, Boost Mindest- und neueste Version.
  Verified by: `vendor/bin/pest` und `vendor/bin/pint --test` mit Exit 0; Workflow `.github/workflows/tests.yml` grün.
- AC-2 Für jeden Architekturmodus und jede Stack-Variante enthält der über Boosts `GuidelineComposer` gerenderte Text die Kit-Guidelines (nur die aktive Variante und den aktiven Modus), die Overrides greifen (die Testlinie erscheint auch ohne Tests im Projekt), ohne Pest erscheint keine Pest-Guideline, und kein Satz aus `Contradictions` erscheint.
  Verified by: `tests/Feature/Guidelines/KitGuidelinesTest.php`, `tests/Feature/Guidelines/OverridesTest.php`, `tests/Feature/Guidelines/StackGuidelineTest.php`, `tests/Feature/Commands/InstallCommandTest.php` (Fixture ohne Pest).
- AC-3 In einer frischen Laravel-13-App mit Boost und dem Paket als Path-Repository ergibt schon `kit:install --stack=livewire` (und danach `boost:update`) eine CLAUDE.md und eine AGENTS.md mit den Kit-Guidelines der gewählten Variante, der Testlinie, ohne die Widerspruchssätze und ohne `# Pest`; die fünf `kit-*`-Skills liegen in `.claude/skills`.
  Verified by: `tests/Feature/Commands/InstallCommandTest.php` (Variante und Modus direkt nach `kit:install`); `bash scripts/smoke.sh`, Szenario "fresh" meldet `PASS`; CI-Job `smoke` grün.
- AC-4 `kit:install` überschreibt nie ungefragt: Abweichende Dateien bleiben ohne Interaktion unverändert und werden gemeldet; ein zweiter Lauf meldet die verwalteten Dateien als `unchanged` und `config/kit.php` und die Doku als `exists`; eine bestehende `.claude/settings.json` behält alle Einträge und bekommt die zwei `Stop`-Hooks genau einmal und keinen `PostToolUse`-Hook.
  Verified by: `tests/Feature/Commands/InstallCommandTest.php`, `tests/Unit/ClaudeSettingsTest.php`.
- AC-5 `kit:update` ersetzt unveränderte verwaltete Dateien, zeigt bei geänderten den Diff und fragt, und ruft danach `boost:update` auf.
  Verified by: `tests/Feature/Commands/UpdateCommandTest.php`.
- AC-6 Der Doku-Wächter blockiert einen Turn mit struktureller Änderung (neue Datei unter `app/`, Migration, `routes/`, neue Datei unter `resources/js/` oder `resources/views/`) ohne Änderung unter `docs/` genau einmal (`decision: block`); Edits an bestehenden Dateien, `stop_hook_active`, eine Doku-Änderung im Turn oder `kit.hooks.docs_guard` = `false` lassen durch.
  Verified by: `tests/Unit/DocsGuardTest.php`.
- AC-7 An einer v1-Fixture meldet `kit:upgrade --dry-run` die richtigen Aktionen ohne Dateiänderung; `kit:upgrade` löscht Unverändertes, archiviert Angepasstes mit Diff-Bericht, verschiebt projekteigene Regeln nach `.ai/guidelines/project/`, setzt `kit.architecture` (und CLAUDE.md zeigt den abgeleiteten Modus), entfernt `laravel-claude-kit/`, lässt `docs/` und fremde Skills stehen und führt `kit:install` aus. Ein unverändertes v1-Template ergibt `actions` ohne Rückfrage.
  Verified by: `tests/Feature/Commands/UpgradeCommandTest.php`, `tests/Unit/ArchitectureResolverTest.php`; `bash scripts/smoke.sh`, Szenario "upgrade" meldet `PASS`.
- AC-8 `kit:doctor` meldet Boost-Version, aktives Paket, v1-Reste, Override-Drift, ein älteres Boost als Warnung und Doku über der Schwelle; ein veränderter Override-Hash erscheint als Drift mit Exit-Code 1.
  Verified by: `tests/Feature/Commands/DoctorCommandTest.php`; im Rauchtest die Zeile `[FAIL] Override drift`.
- AC-9 Boost erkennt genau die fünf `kit-*`-Skills mit gültigem Frontmatter; `kit-onboard` beschreibt beide Modi, die Migration fremder Doku mit Dry-Run und das Angebot, Pest zu installieren; die Stubs der Hülle (`pint.json`, Workflow, Architekturtest) sind gültig.
  Verified by: `tests/Feature/Skills/SkillDiscoveryTest.php`, `tests/Feature/Skills/OnboardStubsTest.php`.
- AC-10 Der wöchentliche Lauf schlägt fehl, wenn Boosts `enforce-tests` oder `pest/core` von der Referenz-Prüfsumme abweicht.
  Verified by: `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` lokal grün; `.github/workflows/boost-drift.yml` hat die Trigger `schedule` und `workflow_dispatch` (Prüfung der Datei; auf GitHub erst nach dem Merge auf den Default-Branch startbar).
- AC-11 Die README beschreibt Installation, Befehle, Update und Upgrade von v1.
  Verified by: Abschnitte "Installation", "Commands", "Updating" und "Upgrading from v1" in `README.md`.
- AC-12 Das Repo enthält keine Kundennamen, Kundendaten oder echten Projektnamen, weder in Dateien noch in Commit-Nachrichten.
  Verified by: die Namens-Suche mit `git grep` und `git log --format=%B | grep` in Paket 13 und im Paket "Schluss-Review", jeweils ohne Treffer.
- AC-13 Die immer geladenen Kit-Guidelines haben für jede Kombination aus Stack, Architekturmodus und Doku-Layout höchstens 200 Zeilen.
  Verified by: `tests/Feature/Guidelines/ContextBudgetTest.php`.
- AC-14 Die `core`-Guideline enthält die Grössenregel (klein, mittel, gross); das Feature-Template von `kit-spec` hat nummerierte Kriterien und "Open Questions" mit Umsetzungsstopp; `kit-review` prüft die Kriterien-Abdeckung.
  Verified by: Marker-Sätze in `tests/Feature/Guidelines/KitGuidelinesTest.php` und `tests/Feature/Skills/SkillContentTest.php`.

## Track sheet

| # | Kind | Title | Status | Model | Commits | Verdict |
|---|---|---|---|---|---|---|
| 1 | implement | Grundgerüst: composer.json, Kit, Config, Provider, Manifest, Pint und CI | done | sonnet | `839cc85…c7407dc` | – |
| 2 | implement | Testbench-Harness mit Boost-Fixture und niedrigste Boost-Version | done | opus | `5ddd70c…9964038` | – |
| 3 | implement | Kit-Guidelines (Kern mit Grössenregel und Doku, Architektur, Konventionen, Qualität) und die zwei Overrides | done | opus | `7449fd6…585493f` | – |
| 4 | implement | Stack-Varianten mit @scoped, Stack-Erkennung und Kontextbudget-Test | done | sonnet | `6024dda…c36e79d` | – |
| 5 | implement | Skills kit-spec (Kriterien, Open Questions), kit-review und kit-brief | done | sonnet | `abef363…2490900` | – |
| 6 | implement | Skills kit-onboard (beide Modi, Migration fremder Doku) und kit-docs-split mit Doku-Vorlagen und Projekt-Hülle | done | opus | `e87693e…47cb5c0` | – |
| 7 | implement | kit:install mit Manifest, boost.json-Eintrag, Overrides, Config und flacher Doku | done | opus | `93da9af…4cb268d` | – |
| 8 | implement | Claude-Stop-Hooks: Pint einmal pro Turn und struktureller Doku-Wächter | running | opus | – | – |
| 9 | implement | kit:update mit Diff und Rückfrage | pending | sonnet | – | – |
| 10 | review | Review: Guidelines, Overrides, Stack, Budget, Install, Hooks und Update | pending | opus | – | – |
| 11 | implement | kit:upgrade von v1 auf v2 mit Dry-Run, Archiv und Bericht | pending | opus | – | – |
| 12 | implement | kit:doctor, wöchentlicher Boost-Drift-Lauf und README | pending | sonnet | – | – |
| 13 | implement | Rauchtest an frischer Laravel-13-App (Install und v1-Upgrade) als Skript und CI-Job | pending | sonnet | – | – |
| 14 | review | Schluss-Review gegen alle Abnahmekriterien | pending | opus | – | – |
| 15 | acceptance | Abnahme durch Simon | pending | opus | – | – |

Each package's spec, report, handoff, decisions, checks and findings are in `packages/`; every reported decision is in `decisions.md`.
