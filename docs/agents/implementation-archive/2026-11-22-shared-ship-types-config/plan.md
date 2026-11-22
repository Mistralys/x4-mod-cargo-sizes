# Plan

## Plan Audit Cycles
- Audits: 1 (Sonnet 5.5 ×1) — Plan Auditor v1.5.0
- Architectural Reviews: 1 (Sonnet 5.5 ×1) — Plan Architect Reviewer v1.6.0

## Prior Project Context
The `2026-06-12-add-h-and-barbarossa` plan has shipped (see its `synthesis.md`). It added the Barbarossa (`scavenger`) and Xenon H (`terraformer`) keyword aliases as hardcoded `in_array` checks, separately in `CargoSizeExtractor::resolveShipType()` and `ShipDataService::determineShipType()`. It also added a guard that skips pure alias macros, and a characterization test (`tests/CargoSizesModTests/ShipTypeResolutionTest.php`). Its synthesis lists three code insights, and this plan resolves all of them:

| Synthesis item | Resolved by |
|---|---|
| [medium] debt — alias table hardcoded as literals in `resolveShipType()` | Steps 1–3: the aliases move to `config/ship-types.json` |
| [low] convention — `determineShipType()` drift contract has no enforcement | Steps 2 and 5: both layers use `ShipTypeConfig`, so there is no second copy left to drift |
| [low] improvement — non-ASCII dash in the alias skip message gives Windows console mojibake | Step 4 |

## Summary
Move the ship type definitions duplicated between the CLI's `CargoSizeExtractor` and the GUI's `ShipDataService` (keyword matching, keyword aliases, display labels) into a shared `config/ship-types.json` file, read through a new `ShipTypeConfig` class. The fixed size set (`xs`..`xl`) becomes a single shared constant on that class. This removes the two hardcoded alias checks added by the H & Barbarossa plan and the synchronization contract between the two layers. It also clears the remaining H & Barbarossa debt items, while keeping the CLI's build output byte-identical.

## Architectural Context

### Current State — Duplication

**CLI Build System** (`src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`, namespace `Mistralys\X4\Mods\CargoSizesMod`):
- `SHIP_TYPE_*` constants (L44–48): `trans`, `storage`, `miner`, `resupplier`, `carrier`.
- `SHIP_SIZES` (L57): `['xs', 's', 'm', 'l', 'xl']`, used at L400, L677 and L715, and in `src/Mods/CargoSizesMod/FOMOD/FomodWriter.php` L178 and `src/Mods/CargoSizesMod/XML/ShipXMLFile.php` L43.
- `SHIP_TYPES` (L71–96): maps the internal constants to translation metadata, in the order trans, storage, miner, resupplier, carrier.
- `resolveShipType()` (L536–553):
  1. **Hardcoded keyword aliases** (L540–548): `scavenger` → `SHIP_TYPE_TRANSPORT`, `terraformer` → `SHIP_TYPE_MINER`.
  2. `array_find` over the `SHIP_TYPES` keys, returning the **matched internal keyword**, so storage ships resolve to `storage` and not to `trans`.
- `normalizeShipType()` (L521–534): output naming. `trans`/`storage`→`transport`, `miner`→`miner`, `resupplier`→`auxiliary`, anything else unchanged. Its values feed the FOMOD folder names (`transport_*`, `miner_*`) and the `example-ships` keys in `config/build-config.json` (e.g. `"miner-m"`).
- `analyzeShipMacro()` L577–584: the H & Barbarossa **pure alias macro guard**, which skips macros with an `alias=` XML attribute. This is not related to ship types and stays in place. Its console message at L582 contains a non-ASCII en-dash.

**GUI Backend** (`gui/backend/src/Services/ShipDataService.php`):
- `SHIP_TYPE_*` constants, `SHIP_TYPE_MAP` (`transport`→`trans`, `mining`→`miner`, `auxiliary`→`resupplier`, `carrier`→`carrier`) and `SHIP_SIZES` (L31, used at L280, L312, L431).
- `getShipTypes()`: returns a hardcoded `[{type, label}]` array.
- `determineShipType()` (L330–360): **hardcoded keyword aliases** (L341–349: `scavenger`→`transport`, `terraformer`→`mining`), then a loop over `SHIP_TYPE_MAP`. Its docblock claims it "Uses the same logic as CargoSizeExtractor::resolveShipType()", and nothing enforces that.

**GUI Frontend** (`gui/frontend/src/types/ships.d.ts`): hardcoded `ShipType` and `ShipSize` union types.

### Existing Inconsistencies
- The GUI's canonical name is `mining`. The CLI's output name (`normalizeShipType()`) is `miner`.
- The GUI has no `storage` keyword, so storage macros resolve to `null` there. In the CLI they resolve to `storage`, which outputs as `transport`.

### Terminology
Two unrelated things are called "alias" in this codebase:
- **Keyword aliases**: a macro name part such as `scavenger` that is sent to an existing ship type. These are what this plan moves into JSON.
- **Alias macros**: XML macros with an `alias=` attribute, skipped by the guard in `analyzeShipMacro()`. This plan does not touch them, except for the message text fix in Step 4.

The JSON key is named `keywordAliases` so the two don't get confused.

### Config Pattern Precedent
`config/` already holds `build-config.json` and `translations.json`. The GUI's `ConfigService` (`gui/backend/src/Services/ConfigService.php`) reads `config/build-config.json` through a `__DIR__`-relative path.

## Approach / Architecture

Introduce `config/ship-types.json` as the single source of truth, and a shared `ShipTypeConfig` class (NEW, `src/Mods/CargoSizesMod/ShipTypeConfig.php`) that loads it and resolves macro names. The class has two resolution methods, one per layer:

- `resolveType(string $macroName): ?string` returns the **canonical type** (`transport`, `mining`, `auxiliary`, `carrier`). The GUI uses this.
- `resolveMacroKeyword(string $macroName): ?string` returns the **internal macro keyword** (`trans`, `storage`, `miner`, `resupplier`, `carrier`). The CLI uses this. A keyword alias resolves to the explicit **`primaryKeyword`** of its target type, so `scavenger` → `trans` and `terraformer` → `miner`, which matches today's output exactly.

Sizes are not data: they are a closed, game-defined set that the macro names, the FOMOD step layout and the frontend `ShipSize` union all assume. They live as `public const array SIZES` on `ShipTypeConfig`, with no file I/O, which is the one shared source for both layers.

Both methods use the same matching rules: split on `_`, check `keywordAliases` first, then go through the types and their `macroKeywords` in JSON document order. The JSON order (trans, storage, miner, resupplier, carrier) matches the current `SHIP_TYPES` order, so precedence does not change.

### Data File Shape

```json
{
  "types": {
    "transport": {
      "label": "Transport ships",
      "primaryKeyword": "trans",
      "macroKeywords": ["trans", "storage"]
    },
    "mining": {
      "label": "Mining ships",
      "primaryKeyword": "miner",
      "macroKeywords": ["miner"]
    },
    "auxiliary": {
      "label": "Auxiliaries",
      "primaryKeyword": "resupplier",
      "macroKeywords": ["resupplier"]
    },
    "carrier": {
      "label": "Carriers",
      "primaryKeyword": "carrier",
      "macroKeywords": ["carrier"]
    }
  },
  "keywordAliases": {
    "scavenger": "transport",
    "terraformer": "mining"
  }
}
```

Each type's `primaryKeyword` is explicit and validated at load time to be a member of that type's `macroKeywords`, so array order carries no hidden meaning for the CLI output contract. The rule is documented in `constraints.md` (Step 9).

### Integration Points

```
config/ship-types.json
        │
        ▼
  ShipTypeConfig (shared PHP class)
        │
   ┌────┴──────────────────────┐
   ▼                           ▼
 CLI: CargoSizeExtractor       GUI: ShipDataService
   resolveMacroKeyword()         resolveType()
   SIZES (const)                 getTypes() / SIZES (const)
```

- **`CargoSizeExtractor`**: `resolveShipType()` delegates to `resolveMacroKeyword()`, and the hardcoded alias checks are deleted. `SHIP_SIZES` is removed and all five consumers reference `ShipTypeConfig::SIZES`. `SHIP_TYPE_*`, `SHIP_TYPES` (translation metadata) and `normalizeShipType()` (output naming, see Structural Improvements) stay.
- **`ShipDataService`**: all duplicated constants, the hardcoded type list and the alias checks are removed, and the service delegates to `ShipTypeConfig`.
- **Frontend types** stay hardcoded, with a comment pointing to the JSON file.

## Rationale

- **Single source of truth**: a new ship class (like Barbarossa or H) becomes a one-line JSON edit picked up by both layers. The H & Barbarossa plan needed two parallel code edits.
- **Eliminates drift**: the documented-but-unenforced contract between `determineShipType()` and `resolveShipType()` goes away, because there is no longer a second implementation.
- **Byte-identical CLI output**: returning internal keywords from `resolveMacroKeyword()` keeps `storage` distinct from `trans` and keeps `scavenger`→`trans` / `terraformer`→`miner`. An earlier draft of this plan reverse-mapped the canonical type back to a single constant, which would have merged storage ships into `trans`.
- **Follows established pattern**: JSON in `config/`, read synchronously, as with `build-config.json`.
- **Sizes as a constant, not data**: a runtime JSON field would suggest sizes are tunable, but changing them would also need a new frontend type, FOMOD handling and physics config. A shared const removes the duplicate without implying that flexibility, and avoids a singleton lookup inside loops such as `ShipXMLFile` L43.
- **Explicit primary keyword**: the CLI's byte-identical output depends on which keyword an alias resolves to, so it is a named, validated field instead of an array-order convention.
- **Translation keys stay in PHP**: `SHIP_TYPES` references `Translation` class constants, which do not belong in language-agnostic JSON.

## Considered Alternatives

| Decision | Chosen Shape | Alternatives Considered | Trade-Off Summary |
|----------|--------------|-------------------------|-------------------|
| Data file location | `config/ship-types.json` | New `data/` directory; embed in `build-config.json` | `config/` already holds shared build data. A separate file keeps type metadata apart from build tuning. |
| Shared code layer | New `ShipTypeConfig` PHP class | Each consumer parses the JSON itself; a `SHIP_TYPE_ALIASES` PHP const map (the synthesis suggestion) | A const map fixes the CLI debt but leaves the GUI copy. A shared class fixes both debt items at once. |
| CLI resolution return value | `resolveMacroKeyword()` returns the internal keyword; aliases go to the target type's primary keyword | Resolve to the canonical type, then reverse-map to one constant per type | Reverse-mapping merges `storage` into `trans` and changes build output. Returning the keyword keeps today's behaviour exactly. |
| Alias target shape | `keywordAliases` map to a canonical type; explicit `primaryKeyword` field per type | Aliases map straight to an internal keyword; primary = first `macroKeywords` entry (implicit) | Mapping to a canonical type keeps the JSON readable in the GUI's vocabulary. An explicit, validated field gives the CLI a deterministic keyword without a hidden array-order rule. |
| Sizes | `public const array SIZES` on `ShipTypeConfig` | Sizes in `ship-types.json` read at runtime; leave the CLI constant and point the GUI at it | Sizes are a closed game-defined set. A const gives one source with no I/O and no implication that they are editable; pointing the GUI at the extractor would couple it to the CLI class. |
| `ShipTypeConfig` API breadth | Only accessors with a caller: `resolveType`, `resolveMacroKeyword`, `getTypes`, `getTypeNames` | Also `getMacroKeywordsForType`, `getKeywordAliases`, or a single `getData()` array | Unused public methods become a maintained contract; `getData()` leaks the file shape. Add accessors when a caller exists. |
| JSON key name | `keywordAliases` | `aliases` | The codebase already uses "alias" for XML alias macros. A qualified name avoids confusing the two. |
| Translation keys | Keep in `CargoSizeExtractor::SHIP_TYPES` | Move to JSON | They are PHP class constant references. |
| Frontend types | Hardcoded union types with a doc comment | Generate from JSON | Runtime values come from the API. Code generation adds toolchain cost for little gain. |

## Pattern Alignment

- **Shared Config Pattern** (`config/build-config.json`, `gui/backend/src/Services/ConfigService.php`): followed — JSON in `config/`, read synchronously through a `__DIR__`-relative path.
- **Static Factory Pattern** (`docs/agents/project-manifest/tech-stack.md`, as in `ShipDefs::getInstance()`): followed — `ShipTypeConfig::getInstance()`. This is also required because `ShipTypeResolutionTest` creates the extractor without its constructor, which rules out constructor injection on the CLI side.
- **Translation Pattern** (`src/Mods/CargoSizesMod/Translation.php`): followed — translation keys stay in PHP.

## Structural Improvements

| Structure | Observation | Decision | Reason |
|-----------|-------------|----------|--------|
| `CargoSizeExtractor::resolveShipType()` L540–548 | Hardcoded `scavenger`/`terraformer` alias checks (H & Barbarossa debt, medium) | Promoted to steps 1–3 | Core goal of this plan. |
| `ShipDataService::determineShipType()` L341–360 | Duplicates CLI logic, with an unenforced "same logic" contract (H & Barbarossa convention, low) | Promoted to step 5 | Core goal of this plan. |
| `CargoSizeExtractor.php` L582 | Non-ASCII en-dash in the alias skip console message shows as mojibake on Windows (H & Barbarossa improvement, low) | Promoted to step 4 | One-line change in a file this plan already edits. |
| `CargoSizeExtractor::SHIP_SIZES` and its 5 consumers | Duplicated by `ShipDataService::SHIP_SIZES` | Promoted to step 3 (as `ShipTypeConfig::SIZES`) | Leaving the CLI constant in place would keep a second copy of the sizes. All consumers are listed (`FomodWriter` L178, `ShipXMLFile` L43, extractor L400/L677/L715). |
| `CargoSizeExtractor::normalizeShipType()` | Hand-maintained if/else chain; repeats the storage→transport grouping | Rejected | It produces **output names** (`miner`, not the canonical `mining`) that are baked into FOMOD folder names and the `example-ships` keys of `build-config.json`. Sending it through the canonical types would rename `miner_*` folders and break those keys. Supporting it would need a separate `outputName` field in the JSON, a CLI-only concern that is not part of the CLI/GUI duplication. Reconsider if output naming is ever unified. |
| `analyzeShipMacro()` pure alias macro guard | Unrelated to ship type metadata | Rejected (no change apart from the step 4 message text) | It is correct as it stands. The GUI does not need it because X4 Core's `ShipDefs` already filters alias-only entries (per the H & Barbarossa synthesis). |

## Detailed Steps

### Step 0: Capture the baseline build output (no dependencies — run first)
Before any source change, run `composer build`. Then copy `build/physics-diagnostics.txt`, plus a sorted recursive file listing of `build/`, into `work/baseline-ship-types/`. `work/` is gitignored, and nothing in this plan cleans or writes to it. `build/` itself cannot be used as the reference, because every later build overwrites it. This snapshot is the oracle for AC-10.

### Step 1: Create `config/ship-types.json` (NEW)
Create the file with the shape defined above: four types in the order transport, mining, auxiliary, carrier; each with `label`, `primaryKeyword` and `macroKeywords`; `transport.macroKeywords` = `["trans", "storage"]` in that order; and `keywordAliases` with `scavenger`→`transport` and `terraformer`→`mining`. The file has no sizes field.

### Step 2: Create `src/Mods/CargoSizesMod/ShipTypeConfig.php` (NEW)
Namespace `Mistralys\X4\Mods\CargoSizesMod`, with `declare(strict_types=1)`. The class:
- `public const array SIZES = ['xs', 's', 'm', 'l', 'xl'];` — the single shared size list (moved from `CargoSizeExtractor::SHIP_SIZES`).
- Loads and caches the JSON synchronously (`file_get_contents()`), and throws a clear exception that names the path if the file is missing or invalid.
- At load time, validates that every `keywordAliases` target is a defined type, every type has at least one `macroKeywords` entry, and every type's `primaryKeyword` is a member of its own `macroKeywords`. It throws on violation.
- `getInstance(): self` — static singleton using the default path (`__DIR__`-relative to `config/ship-types.json`).
- `createFromFile(string $path): self` and `resetInstance(): void` — so tests can use fixture files.
- `resolveType(string $macroName): ?string` — canonical type.
- `resolveMacroKeyword(string $macroName): ?string` — internal keyword. Aliases return the target type's `primaryKeyword`.
- `getTypes(): array` — `[{type, label}, …]` in JSON order.
- `getTypeNames(): array` — canonical type names (for validation).

The API deliberately has no further accessors: every method above has a caller in Step 3 or 5. Add more only together with a real caller.

Because the root `composer.json` autoloads `src` by classmap (not PSR-4), run `composer dump-autoload` after creating the class so it is found, from the CLI and from the GUI backend's autoloader.

Matching rules (both resolve methods): split the macro name on `_` using the same `ConvertHelper::explodeTrim('_', …)` the current code uses; check `keywordAliases` first; then check types in document order and each type's `macroKeywords` in array order; first match wins.

### Step 3: Refactor `CargoSizeExtractor` and the CLI size consumers
In `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`:
- Replace the body of `resolveShipType()` with `return ShipTypeConfig::getInstance()->resolveMacroKeyword($macroName);`. **Delete the hardcoded `scavenger`/`terraformer` checks and their comment (L540–548).**
- Remove the `SHIP_SIZES` constant and replace its uses at L400, L677 and L715 with `ShipTypeConfig::SIZES`.
- Keep `SHIP_TYPE_*`, `SHIP_TYPES`, `getShipTypesPretty()`, `getTypeLabel()` and `normalizeShipType()` unchanged (see Structural Improvements).

In `src/Mods/CargoSizesMod/FOMOD/FomodWriter.php` (L178) and `src/Mods/CargoSizesMod/XML/ShipXMLFile.php` (L43): replace `CargoSizeExtractor::SHIP_SIZES` with `ShipTypeConfig::SIZES`.

Before removing the constant, grep the whole repository (including `gui/backend/src` and `tests/`) for `SHIP_SIZES` to confirm no other consumer exists.

### Step 4: Fix the alias skip console message encoding
In `CargoSizeExtractor::analyzeShipMacro()` (L582), change `'Alias macro – skipping [%s]'` to `'Alias macro - skipping [%s]'` (plain ASCII hyphen). Leave the guard logic as it is.

### Step 5: Refactor `ShipDataService` to use `ShipTypeConfig`
In `gui/backend/src/Services/ShipDataService.php`:
- Remove the `SHIP_TYPE_TRANSPORT`, `SHIP_TYPE_MINING`, `SHIP_TYPE_AUXILIARY`, `SHIP_TYPE_CARRIER`, `SHIP_TYPE_MAP` and `SHIP_SIZES` constants.
- `getShipTypes()` returns `$this->shipTypeConfig->getTypes()`.
- `determineShipType()` delegates to `$this->shipTypeConfig->resolveType($shipId)`. **Delete the hardcoded `scavenger`/`terraformer` checks (L341–349)** and replace the "Uses the same logic as …" docblock line with a reference to `ShipTypeConfig` and `config/ship-types.json`.
- Replace `self::SHIP_SIZES` at L280, L312 and L431 with `ShipTypeConfig::SIZES`.
- Validate the type parameter in `getShipsByType()` against `getTypeNames()`.
- Add an optional `?ShipTypeConfig $shipTypeConfig = null` constructor parameter that falls back to `ShipTypeConfig::getInstance()`.
- Intended behaviour change: storage macros, which the GUI resolved to `null` before, now resolve to `transport`, matching how the CLI outputs them.

### Step 6: Create `ShipTypeConfig` unit tests (NEW)
Create `tests/CargoSizesModTests/ShipTypeConfigTest.php`, covering every entry listed in the Test Plan. Load the real `config/ship-types.json` for the mapping tests, and fixture files under a NEW `tests/files/ship-types/` folder for the validation and exception tests (including a fixture whose `primaryKeyword` is not in `macroKeywords`). Call `resetInstance()` in `tearDown()`.

### Step 7: Extend the CLI characterization test
In `tests/CargoSizesModTests/ShipTypeResolutionTest.php`:
- Keep all existing tests unchanged. They are the regression guard proving the delegation preserves behaviour.
- Add `testStorageResolvesToStorage`: a storage macro (e.g. `ship_arg_l_storage_container_01_a_macro`) resolves to `CargoSizeExtractor::SHIP_TYPE_STORAGE`. This guards the storage/trans distinction.

### Step 8: Update the `ShipDataService` test suite
In `gui/backend/tests/Unit/Services/ShipDataServiceTest.php`:
- Update any tests that use the removed constants.
- Add tests showing that `getShipTypes()` returns the JSON-defined types, that scavenger/terraformer macros come back under `transport`/`mining` (through the public API, or Reflection on `determineShipType()` like the CLI test does), and that an injected fixture `ShipTypeConfig` with an extra keyword alias is picked up without code changes.
- Add `testStorageMacroClassifiedAsTransport`: a storage macro (e.g. `ship_arg_l_storage_container_01_a_macro`) is classified as `transport` through the GUI service. This covers the intended Step 5 behaviour change (storage was `null` before).
- Fixture source for the injected-config test: the GUI test does not use the CLI `tests/files/ship-types/` folder, to avoid a cross-tree path dependency. It writes its own small JSON fixture (the real file's content plus one extra keyword alias) to a temp file under `sys_get_temp_dir()` in `setUp()`, loads it with `ShipTypeConfig::createFromFile()`, passes it to the `ShipDataService` constructor, and deletes the file in `tearDown()`.

### Step 9: Update documentation
Apply every entry under Documentation Updates. The alias tables added by the H & Barbarossa plan must be **replaced** with a pointer to `config/ship-types.json`, not kept alongside it. Keep the pure alias macro skip rule text in every file.

### Step 10: Update frontend type documentation
In `gui/frontend/src/types/ships.d.ts`, add a comment to `ShipType` stating it must match the types in `config/ship-types.json`, and to `ShipSize` stating it must match `ShipTypeConfig::SIZES`. No code changes.

## Dependencies
- Builds on the already-shipped `2026-06-12-add-h-and-barbarossa` plan: it removes that plan's hardcoded keyword alias checks in both layers and replaces its documentation tables.
- Step 0 must finish before Steps 1–5 change any source. The AC-10 build comparison depends on Step 0's snapshot in `work/baseline-ship-types/`.
- Step 2 depends on Step 1 (and includes the `composer dump-autoload` run). Steps 3, 5 and 6 depend on Step 2. Step 7 depends on Step 3. Step 8 depends on Step 5. Step 9 depends on Steps 3–5. Step 4 has no dependencies.
- No external library dependencies.

## Required Components
- `config/ship-types.json` (NEW)
- `src/Mods/CargoSizesMod/ShipTypeConfig.php` (NEW)
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` (MODIFY)
- `src/Mods/CargoSizesMod/FOMOD/FomodWriter.php` (MODIFY — sizes source)
- `src/Mods/CargoSizesMod/XML/ShipXMLFile.php` (MODIFY — sizes source)
- `gui/backend/src/Services/ShipDataService.php` (MODIFY)
- `tests/CargoSizesModTests/ShipTypeConfigTest.php` (NEW)
- `tests/files/ship-types/` fixture JSON files (NEW)
- `tests/CargoSizesModTests/ShipTypeResolutionTest.php` (MODIFY — add storage case)
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` (MODIFY)
- `gui/frontend/src/types/ships.d.ts` (MODIFY — comment only)

## Assumptions
- Composer autoloads `src` by classmap (verified in `composer.json`), so `ShipTypeConfig` is found only after `composer dump-autoload`; Step 2 includes that run.
- The GUI backend already depends on CLI classes through Composer, so using `ShipTypeConfig` adds no new dependency direction.
- `tests/files/` is an acceptable fixture location. If the CLI test suite uses a different fixture folder convention, the developer follows that convention.

## Constraints
- File I/O must stay strictly synchronous (per `constraints.md`).
- `strict_types` in all PHP files.
- `resolveShipType()` must keep returning internal keywords, and `normalizeShipType()` output values must not change. Both are part of the build output contract.

## Out of Scope
- Generating TypeScript types from the JSON.
- Moving translation keys into JSON.
- Unifying `normalizeShipType()` output naming (`miner`) with canonical naming (`mining`). See Structural Improvements.
- Any change to the pure alias macro guard logic.

## Acceptance Criteria
- AC-01: `config/ship-types.json` exists with all four types, their labels, explicit primary keywords and macro keywords, and the `scavenger` and `terraformer` keyword aliases.
- AC-02: `ShipTypeConfig` loads the JSON, validates it (including that each `primaryKeyword` is one of its type's `macroKeywords`), and provides `resolveType()`, `resolveMacroKeyword()`, `getTypes()` and `getTypeNames()`, plus the `SIZES` constant.
- AC-03: `CargoSizeExtractor::resolveShipType()` delegates to `ShipTypeConfig::resolveMacroKeyword()` and contains no hardcoded `scavenger`/`terraformer` checks.
- AC-04: `ShipDataService::determineShipType()` delegates to `ShipTypeConfig::resolveType()` and contains no hardcoded `scavenger`/`terraformer` checks. `ShipDataService` has no ship type or size constants, maps or "same logic as" contract.
- AC-05: `CargoSizeExtractor::SHIP_SIZES` no longer exists. Every former consumer, in both layers, references `ShipTypeConfig::SIZES`.
- AC-06: The alias skip message in `analyzeShipMacro()` contains only ASCII characters.
- AC-07: `normalizeShipType()` is unchanged, and storage macros still resolve to `SHIP_TYPE_STORAGE` in the CLI.
- AC-08: Adding a new entry to `keywordAliases` in `config/ship-types.json` is enough for both CLI and GUI to recognize a new keyword, with no PHP code changes.
- AC-09: `composer test` passes for the CLI and GUI test suites. All existing `ShipTypeResolutionTest` tests pass without modification.
- AC-10: `composer build` produces the same override files and `physics-diagnostics.txt` ship list as before the change, including Barbarossa under `transport_l_*` and H under `miner_l_*`.
- AC-11: No project manifest file still contains a hardcoded scavenger/terraformer alias table or a "mirrors `resolveShipType()`" statement. All of them point to `config/ship-types.json`.

## Testing Strategy
The existing `ShipTypeResolutionTest` (from H & Barbarossa) is the characterization guard for the CLI: it must pass untouched after the delegation. `ShipTypeConfig` gets direct unit tests for both resolve methods, precedence, validation and fixture-driven extensibility. The GUI suite covers the delegation and the config-driven type list. A full `composer build` run, compared against a pre-change run, confirms the build output is unchanged.

## Test Plan
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testResolveTypeStandardKeywords` — `trans`/`storage`→`transport`, `miner`→`mining`, `resupplier`→`auxiliary`, `carrier`→`carrier` — AC-02
- `…::testResolveTypeKeywordAliases` — `scavenger`→`transport`, `terraformer`→`mining` — AC-02, AC-08
- `…::testResolveMacroKeywordStandard` — returns the matched keyword (`trans`, `storage`, `miner`, `resupplier`, `carrier`) — AC-02, AC-07
- `…::testResolveMacroKeywordAliasReturnsPrimaryKeyword` — `scavenger`→`trans`, `terraformer`→`miner` — AC-02, AC-10
- `…::testResolveUnknownReturnsNull` — fighter macro returns `null` from both methods — AC-02
- `…::testGetTypes` / `testGetTypeNames` — four types in JSON order with labels — AC-01, AC-02
- `…::testSizesConstant` — `ShipTypeConfig::SIZES` equals `['xs','s','m','l','xl']` — AC-05
- `…::testEveryMacroKeywordHasShipTypesEntry` — every `macroKeywords` entry in the real JSON is a key of `CargoSizeExtractor::SHIP_TYPES`, so a JSON typo cannot pass resolution and fail later in the label lookup — AC-02, AC-07
- `…::testInvalidPrimaryKeywordThrows` — a fixture whose `primaryKeyword` is not in its `macroKeywords` throws — AC-02
- `…::testFixtureKeywordAliasIsResolved` — a fixture file with an extra keyword alias resolves in both methods — AC-08
- `…::testMissingFileThrows` / `testInvalidAliasTargetThrows` — clear exceptions for a missing file and for an alias pointing to an undefined type — AC-02
- `tests/CargoSizesModTests/ShipTypeResolutionTest.php` (existing 7 tests, unchanged) — CLI delegation keeps behaviour — AC-03, AC-09, AC-10
- `tests/CargoSizesModTests/ShipTypeResolutionTest.php::testStorageResolvesToStorage` (NEW) — storage stays distinct from trans — AC-07
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` — `getShipTypes()` returns the JSON types; scavenger/terraformer classified as `transport`/`mining`; an injected fixture alias is picked up (fixture written to a temp file in the test's `setUp()`) — AC-04, AC-08
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php::testStorageMacroClassifiedAsTransport` (NEW) — a storage macro is classified as `transport` by the GUI service (previously `null`) — AC-04
- Source assertion (grep in review/QA): no `'scavenger'`/`'terraformer'` literals in `src/` or `gui/backend/src/`, no `SHIP_SIZES` anywhere, and only ASCII on the L582 message line — AC-03, AC-04, AC-05, AC-06
- `composer build` after the change, with the `build/` file listing and `physics-diagnostics.txt` compared against the Step 0 snapshot in `work/baseline-ship-types/` — AC-10
- Manifest grep for `scavenger`/`terraformer`/`mirrors` — AC-11

## Documentation Updates
- `docs/agents/project-manifest/tech-stack.md` — Replace the "Ship Type Alias Mapping (Filter Pattern)" table (L78–86) with a short description of `config/ship-types.json` + `ShipTypeConfig` (Shared Config Pattern). Keep the pure alias macro skip paragraph (L87). Bump the version and add a changelog row.
- `docs/agents/project-manifest/constraints.md` §5 "Ship Type Filtering Rules" — Replace the alias intercept rule and table with these rules: (a) ship type metadata, keyword aliases and sizes MUST be defined in `config/ship-types.json` and never hardcoded in PHP; (b) each type declares an explicit `primaryKeyword` (a member of its `macroKeywords`) that keyword aliases resolve to in the CLI, and sizes are the `ShipTypeConfig::SIZES` constant, not data; (c) keyword aliases are checked before standard keywords. Keep the pure alias macro skip rule. Remove the L821 sentence about `determineShipType()` applying "the identical intercept". Bump the version.
- `docs/agents/project-manifest/data-flows.md` Step 4 (L129–137) — Keep the alias macro skip line. Replace the two-step resolution block with "Resolve via `ShipTypeConfig::resolveMacroKeyword()` (keyword aliases → standard keywords, from `config/ship-types.json`)". Bump the version.
- `docs/agents/project-manifest/public-api.md` — Add the `ShipTypeConfig` public API (the four accessors and the `SIZES` constant).
- `docs/agents/project-manifest/file-tree.md` — Add `config/ship-types.json` and `src/Mods/CargoSizesMod/ShipTypeConfig.php`.
- `gui/docs/project-manifest/public-api.md` — Remove the duplicated constants. Reword the `getShipsByType()` docblock (L222–223) to say classification comes from `config/ship-types.json` through `ShipTypeConfig`. Document the new constructor parameter.
- `gui/docs/project-manifest/data-flows.md` (L399–401) — Replace the "alias intercept (mirrors CargoSizeExtractor::resolveShipType())" block with "`ShipTypeConfig::resolveType()` (shared with the CLI)". Bump the version.
- `changelog.md` — Add bullets under the topmost version entry if it is still unreleased, otherwise under a new patch version: "Core: Ship types and keyword aliases are now defined in the shared `config/ship-types.json`; sizes are a shared constant.", "GUI: Storage ships are now listed under the Transport type, matching the build output." and "Core: Fixed garbled dash in the alias macro console message on Windows."

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **CLI build output changes (storage merged into trans, `miner` renamed)** | `resolveMacroKeyword()` returns internal keywords; `normalizeShipType()` is untouched; `testStorageResolvesToStorage` and the unchanged `ShipTypeResolutionTest` guard it; `composer build` comparison (AC-10). |
| **Match precedence changes** | JSON document order matches the current `SHIP_TYPES` order; precedence is covered by tests. |
| **JSON file missing or malformed at runtime** | Load-time validation with exceptions that name the path. |
| **Missed `SHIP_SIZES` consumer causes a fatal error** | Step 3 lists all five known consumers and requires a repository-wide grep before removal. |
| **New class not found (classmap autoload)** | Step 2 includes `composer dump-autoload`. |
| **JSON `macroKeywords` typo passes resolution, fails in label lookup** | `testEveryMacroKeywordHasShipTypesEntry`; load-time `primaryKeyword` validation. |
| **Confusion between keyword aliases and alias macros** | `keywordAliases` JSON key; Terminology section; the manifests keep the two rules separate. |
| **Singleton state leaking between tests** | `resetInstance()` in test `tearDown()`; GUI uses constructor injection. |
| **Stale manifests still describing the hardcoded alias checks** | Documentation Updates names every location; AC-11 grep. |

## Recommended Workflow
- **Workflow:** ledger
- **Rationale:** The change spans the CLI and the GUI backend through a new shared class, carries a strict no-output-change requirement, and replaces documentation in two manifest sets, so it benefits from formal QA and review stages.
