# Plan

## Plan Audit Cycles
- Audits: none — Plan Auditor v1.5.0
- Architectural Reviews: none — Plan Architect Reviewer v1.6.0

## Summary
Extract the duplicated ship type definitions from the CLI's `CargoSizeExtractor` and the GUI's `ShipDataService` into a shared `config/ship-types.json` data file. Both systems will read this file as the single source of truth for ship type metadata (display labels, macro keyword mappings, aliases, and supported sizes), eliminating the synchronization contract and drift risk between the two codebases.

## Architectural Context

### Current State — Duplication

Ship type knowledge is duplicated across two independent codebases:

**CLI Build System** ([src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php](src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php)):
- `SHIP_TYPE_*` constants: `trans`, `storage`, `miner`, `resupplier`, `carrier`
- `SHIP_TYPES` array: maps constants → `{label, name, description}` (includes Translation keys)
- `SHIP_SIZES`: `['xs', 's', 'm', 'l', 'xl']`
- `resolveShipType()`: explodes macro name by `_`, matches parts against `SHIP_TYPES` keys
- `normalizeShipType()`: `trans`→`transport`, `storage`→`transport`, `miner`→`miner`, `resupplier`→`auxiliary`

**GUI Backend** ([gui/backend/src/Services/ShipDataService.php](gui/backend/src/Services/ShipDataService.php)):
- `SHIP_TYPE_*` constants: duplicates of the extractor's values
- `SHIP_TYPE_MAP`: `['transport'=>'trans', 'mining'=>'miner', 'auxiliary'=>'resupplier', 'carrier'=>'carrier']`
- `SHIP_SIZES`: duplicated `['xs', 's', 'm', 'l', 'xl']`
- `getShipTypes()`: returns hardcoded `[{type, label}]` array
- `determineShipType()`: same explode+match logic as `resolveShipType()`

**GUI Frontend** ([gui/frontend/src/types/ships.d.ts](gui/frontend/src/types/ships.d.ts)):
- `ShipType` union type: `'transport' | 'mining' | 'auxiliary' | 'carrier'` — hardcoded, must stay in sync with backend
- `ShipSize` union type: `'xs' | 's' | 'm' | 'l' | 'xl'` — hardcoded

### Existing Inconsistencies
- GUI uses `mining` as the display key; the extractor's `normalizeShipType()` returns `miner`
- GUI omits the `storage` type entirely (it normalizes to `transport` in the CLI)

### Config Pattern Precedent
The `config/` directory already houses `build-config.json` and `translations.json`, both read by the CLI build system. The GUI's `ConfigService` ([gui/backend/src/Services/ConfigService.php](gui/backend/src/Services/ConfigService.php)) reads `config/build-config.json` using `__DIR__ . '/../../../../config/build-config.json'`, establishing the relative-path pattern for shared config access.

## Approach / Architecture

Introduce `config/ship-types.json` as the single source of truth for ship type metadata. This file defines:

1. **Types** — the canonical display names, labels, and which macro keywords resolve to each type
2. **Aliases** — macro keywords that should be redirected to an existing type (e.g., `scavenger` → `transport`)
3. **Sizes** — the supported ship size categories

A new shared PHP class `ShipTypeConfig` is created at `src/Mods/CargoSizesMod/ShipTypeConfig.php` to load and query the JSON file. Both the CLI's `CargoSizeExtractor` and the GUI's `ShipDataService` consume this class instead of maintaining their own constants and resolution logic.

### Data File Shape

```json
{
  "types": {
    "transport": {
      "label": "Transport ships",
      "macroKeywords": ["trans", "storage"]
    },
    "mining": {
      "label": "Mining ships",
      "macroKeywords": ["miner"]
    },
    "auxiliary": {
      "label": "Auxiliaries",
      "macroKeywords": ["resupplier"]
    },
    "carrier": {
      "label": "Carriers",
      "macroKeywords": ["carrier"]
    }
  },
  "aliases": {
    "scavenger": "transport",
    "terraformer": "mining"
  },
  "sizes": ["xs", "s", "m", "l", "xl"]
}
```

### Integration Points

```
config/ship-types.json
        │
        ▼
  ShipTypeConfig (shared PHP class)
        │
   ┌────┴────┐
   ▼         ▼
 CLI         GUI
 (CargoSizeExtractor)  (ShipDataService)
```

- **`CargoSizeExtractor`** replaces `resolveShipType()` internals and `normalizeShipType()` with calls to `ShipTypeConfig`. Keeps `SHIP_TYPES` for translation key lookups only (translation keys are PHP-specific and do not belong in the shared JSON).
- **`ShipDataService`** removes all `SHIP_TYPE_*` constants, `SHIP_TYPE_MAP`, `SHIP_SIZES`, hardcoded `getShipTypes()`, and `determineShipType()` logic, replacing them with `ShipTypeConfig` calls.
- **Frontend TypeScript types** remain hardcoded union types (`ShipType`, `ShipSize`) since they are compile-time constructs. A comment documents they must match the JSON file. The runtime values are already derived from the backend API response, so type safety is maintained.

## Rationale

- **Single source of truth**: Adding new ship types or aliases becomes a config edit, not a code change in two places.
- **Eliminates drift**: The audit finding from the H & Barbarossa plan (GUI's `determineShipType()` not updated when CLI's `resolveShipType()` changes) becomes impossible — both read the same file.
- **Config-driven extensibility**: Future ship type additions (DLC ships, new game versions) require only a JSON edit.
- **Follows established pattern**: `config/build-config.json` and `config/translations.json` already exist; this extends the same convention.
- **Minimal disruption**: The `SHIP_TYPES` constant with its PHP-specific translation keys stays in the extractor. Only the shared/duplicated metadata moves to JSON.

## Considered Alternatives

| Decision | Chosen Shape | Alternatives Considered | Trade-Off Summary |
|----------|--------------|-------------------------|-------------------|
| Data file location | `config/ship-types.json` (existing config directory) | New `data/` directory; Embed in `build-config.json` | Config directory already used for shared build data; keeps separation of concerns between build tuning and type metadata. |
| Shared code layer | New `ShipTypeConfig` PHP class at project root `src/` | Let each consumer parse JSON independently; Put resolution logic in JSON (DSL) | A shared PHP class avoids duplicating JSON parsing/validation and provides typed API; avoids over-engineering a JSON DSL. |
| Translation keys | Keep in `CargoSizeExtractor::SHIP_TYPES` only | Move to JSON file; Create separate translation-config.json | Translation keys reference PHP `Translation` class constants — they are compile-time PHP dependencies and don't belong in a language-agnostic JSON file. |
| Frontend types | Keep as hardcoded TypeScript union types with doc comment | Generate from JSON at build time; Runtime validation against API | TypeScript union types are compile-time only; the actual values come from the API at runtime. Code generation adds toolchain complexity for minimal gain. |
| Normalization mapping | Implicit via `macroKeywords` array membership | Explicit `normalization` map in JSON | The `macroKeywords` array already encodes which internal keywords map to which display type, making a separate normalization map redundant. |

## Pattern Alignment

- **Shared Config Pattern** ([config/build-config.json](config/build-config.json)): Follows the existing convention of JSON files in `config/` consumed by both CLI and GUI via `ConfigService`'s relative-path resolution.
- **Service Class Pattern** ([gui/backend/src/Services/ConfigService.php](gui/backend/src/Services/ConfigService.php)): `ShipTypeConfig` follows the same sync-file-read pattern used by `ConfigService`.
- **Translation Pattern** ([src/Mods/CargoSizesMod/Translation.php](src/Mods/CargoSizesMod/Translation.php)): Translation keys intentionally remain in PHP code, not migrated to JSON, preserving the existing compile-time reference pattern.
- **Static Factory Pattern** ([docs/agents/project-manifest/tech-stack.md](docs/agents/project-manifest/tech-stack.md)): `ShipTypeConfig` uses a static singleton factory consistent with `ShipDefs::getInstance()`, `EngineDefs::getInstance()`.

## Detailed Steps

### Step 1: Create the shared data file `config/ship-types.json`

Create the JSON file with the shape defined above, containing the four ship types with their labels and macro keywords, the two aliases (`scavenger` → `transport`, `terraformer` → `mining`), and the sizes array.

### Step 2: Create `src/Mods/CargoSizesMod/ShipTypeConfig.php`

Create a new shared class that:
- Loads and caches `config/ship-types.json` via synchronous `file_get_contents()`
- Provides `resolveType(string $macroName): ?string` — explodes macro name, checks aliases first, then matches against all `macroKeywords` across types; returns the canonical type name (e.g., `transport`, `mining`) or `null`
- Provides `getTypes(): array` — returns `[{type: string, label: string}, ...]` from the JSON
- Provides `getSizes(): array` — returns the sizes array
- Provides `getMacroKeywordsForType(string $type): array` — returns the macro keywords for a given type
- Uses a static singleton pattern (`getInstance()`) with a configurable file path for testing

### Step 3: Refactor `CargoSizeExtractor` to use `ShipTypeConfig`

In [src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php](src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php):
- Replace `resolveShipType()` body: delegate to `ShipTypeConfig::getInstance()->resolveType($macroName)`, then map the returned canonical name back to the internal constant (e.g., `transport` → `self::SHIP_TYPE_TRANSPORT`). This reverse mapping is needed because the rest of the extractor (FOMOD builder, translation lookups, output folders) still operates on internal constants like `trans`.
- Replace `normalizeShipType()` body: delegate to `ShipTypeConfig` — look up which type owns the given internal keyword and return the canonical name.
- Replace `SHIP_SIZES` usage with `ShipTypeConfig::getInstance()->getSizes()`.
- Keep `SHIP_TYPE_*` constants and `SHIP_TYPES` array unchanged — they serve the translation key lookup which is PHP-specific.
- Keep `getShipTypesPretty()` and `getTypeLabel()` intact — they depend on `SHIP_TYPES` for translation metadata.

### Step 4: Refactor `ShipDataService` to use `ShipTypeConfig`

In [gui/backend/src/Services/ShipDataService.php](gui/backend/src/Services/ShipDataService.php):
- Remove `SHIP_TYPE_TRANSPORT`, `SHIP_TYPE_MINING`, `SHIP_TYPE_AUXILIARY`, `SHIP_TYPE_CARRIER` constants.
- Remove `SHIP_SIZES` constant.
- Remove `SHIP_TYPE_MAP` constant.
- Replace `getShipTypes()`: return `ShipTypeConfig::getInstance()->getTypes()`.
- Replace `determineShipType()`: delegate to `ShipTypeConfig::getInstance()->resolveType($shipId)`.
- Replace `extractShipSize()`: use `ShipTypeConfig::getInstance()->getSizes()` instead of `self::SHIP_SIZES`.
- Update `getShipsByType()` validation: use `ShipTypeConfig::getInstance()->getTypes()` to validate the type parameter instead of checking `self::SHIP_TYPE_MAP`.
- Accept an optional `ShipTypeConfig` constructor parameter for testability.

### Step 5: Update the `ShipDataService` test suite

In [gui/backend/tests/Unit/Services/ShipDataServiceTest.php](gui/backend/tests/Unit/Services/ShipDataServiceTest.php):
- Update any tests that reference the removed constants or rely on the hardcoded `SHIP_TYPE_MAP`.
- Add a test that verifies `getShipTypes()` returns the types from the JSON config.
- Add a test that verifies scavenger/terraformer macros are classified correctly via the shared config.

### Step 6: Create `ShipTypeConfig` unit tests

Create `tests/CargoSizesModTests/ShipTypeConfigTest.php`:
- Test that `resolveType()` correctly maps standard macro keywords (e.g., macro containing `trans` → `transport`).
- Test that aliases work (macro containing `scavenger` → `transport`, `terraformer` → `mining`).
- Test that `getTypes()` returns the expected type list.
- Test that `getSizes()` returns the expected sizes.
- Test that an unknown macro keyword returns `null`.
- Use a test fixture JSON file or the real `config/ship-types.json`.

### Step 7: Update frontend type documentation

In [gui/frontend/src/types/ships.d.ts](gui/frontend/src/types/ships.d.ts):
- Add a comment to `ShipType` and `ShipSize` stating they must match `config/ship-types.json`.
- No code changes needed — the runtime values come from the API.

## Dependencies
- The H & Barbarossa plan (`2026-06-12-add-h-and-barbarossa`) should consume this plan's output: instead of adding alias logic in `resolveShipType()` and `determineShipType()` independently, the aliases will be defined in `config/ship-types.json` and both systems pick them up automatically.
- No external library dependencies.

## Required Components
- `config/ship-types.json` (NEW — shared data file)
- `src/Mods/CargoSizesMod/ShipTypeConfig.php` (NEW — shared config loader class)
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` (MODIFY — delegate to ShipTypeConfig)
- `gui/backend/src/Services/ShipDataService.php` (MODIFY — delegate to ShipTypeConfig, remove duplicated constants)
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` (MODIFY — update for new config-based approach)
- `tests/CargoSizesModTests/ShipTypeConfigTest.php` (NEW — unit tests for shared config class)
- `gui/frontend/src/types/ships.d.ts` (MODIFY — add documentation comment)

## Assumptions
- The `config/` directory is accessible at a known relative path from both the CLI entry point and the GUI backend's `Services/` directory.
- The `ShipTypeConfig` class can use the same `__DIR__`-based relative path pattern established by `ConfigService`.
- Composer autoloading covers `src/Mods/CargoSizesMod/ShipTypeConfig.php` via the existing PSR-4 mapping.
- The GUI frontend's TypeScript types remain compile-time constructs; they are not generated from the JSON file.

## Constraints
- File I/O must remain strictly synchronous (per `constraints.md`).
- No database connections.
- `strict_types` required in all PHP files.
- `SHIP_TYPES` with translation keys must remain in `CargoSizeExtractor` — translation keys reference PHP class constants and cannot move to JSON.

## Out of Scope
- Generating TypeScript types from the JSON file at build time (not worth the toolchain complexity).
- Moving translation keys out of PHP code into JSON.
- Changing the FOMOD builder's consumption of `SHIP_TYPES` — it continues to use the PHP constant for translation metadata.
- Renaming the GUI's `mining` display key to `miner` or vice versa (the JSON establishes `mining` as canonical, and `miner` as a macro keyword under it).

## Acceptance Criteria
- `config/ship-types.json` exists and contains all four ship types, their labels, macro keywords, aliases, and sizes.
- `ShipTypeConfig` class loads the JSON and provides typed access to types, aliases, sizes, and type resolution.
- `CargoSizeExtractor::resolveShipType()` delegates to `ShipTypeConfig` for type resolution.
- `CargoSizeExtractor::normalizeShipType()` delegates to `ShipTypeConfig` for normalization.
- `ShipDataService` has zero duplicated ship type constants, maps, or resolution logic.
- `ShipDataService::getShipTypes()` returns data derived from the JSON config.
- `ShipDataService::determineShipType()` delegates to `ShipTypeConfig`.
- `composer test` passes for both CLI and GUI test suites.
- `composer build` produces identical output as before (no behavioral change).
- Adding a new alias to `config/ship-types.json` is sufficient to make both CLI and GUI recognize a new ship keyword — no PHP code changes required.

## Testing Strategy
Testing validates that the refactoring produces identical behavior to the current implementation, plus verifies the new config-driven extensibility (aliases). The `ShipTypeConfig` class gets direct unit tests. Both `CargoSizeExtractor` and `ShipDataService` are tested indirectly through their existing test suites, which must continue to pass unchanged. New tests specifically exercise the alias resolution path.

## Test Plan
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveTransportMacro` — macro containing `trans` resolves to `transport` — Acceptance: type resolution delegation
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveStorageMacro` — macro containing `storage` resolves to `transport` — Acceptance: storage→transport normalization preserved
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveMinerMacro` — macro containing `miner` resolves to `mining` — Acceptance: type resolution delegation
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveAuxiliaryMacro` — macro containing `resupplier` resolves to `auxiliary` — Acceptance: type resolution delegation
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveCarrierMacro` — macro containing `carrier` resolves to `carrier` — Acceptance: type resolution delegation
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveScavengerAlias` — macro containing `scavenger` resolves to `transport` — Acceptance: alias resolution
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveTerraformerAlias` — macro containing `terraformer` resolves to `mining` — Acceptance: alias resolution
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveUnknownReturnsNull` — macro with no matching keyword returns `null` — Acceptance: type resolution delegation
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testGetTypes` — returns all four types with labels — Acceptance: config-driven types
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testGetSizes` — returns `['xs','s','m','l','xl']` — Acceptance: config-driven sizes
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` — existing tests pass unchanged — Acceptance: no behavioral regression
- Existing CLI test suite (`composer test`) — all pass — Acceptance: no behavioral regression

## Documentation Updates
- `docs/agents/project-manifest/tech-stack.md` — Add `ShipTypeConfig` class and the Shared Config Pattern to the architectural patterns section.
- `docs/agents/project-manifest/public-api.md` — Add `ShipTypeConfig` public API (static factory, `resolveType()`, `getTypes()`, `getSizes()`, `getMacroKeywordsForType()`).
- `docs/agents/project-manifest/file-tree.md` — Add `config/ship-types.json` and `src/Mods/CargoSizesMod/ShipTypeConfig.php`.
- `docs/agents/project-manifest/data-flows.md` — Update the Ship Type Resolution flow to show both CLI and GUI reading from `config/ship-types.json` via `ShipTypeConfig`.
- `docs/agents/project-manifest/constraints.md` — Add a rule that ship type metadata must be defined in `config/ship-types.json`, not hardcoded in PHP.
- `gui/docs/project-manifest/public-api.md` — Remove the duplicated constants section from `ShipDataService`; document dependency on `ShipTypeConfig`.
- `gui/docs/project-manifest/data-flows.md` — Update ship type resolution flow to reference `ShipTypeConfig`.
- `changelog.md` — Add entry for shared ship type configuration refactoring.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **JSON file not found at runtime** | `ShipTypeConfig` throws a clear exception with the expected path. Constructor accepts a custom path for testing. |
| **Behavioral regression in type resolution** | Comprehensive unit tests for `ShipTypeConfig` cover all existing keyword→type mappings. Existing test suites must pass unchanged. |
| **GUI backend cannot resolve relative path to config/** | Uses the same `__DIR__ . '/../../../../config/'` pattern proven by `ConfigService`. |
| **Translation key lookups break** | `SHIP_TYPES` constant stays in `CargoSizeExtractor` unchanged — translation logic is unaffected. |
| **Performance: JSON parsed on every request** | `ShipTypeConfig` uses a static singleton with in-memory cache — file is read once per process. |
| **Circular dependency between CLI and GUI via shared class** | `ShipTypeConfig` lives in the CLI's `src/` namespace. GUI already depends on CLI code via Composer. No new dependency direction introduced. |
