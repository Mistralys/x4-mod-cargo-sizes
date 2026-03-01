# Synthesis Report — FOMOD Example Ship Rework

> **Project:** 2026-03-01-fomod-example-ship-rework-1  
> **Date:** March 1, 2026  
> **Status:** All 6 WPs COMPLETE

---

## Summary

This rework replaced `array_rand()` ship selection in both the FOMOD installer plugin descriptions and the release notes comparison table with a **deterministic, config-driven selection system** backed by `config/build-config.json`. It also added dedicated PHPUnit unit tests for the affected code paths and updated the project manifest to reflect all changes.

---

## What Was Delivered

### WP-001 — BuildConfig Extension
- Added `"example-ships"` object to `config/build-config.json` with 8 type-size → ship-label mappings.
- Added `BuildConfig::KEY_EXAMPLE_SHIPS` constant.
- Added `BuildConfig::getExampleShip(string $shipType, string $shipSize): string` public method (returns `''` when absent).

### WP-002 — CargoSizeExtractor + FileCollection Wiring
- Added `BuildConfig $buildConfig` as a required third parameter to `CargoSizeExtractor::__construct()`.
- Updated `CargoSizeBuildTools::build()` to pass `self::getConfig()` as the third argument.
- Called `FileCollection::setConfig($this->buildConfig)` in `CargoSizeExtractor::extract()` immediately after `FileCollection::reset()`.
- Added `public static function setConfig(BuildConfig $config): void` and `private static ?BuildConfig $buildConfig = null` to `FileCollection`.

### WP-003 — FileCollection Deterministic Selection
- Rewrote `FileCollection::getExampleShipDescription()` to prefer the config-supplied ship label before falling back to `array_rand()`.
- Added the "Unchanged" FOMOD option invariant comment above the `empty($storageFiles)` guard.
- Extended `FileCollection::reset()` to also clear `$buildConfig = null` (correct complete-reset semantics).

### WP-004 — ReleaseNotesGenerator Deterministic Selection
- Added `BuildConfig $buildConfig` as a required fourth parameter to `ReleaseNotesGenerator::__construct()`.
- Updated `CargoSizeExtractor::writeReleaseNotes()` to pass `$this->buildConfig`.
- Rewrote `formatComparisonTable()` to iterate `trans-s → trans-m → trans-l → storage-s …` using `BuildConfig::getExampleShip()`, falling back to `array_rand()` when no match is found.
- Changed `formatComparisonTable()` visibility from `private` to `protected` (enables subclass testing).

### WP-005 — PHPUnit Unit Tests
- Created `tests/CargoSizesModTests/ExampleShipSelectionTest.php` with 9 tests:
  - 4 tests for `FileCollection::getExampleShipDescription()` (format, deterministic pick, fallback, empty)
  - 5 tests for `ReleaseNotesGenerator::formatComparisonTable()` (format, math, deterministic pick, fallback, empty)
- All 56 PHPUnit tests pass; PHPStan 52/52 clean.

### WP-006 — Manifest Updates
- `public-api.md` v1.4 → v1.6: new `BuildConfig` constant/method, updated constructor signatures, new `FileCollection` static members.
- `constraints.md` v1.3 → v1.4: new section 4 "Build Configuration Keys" documenting all `build-config.json` top-level keys including `"example-ships"`.
- `data-flows.md` v1.3 → v1.4: both FOMOD and release notes flows updated to show the deterministic selection path with fallback.

---

## Files Modified

| File | Change |
|------|--------|
| `config/build-config.json` | Added `"example-ships"` object |
| `src/Mods/CargoSizesMod/Build/BuildConfig.php` | KEY_EXAMPLE_SHIPS, $exampleShips, getExampleShip() |
| `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` | BuildConfig param, setConfig() call, writeReleaseNotes() updated |
| `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php` | Pass self::getConfig() to CargoSizeExtractor |
| `src/Mods/CargoSizesMod/FOMOD/FileCollection.php` | setConfig(), $buildConfig, deterministic getExampleShipDescription(), reset() extended, invariant comment |
| `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php` | BuildConfig param, deterministic formatComparisonTable(), protected visibility |
| `tests/CargoSizesModTests/ExampleShipSelectionTest.php` | New — 9 unit tests |
| `docs/agents/project-manifest/public-api.md` | v1.6 — updated signatures |
| `docs/agents/project-manifest/constraints.md` | v1.4 — config keys section |
| `docs/agents/project-manifest/data-flows.md` | v1.4 — deterministic flow notes |

---

## Notable Implementation Decisions

1. **`@phpstan-ignore` annotation was used temporarily** in WP-002 on `FileCollection::$buildConfig` (write-only at that point) and correctly removed in WP-003 once the read path was added. PHPStan correctly flagged the stale annotation.

2. **`ReleaseNotesGenerator::formatComparisonTable()` changed from `private` to `protected`** to enable `TestableReleaseNotesGenerator` subclass in tests. This is a deliberate and accepted approach — PHP does not allow subclasses to call `private` methods via `$this`.

3. **`FileCollection::reset()` extended to clear `$buildConfig`** — this is strictly correct behavior (complete static reset) and is backward-compatible since production code always calls `setConfig()` immediately after `reset()`.

4. **9 PHPUnit Notices (`N` markers)** appear for the new tests in PHPUnit 12.5.11. Investigation confirms these are benign metadata notices (likely PHPUnit noticing the `TestableReleaseNotesGenerator` non-test class in the test file). All assertions pass and exit code is 0.

---

## Final State

- `composer build` ✅ exits 0
- `composer test` ✅ 56/56 pass
- `composer analyze` ✅ 52/52 clean
