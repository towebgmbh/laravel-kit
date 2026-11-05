<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Decisions: Laravel Kit v2

## Package 1: Grundgerüst: composer.json, Kit, Config, Provider, Manifest, Pint und CI
- Manifest save() output has top-level keys sorted alphabetically (files, overrides, project, version) and empty files/overrides are written as {} objects.
- Manifest::load throws InvalidArgumentException for a manifest file that is not valid JSON.
- Added tests/Feature/.gitkeep and tests/Drift/.gitkeep so the phpunit suite directories exist.
- Added the ManifestTest afterEach cleanup with is_file/is_dir guards to avoid warnings.
