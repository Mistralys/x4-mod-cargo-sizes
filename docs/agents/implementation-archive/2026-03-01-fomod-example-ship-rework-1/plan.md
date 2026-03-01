# Plan

## Summary

This rework plan addresses all three strategic recommendations surfaced in the `2026-03-01-fomod-example-ship` synthesis report. The primary deliverable is a **deterministic ship selection system** for both the FOMOD plugin example ship text and the release notes comparison table, driven by a new `"example-ships"` section in `build-config.json`. The secondary deliverable is **PHPUnit unit test coverage** for `getExampleShipDescription()` and `formatComparisonTable()`, which currently have no unit-level tests. The tertiary deliverable is a **code comment** in `FileCollection.php` documenting the implicit invariant relied upon by the "Unchanged" option graceful fallback.

---

## Architectural Context

### Affected Components

| Component | File | Role |
|-----------|------|------|
| `BuildConfig` | `src/Mods/CargoSizesMod/Build/BuildConfig.php` | Reads `config/build-config.json`; exposes typed config to the build system |
| `CargoSizeBuildTools` | `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php` | Top-level entry point; constructs `CargoSizeExtractor` and provides `BuildConfig` singleton |
| `CargoSizeExtractor` | `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` | Orchestrates the full build; constructs `ReleaseNotesGenerator`; the `FileCollection` static instances are populated during extraction |
| `FileCollection` | `src/Mods/CargoSizesMod/FOMOD/FileCollection.php` | Static-factory class holding FOMOD plugin definitions per (type × size × multiplier); `getExampleShipDescription()` currently uses `array_rand()` |
| `ReleaseNotesGenerator` | `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php` | Generates vers-numbered `build/release-notes-vX.md`; `formatComparisonTable()` currently uses `array_rand()` |
| `build-config.json` | `config/build-config.json` | Runtime configuration file; holds multipliers and flight-mechanics tuning knob |

### Key Integration Points

- `CargoSizeBuildTools::build()` calls `self::getConfig()` (a `BuildConfig` singleton) and passes only the multiplier array to `CargoSizeExtractor::extract()`. The `BuildConfig` object itself is **not** currently threaded through to the extractor.
- `FileCollection` uses a static registry (`self::$instances`). Ship selection logic in `getExampleShipDescription()` operates on the `$this->files` array (populated via `addFile()`), which contains `StorageOverrideFile` instances whose `getShipName()` returns `ShipResult::getShipLabel()`.
- `ReleaseNotesGenerator` receives `FolderInfo`, `float[]` multipliers, and `ShipResult[]` in its constructor. It has no current access to `BuildConfig`.
- `CargoSizeExtractor::writeReleaseNotes()` constructs `ReleaseNotesGenerator` directly; the extractor currently receives no `BuildConfig` reference.

### Existing Static Pattern in `FileCollection`

`FileCollection` maintains `private static array $instances` and a `public static function reset(): void`. Adding a `private static ?BuildConfig $buildConfig = null` and `public static function setConfig(BuildConfig $config): void` is consistent with this pattern and avoids changing the `getPluginDescription()` signature (which is called from `FomodWriter.php:308`).

---

## Approach / Architecture

### Item 1 — Deterministic Ship Selection

#### Config Schema (new `"example-ships"` key)

Add to `config/build-config.json`:

```json
"example-ships": {
    "trans-s":       "Courier Sentinel",
    "trans-m":       "Demeter Sentinel",
    "trans-l":       "Shuyaku Sentinel",
    "miner-s":       "Magpie",
    "miner-m":       "Manorina Sentinel",
    "miner-l":       "Magnetar Sentinel",
    "carrier-xl":    "Nomad Sentinel",
    "resupplier-xl": "Condor Sentinel"
}
```

The key format is `{normalizedShipType}-{size}` (lowercase), matching the existing patterns used by `FileCollection::create()` and the `SHIP_TYPES` / `SHIP_SIZES` constants in `CargoSizeExtractor`.

#### `BuildConfig` extension

- New constant: `public const string KEY_EXAMPLE_SHIPS = 'example-ships';`
- New private property: `private array $exampleShips = [];` (`array<string, string>`)
- Constructor reads the `"example-ships"` object from JSON into `$exampleShips`. No validation required (the key is optional; an absent or empty config simply means fallback to random selection).
- New public method: `public function getExampleShip(string $shipType, string $shipSize): string` — builds the lookup key `{shipType}-{shipSize}` and returns the configured ship label, or empty string if not set.

#### Threading `BuildConfig` to `CargoSizeExtractor`

`CargoSizeExtractor` currently receives only two folder arguments. Add `BuildConfig $buildConfig` as a **third required constructor parameter**. Update `CargoSizeBuildTools::build()` to pass `self::getConfig()`.

Within `CargoSizeExtractor::extract()` (or the earliest point before FOMOD files are created), call `FileCollection::setConfig($this->buildConfig)` so the static example-ship lookup is available for the duration of the build.

#### `FileCollection` — deterministic ship selection

1. Add `private static ?BuildConfig $buildConfig = null;`
2. Add `public static function setConfig(BuildConfig $config): void` that sets `self::$buildConfig`.
3. Update `getExampleShipDescription()`:
   - If `self::$buildConfig !== null`, call `self::$buildConfig->getExampleShip($this->shipType, $this->shipSize)`.
   - If the returned label is non-empty, search `$storageFiles` for a `StorageOverrideFile` whose `getShipName()` matches that label.
   - If a match is found, use it as `$example` (bypassing `array_rand()`).
   - If no match is found (e.g., the configured ship is absent from this build's game data), fall back silently to `array_rand()`.

#### `ReleaseNotesGenerator` — deterministic transport ship for comparison table

1. Add `BuildConfig $buildConfig` as a fourth constructor parameter.
2. Update `CargoSizeExtractor::writeReleaseNotes()` to pass `$this->buildConfig`.
3. Update `formatComparisonTable()`:
   - Try each transport type (`SHIP_TYPE_TRANSPORT`, `SHIP_TYPE_STORAGE`) and each size (`s`, `m`, `l`) in order, calling `$this->buildConfig->getExampleShip($type, $size)`.
   - For the first non-empty label found, search `$transportShips` for a `ShipResult` whose `getShipLabel()` matches.
   - If a match is found, use it deterministically. Otherwise fall back to `array_rand()`.

---

### Item 2 — PHPUnit Unit Tests

**New test file:** `tests/CargoSizesModTests/ExampleShipSelectionTest.php`

All tests use PHPUnit mock objects for `ShipResult`, `StorageOverrideFile`, and `BuildConfig` — no real XML game files are required.

#### Tests for `FileCollection::getExampleShipDescription()`

| Test | Scenario | Assertion |
|------|----------|-----------|
| `testExampleShipDescriptionFormat` | One `StorageOverrideFile` in collection, cargo 1000, multiplier ×4 (adjusted 4000) | Returned string contains ship name, "1,000", "4,000" |
| `testExampleShipDescriptionDeterministicSelectionPicksConfiguredShip` | Two `StorageOverrideFile` entries (`ShipA`, `ShipB`), config sets ship to `ShipB` | String contains `ShipB`, not `ShipA` |
| `testExampleShipDescriptionFallbackWhenConfiguredShipNotFound` | Config names `ShipC` but only `ShipA` exists in collection | Returns non-empty string (graceful fallback to `array_rand()` — any ship) |
| `testExampleShipDescriptionEmptyWhenNoStorageFiles` | No `StorageOverrideFile` in collection | Returns `''` |

#### Tests for `ReleaseNotesGenerator::formatComparisonTable()` (via a test-accessible wrapper)

Since `formatComparisonTable()` is private, expose it for testing either by making it package-visible (changing `private` to `protected`) or by creating a `TestableReleaseNotesGenerator` subclass in the test file that re-exposes the method as public. The subclass approach is preferred as it requires no production-code visibility change.

| Test | Scenario | Assertion |
|------|----------|-----------|
| `testComparisonTableFormat` | One transport `ShipResult`, two multipliers | Table header row present; two data rows with correct ship name |
| `testComparisonTableMath` | Ship with cargo 1000, multipliers [2, 4] | Row ×2 shows "2,000"; row ×4 shows "4,000" |
| `testComparisonTableDeterministicSelectionPicksConfiguredShip` | Two transport ships (`ShipA` M-size, `ShipB` S-size), config sets `trans-s` = `ShipB` | Table uses `ShipB` |
| `testComparisonTableFallbackWhenConfiguredShipNotFound` | Config names `ShipC`, only `ShipA` exists | Table still generated with `ShipA` (graceful fallback) |
| `testComparisonTableEmptyWhenNoTransportShips` | All `ShipResult` entries are miner type | Returns `''` |

---

### Item 3 — "Unchanged" Option Code Comment

Add a PHPDoc comment block (or inline comment) directly above the `if (empty($storageFiles)) { return ''; }` guard in `getExampleShipDescription()` explaining:

> The "Unchanged" plugin option for each FOMOD step contains no `StorageOverrideFile` entries — it is a no-op that does not modify any game files. This means the `empty($storageFiles)` guard naturally produces an empty string for those options. This invariant must be preserved: if an "Unchanged" option were ever to gain `StorageOverrideFile` entries, it would incorrectly show example ship text in the FOMOD installer description.

---

## Rationale

- **Static `setConfig()` on `FileCollection`** — `FileCollection` already uses static state for its instance registry. Adding a second static property for config is consistent, avoids changing method signatures, and requires no changes to `FomodWriter.php`.
- **`BuildConfig` as required constructor param on `CargoSizeExtractor`** — threading it through the constructor makes the dependency explicit and avoids global state. The only caller (`CargoSizeBuildTools::build()`) already has `self::getConfig()` available.
- **Named per-type-size config keys over a single boolean flag** — the synthesis suggested a `"deterministicShipSelection": true` flag (alphabetically first). The user's request specifies exact ships by type+size, which requires named mappings. This approach is strictly more expressive and includes the boolean-flag use case (by setting all keys).
- **Graceful fallback to `array_rand()`** — if a configured ship label doesn't appear in the current build's game data (e.g. the ship is DLC-locked and the DLC is not installed), the build still succeeds and produces a valid (random) example.
- **`TestableReleaseNotesGenerator` subclass for testing** — avoids changing the `private` visibility of `formatComparisonTable()` in production code, keeping the encapsulation intact.

---

## Detailed Steps

1. **Update `config/build-config.json`** — add the `"example-ships"` object with the eight user-specified type-size → ship-label mappings.

2. **Extend `BuildConfig`** — add `KEY_EXAMPLE_SHIPS` constant, `$exampleShips` property, constructor logic to read the optional JSON key, and `getExampleShip(string $shipType, string $shipSize): string` method.

3. **Update `CargoSizeExtractor` constructor** — add `BuildConfig $buildConfig` as third parameter; store as `private BuildConfig $buildConfig`; call `FileCollection::setConfig($this->buildConfig)` at the start of `extract()`.

4. **Update `CargoSizeBuildTools::build()`** — pass `self::getConfig()` as the third argument to the `CargoSizeExtractor` constructor.

5. **Update `FileCollection`** — add `private static ?BuildConfig $buildConfig`, `public static function setConfig(BuildConfig $config): void`, and deterministic-first lookup logic in `getExampleShipDescription()`.

6. **Update `ReleaseNotesGenerator`** — add `BuildConfig $buildConfig` as fourth constructor parameter; update `formatComparisonTable()` to prefer configured ship with graceful fallback.

7. **Update `CargoSizeExtractor::writeReleaseNotes()`** — pass `$this->buildConfig` as the fourth argument to `new ReleaseNotesGenerator(...)`.

8. **Add "Unchanged" invariant comment** — insert explanatory comment inside `FileCollection::getExampleShipDescription()` above the empty-guard return.

9. **Write unit tests** — create `tests/CargoSizesModTests/ExampleShipSelectionTest.php` with all tests from the Testing Strategy section.

10. **Update manifest documents** — update `public-api.md` (new `BuildConfig` members, updated `CargoSizeExtractor` constructor, updated `FileCollection` and `ReleaseNotesGenerator` signatures), `constraints.md` (document `"example-ships"` config key), and `data-flows.md` (update FOMOD and release notes flows to reflect deterministic selection path).

---

## Dependencies

- No new Composer packages required.
- `BuildConfig` (`src/Mods/CargoSizesMod/Build/BuildConfig.php`) must be updated before `FileCollection` or `ReleaseNotesGenerator` can consume it.
- `CargoSizeExtractor` constructor update must be done together with the `CargoSizeBuildTools` caller update to avoid breaking the build.

---

## Required Components

### Modified Files

| File | Change |
|------|--------|
| `config/build-config.json` | Add `"example-ships"` section |
| `src/Mods/CargoSizesMod/Build/BuildConfig.php` | New const, property, and method |
| `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` | New constructor parameter; `FileCollection::setConfig()` call; pass config to `ReleaseNotesGenerator` |
| `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php` | Pass `self::getConfig()` to `CargoSizeExtractor` constructor |
| `src/Mods/CargoSizesMod/FOMOD/FileCollection.php` | Static `$buildConfig` property, `setConfig()` method, deterministic lookup in `getExampleShipDescription()`, invariant comment |
| `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php` | New constructor parameter; deterministic lookup in `formatComparisonTable()` |
| `docs/agents/project-manifest/public-api.md` | Updated signatures for `BuildConfig`, `CargoSizeExtractor`, `FileCollection`, `ReleaseNotesGenerator` |
| `docs/agents/project-manifest/constraints.md` | Document `"example-ships"` config key |
| `docs/agents/project-manifest/data-flows.md` | Updated FOMOD and release notes flows |

### New Files

| File | Purpose |
|------|---------|
| `tests/CargoSizesModTests/ExampleShipSelectionTest.php` | PHPUnit unit tests for example-ship selection in `FileCollection` and `ReleaseNotesGenerator` |

---

## Assumptions

- The ship names provided by the user (`Courier Sentinel`, `Demeter Sentinel`, etc.) match exactly the string returned by `ShipResult::getShipLabel()` for those ships in the extracted game data. Case-sensitive match.
- The `"example-ships"` config key is optional — if absent, the build behaves exactly as before (random selection).
- `BuildConfig` is not unit-tested directly; the existing integration path through `CargoSizeBuildTools` provides sufficient coverage.
- `formatComparisonTable()` picks the first matching configured transport ship it finds across all transport sizes (iterating `s` → `m` → `l`). With the provided config, this means `Courier Sentinel` (Transport S) will be used. If a different ship is desired for the table, the user can reorder the config or we document the iteration order.

---

## Constraints

- All PHP files must have `declare(strict_types=1)`.
- No async I/O.
- All new public methods must be documented in `public-api.md` before the plan is considered done.
- Do not change the visibility of `formatComparisonTable()` in production code; use the `TestableReleaseNotesGenerator` subclass technique for testing.
- The `getPluginDescription()` method signature in `FileCollection` must not change (called by `FomodWriter.php:308`).

---

## Out of Scope

- Changing the comparison table to show _multiple_ ships per multiplier row.
- Adding a UI/GUI control for the `"example-ships"` config in the Physics Tuning GUI (separate system, separate manifest).
- Adding an explicit `isUnchangedOption()` method to `FileCollection` (the comment approach from item 3 is sufficient; a full guard method is a separate future refactor).
- Re-running a full integration build as part of this plan — the implementation agent should run `composer test` and `composer analyze` but a full `composer build` (which requires game data) is the operator's responsibility.

---

## Acceptance Criteria

- [ ] `config/build-config.json` contains the `"example-ships"` section with all eight specified ships.
- [ ] `BuildConfig::getExampleShip('trans', 'm')` returns `"Demeter Sentinel"`.
- [ ] `BuildConfig::getExampleShip('miner', 'l')` returns `"Magnetar Sentinel"`.
- [ ] `BuildConfig::getExampleShip('carrier', 'xl')` returns `"Nomad Sentinel"`.
- [ ] `BuildConfig::getExampleShip('resupplier', 'xl')` returns `"Condor Sentinel"`.
- [ ] `BuildConfig::getExampleShip('trans', 'xs')` returns `""` (unconfigured size).
- [ ] When a matching `StorageOverrideFile` is found in `FileCollection`, `getExampleShipDescription()` always uses the configured ship (not `array_rand()`).
- [ ] When the configured ship is absent from the collection, `getExampleShipDescription()` returns a non-empty string (fallback to any available ship).
- [ ] `ReleaseNotesGenerator::formatComparisonTable()` uses the configured transport ship when available.
- [ ] All new acceptance criteria above are covered by PHPUnit unit tests.
- [ ] `tests/CargoSizesModTests/ExampleShipSelectionTest.php` exists with ≥ 9 test methods (4 for `FileCollection` + 5 for `ReleaseNotesGenerator`).
- [ ] `composer test` exits 0 with all tests passing.
- [ ] `composer analyze` exits 0 with 0 PHPStan errors.
- [ ] `FileCollection::getExampleShipDescription()` contains an inline comment explaining the "Unchanged" option invariant.
- [ ] `public-api.md`, `constraints.md`, and `data-flows.md` are updated to reflect all changes.

---

## Testing Strategy

All new logic is covered at the **unit level** using PHPUnit mock objects. No game data files are required for any test.

- `FileCollection` tests instantiate the class via its static `create()` factory, add PHPUnit mock `StorageOverrideFile` objects, optionally call `FileCollection::setConfig()` with a `BuildConfig` configured to return specific ship labels, then assert on `getPluginDescription()` output.
- `ReleaseNotesGenerator` tests use a `TestableReleaseNotesGenerator` subclass (defined inside the test file) that re-exposes `formatComparisonTable()` as `public`, enabling direct invocation. Ship data is provided via PHPUnit mock `ShipResult` objects.
- `BuildConfig` reading from `build-config.json` is tested implicitly (the real file is read in `testExampleShipDescriptionDeterministicSelectionPicksConfiguredShip` if `FileCollection::setConfig(new BuildConfig())` is used) or a stub `BuildConfig` can be created via `createMock(BuildConfig::class)` to isolate tests from the JSON file.

The existing 47 PHPUnit tests must continue to pass with no regressions.

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Ship name mismatch** — configured name doesn't match `getShipLabel()` at runtime | Graceful fallback to `array_rand()` means the build never fails; operator sees a random ship instead. Document the exact expected label format in `constraints.md`. |
| **`CargoSizeExtractor` constructor change breaks custom calling code** | Only one caller exists (`CargoSizeBuildTools::build()`). Update it atomically with the extractor change. |
| **Static `BuildConfig` on `FileCollection` carries state between test runs** | Add `FileCollection::reset()` call (already exists) in test `setUp()` or `tearDown()` to clear static state between tests. Also add `FileCollection::setConfig()` teardown to null out the static config. |
| **`formatComparisonTable()` visibility change required for testing** | Mitigated by `TestableReleaseNotesGenerator` subclass technique — no production-code visibility change needed. |
| **`trans-s` picked over `trans-m` for comparison table** | The user-specified config lists Transport S first; iteration order guarantees `Courier Sentinel` is used for the table. If the user prefers Transport M, the config ordering can be reordered (or iteration can be changed to prefer M-size). Document clearly in the plan note. |
