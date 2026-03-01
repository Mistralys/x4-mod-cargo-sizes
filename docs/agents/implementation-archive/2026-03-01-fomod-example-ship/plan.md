# Plan: Example Ship Cargo Values in FOMOD & Release Notes

## Summary

Two related features to make cargo multiplier choices more tangible for players:

1. **FOMOD installer descriptions** — Add an example ship with vanilla and adjusted cargo values to each multiplier plugin's `<description>` element. For example: *"Increases the cargo size for M-sized Transport ships by x4. Example: Vulture Vanguard cargo changes from 4,600 to 18,400."* The example ship is randomly selected from the matching size class and category.

2. **Release notes AIO comparison table** — Add a Markdown table to the generated release notes showing one randomly-selected transport ship example per AIO multiplier variant, helping players compare multiplier impact at a glance. For example:

   ```
   ## Cargo Multiplier Comparison

   | Variant | Example Ship | Vanilla Cargo | Adjusted Cargo |
   |---------|-------------|---------------|----------------|
   | AIO x2  | Vulture Vanguard | 4,600 m³ | 9,200 m³ |
   | AIO x4  | Vulture Vanguard | 4,600 m³ | 18,400 m³ |
   | AIO x8  | Vulture Vanguard | 4,600 m³ | 36,800 m³ |
   | AIO x10 | Vulture Vanguard | 4,600 m³ | 46,000 m³ |
   ```

## Architectural Context

### Key Modules Involved

- **`FileCollection`** ([src/Mods/CargoSizesMod/FOMOD/FileCollection.php](../../../../src/Mods/CargoSizesMod/FOMOD/FileCollection.php)) — Groups `BaseOverrideFile` objects by ship type, size, and multiplier. Already stores the override files that contain full `ShipResult` data. Generates the plugin label and description text used in the FOMOD ModuleConfig.xml.

- **`FomodWriter`** ([src/Mods/CargoSizesMod/FOMOD/FomodWriter.php](../../../../src/Mods/CargoSizesMod/FOMOD/FomodWriter.php)) — Orchestrates FOMOD generation. Calls `$collection->getPluginDescription()` when building `<plugin>` XML elements for each multiplier option.

- **`BaseOverrideFile`** ([src/Mods/CargoSizesMod/Output/BaseOverrideFile.php](../../../../src/Mods/CargoSizesMod/Output/BaseOverrideFile.php)) — Abstract base for override files. Contains a `protected ShipResult $ship` property with accessors: `getShipName()`, `getCargo()`, `getAdjustedCargo()`, `getSize()`, etc.

- **`ShipResult`** ([src/Mods/CargoSizesMod/Build/ShipResult.php](../../../../src/Mods/CargoSizesMod/Build/ShipResult.php)) — Contains original ship data (label, cargo value, type). Has `calculateCargoValue(float $multiplier): int` for computing adjusted values. Has `getShipLabel(): string` for human-readable ship names.

- **`ReleaseNotesGenerator`** ([src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php](../../../../src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php)) — Generates the `build/release-notes-v{VERSION}.md` file from parsed changelogs. Currently receives only a `FolderInfo $buildFolder` and has NO access to ship results or multiplier data. Outputs: H1 main changelog, optional H2 builder changelog, and a footer explaining AIO vs FOMOD.

- **`CargoSizeExtractor`** ([src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php](../../../../src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php)) — Orchestrates extraction and calls `writeReleaseNotes()`. Holds `$this->results` (all `ShipResult` objects) and `$this->multipliers`. Currently instantiates `ReleaseNotesGenerator` with only the build folder.

### Current Behavior

The current `FileCollection::getPluginDescription()` returns a static string:
```
"Increases the cargo size for M-sized Transport ships by x4."
```

It does NOT reference any specific ship or concrete cargo values.

### Data Flow for This Feature

The data is already available within `FileCollection`. It stores an array of `BaseOverrideFile` objects (`$this->files`), each of which wraps a `ShipResult`. However, `FileCollection` stores BOTH `StorageOverrideFile` and `FlightMechanicsOverrideFile` objects (two per ship). For picking an example ship, we need to:
1. Access the `$this->files` array
2. Pick one `StorageOverrideFile` at random (as it directly exposes cargo values)
3. Use its cargo/adjusted cargo data and ship label

### FOMOD Description Format

The `<description>` element in FOMOD's `ModuleConfig.xml` is **plain text only** (no HTML, no BBCode, no markdown). Line breaks are preserved in most FOMOD readers.

## Approach / Architecture

### Feature 1: FOMOD Plugin Description Examples

#### High-Level Design

Modify `FileCollection` to expose an example ship from its stored override files. Add a new method `getExampleShipDescription()` that:
1. Filters the `$files` array to only `StorageOverrideFile` instances (since those directly expose cargo values)
2. Picks one at random
3. Returns a formatted string like: `"Example: Vulture Vanguard cargo changes from 4,600 to 18,400."`

Then modify `getPluginDescription()` to append this example text.

### Why Modify `FileCollection` (Not `FomodWriter`)

The description generation logically belongs in `FileCollection` because:
- It already generates the `getPluginDescription()` text
- It already has access to the override files containing ship data
- It keeps the FOMOD XML template generation in `FomodWriter` clean
- It follows the existing pattern where `FileCollection` encapsulates all per-collection data

### Random Selection

Use PHP's `array_rand()` for random ship selection. This is a build-time tool that runs once and exits, so:
- Non-deterministic output between builds is acceptable (and desired — variety)
- No seed control needed
- Each build will feature a potentially different example ship

### Feature 2: Release Notes AIO Comparison Table

#### High-Level Design

Modify `ReleaseNotesGenerator` to accept multipliers and ship results, then generate a markdown comparison table between the footer's "AIO = All In One" explanation and the rest of the footer content.

The table picks **one** transport ship at random and shows its vanilla cargo alongside the adjusted cargo for each multiplier. Using the same ship across all rows makes the comparison intuitive — players see the exact same baseline scaled differently.

#### Why One Transport Ship for All Rows

- Transport ships are the most commonly modded category and the most relatable reference
- Using the same ship across all rows eliminates confusing different base values — the comparison is purely about the multiplier effect
- One row per AIO multiplier matches the ZIP file variants exactly

#### Data Flow

1. `CargoSizeExtractor::writeReleaseNotes()` passes `$this->multipliers` and `$this->results` to `ReleaseNotesGenerator`
2. `ReleaseNotesGenerator` filters results to transport ships only (ship type `trans` or `storage` — both map to "Transport ships" in `SHIP_TYPES`)
3. Picks one at random via `array_rand()`
4. Generates a markdown table with one row per multiplier
5. Table is inserted between the builder changelog section and the footer

#### Table Placement in Output

```
# Release v3.0.0 - ...
- Change 1
- Change 2

## Builder v1.4.0 - ...          (optional)
- Builder change 1

## Cargo Multiplier Comparison    <-- NEW SECTION

| Variant | Example Ship | Vanilla Cargo | Adjusted Cargo |
|---------|-------------|---------------|----------------|
| AIO x2  | Shuyaku Vanguard | 37,000 m³ | 74,000 m³ |
| AIO x4  | Shuyaku Vanguard | 37,000 m³ | 148,000 m³ |
| AIO x8  | Shuyaku Vanguard | 37,000 m³ | 296,000 m³ |
| AIO x10 | Shuyaku Vanguard | 37,000 m³ | 370,000 m³ |

----
Choose your ZIP file...
AIO = All In One, with all supported ship types
FOMOD = Installer to choose by ship type and size
```

## Rationale

- **Minimal changes:** Feature 1 modifies one file (`FileCollection`). Feature 2 modifies two files (`ReleaseNotesGenerator` constructor + a new method, and `CargoSizeExtractor::writeReleaseNotes()` call site).
- **User benefit:** Concrete cargo numbers make the multiplier impact immediately tangible — both during FOMOD installation and when choosing which AIO ZIP to download from Nexus Mods
- **Existing data:** All required data (ship labels, cargo values, multiplied cargo values) is already available within the build pipeline — no new data extraction or parsing needed
- **Pattern compliance:** Follows existing patterns — `FileCollection` owns its description text; `ReleaseNotesGenerator` follows the same constructor-injection approach used by `MarkdownReference` and `BBCodeReference`
- **Same ship for comparison table:** Using the same transport ship across all multiplier rows makes the table a clean apples-to-apples comparison

## Detailed Steps

### Step 1: Add `getExampleShipDescription()` to `FileCollection`

**File:** `src/Mods/CargoSizesMod/FOMOD/FileCollection.php`

Add a new private method:

```php
/**
 * Returns a description line with an example ship's cargo change for this collection.
 * Picks a random StorageOverrideFile from the collection to use as the example.
 *
 * @return string Example text, or empty string if no suitable file found
 */
private function getExampleShipDescription(): string
```

**Logic:**
1. Filter `$this->files` to `StorageOverrideFile` instances only (exclude `FlightMechanicsOverrideFile`)
2. Further filter to only those with non-empty ship names (the extractor already skips nameless ships, but defensive check)
3. If no files found, return empty string
4. Use `array_rand()` to pick one at random
5. Format and return: `"Example: {shipName} cargo changes from {original} to {adjusted}."`
6. Use `number_format()` with comma-separated thousands for cargo values, consistent with the reference generators ([MarkdownReference](../../../../src/Mods/CargoSizesMod/References/MarkdownReference.php) and [BBCodeReference](../../../../src/Mods/CargoSizesMod/References/BBCodeReference.php))

### Step 2: Modify `getPluginDescription()` in `FileCollection`

**File:** `src/Mods/CargoSizesMod/FOMOD/FileCollection.php`

Update the existing method to append the example ship text:

**Current:**
```php
public function getPluginDescription() : string
{
    return sprintf(
        'Increases the cargo size for %1$s-sized %2$s by x%3$s.',
        strtoupper($this->getShipSize()),
        $this->getShipTypeLabel(),
        $this->getMultiplier()
    );
}
```

**New:**
```php
public function getPluginDescription() : string
{
    $description = sprintf(
        'Increases the cargo size for %1$s-sized %2$s by x%3$s.',
        strtoupper($this->getShipSize()),
        $this->getShipTypeLabel(),
        $this->getMultiplier()
    );

    $example = $this->getExampleShipDescription();
    if ($example !== '') {
        $description .= "\n" . $example;
    }

    return $description;
}
```

### Step 3: Add import for `StorageOverrideFile`

**File:** `src/Mods/CargoSizesMod/FOMOD/FileCollection.php`

Add `use Mistralys\X4\Mods\CargoSizesMod\StorageOverrideFile;` to the imports (note: the class is in the root namespace `Mistralys\X4\Mods\CargoSizesMod`, not in `Output`).

### Step 4: Update manifest documents

The following manifest documents need minor updates:
- **`public-api.md`** — Add the new `getExampleShipDescription()` method signature (though private methods are not typically documented; instead, note the changed behavior of `getPluginDescription()`). Also update `ReleaseNotesGenerator` constructor signature and add `formatComparisonTable()`.
- **`data-flows.md`** — Update the FOMOD Generation section (Step 8) to mention that plugin descriptions now include an example ship. Update the Release Notes section (Step 9b) to mention the comparison table.

### Step 5: Expand `ReleaseNotesGenerator` constructor to accept ship data

**File:** `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`

Change the constructor to accept multipliers and ship results:

**Current:**
```php
public function __construct(FolderInfo $buildFolder)
{
    $this->buildFolder = $buildFolder;
}
```

**New:**
```php
/**
 * @param FolderInfo $buildFolder
 * @param float[]|int[] $multipliers
 * @param ShipResult[] $shipResults
 */
public function __construct(FolderInfo $buildFolder, array $multipliers, array $shipResults)
{
    $this->buildFolder = $buildFolder;
    $this->multipliers = $multipliers;
    $this->shipResults = $shipResults;
}
```

Add the corresponding properties:
```php
/** @var float[]|int[] */
private array $multipliers;
/** @var ShipResult[] */
private array $shipResults;
```

Add import: `use Mistralys\X4\Mods\CargoSizesMod\ShipResult;`

### Step 6: Add `formatComparisonTable()` to `ReleaseNotesGenerator`

**File:** `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`

Add a new private method:

```php
/**
 * Generates a Markdown comparison table showing cargo changes
 * for a randomly-selected transport ship across all multipliers.
 *
 * @return string Markdown table section, or empty string if no transport ships found
 */
private function formatComparisonTable(): string
```

**Logic:**
1. Filter `$this->shipResults` to transport ships only: `getShipType()` is `CargoSizeExtractor::SHIP_TYPE_TRANSPORT` or `CargoSizeExtractor::SHIP_TYPE_STORAGE` (both are transport-class)
2. If no transport ships found, return empty string (graceful fallback)
3. Pick one at random via `array_rand()`
4. Build a Markdown table:
   - Header: `## Cargo Multiplier Comparison`
   - Column headers: `Variant | Example Ship | Vanilla Cargo | Adjusted Cargo`
   - One row per multiplier from `$this->multipliers`, using the same ship
   - Cargo values formatted with `number_format($value, 0, '.', ',')` plus `m³` unit suffix
   - Variant column: `AIO x{multiplier}`

Add import: `use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;`

### Step 7: Insert the comparison table into `generate()`

**File:** `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`

Modify the `generate()` method to include the comparison table between the builder changelog and the footer:

**Current:**
```php
$content = $this->formatMainChangelog($mainVersion);

if ($builderVersion !== null) {
    $content .= "\n\n" . $this->formatBuilderChangelog($builderVersion);
}

$content .= "\n\n" . $this->formatFooter();
```

**New:**
```php
$content = $this->formatMainChangelog($mainVersion);

if ($builderVersion !== null) {
    $content .= "\n\n" . $this->formatBuilderChangelog($builderVersion);
}

$comparisonTable = $this->formatComparisonTable();
if ($comparisonTable !== '') {
    $content .= "\n\n" . $comparisonTable;
}

$content .= "\n\n" . $this->formatFooter();
```

### Step 8: Update the call site in `CargoSizeExtractor`

**File:** `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`

Modify `writeReleaseNotes()` to pass the multipliers and results:

**Current:**
```php
private function writeReleaseNotes(): void
{
    Console::header('Writing release notes');
    
    $generator = new ReleaseNotesGenerator($this->outputFolder);
    $generator->generate();
    
    Console::line1('Release notes generated successfully.');
}
```

**New:**
```php
private function writeReleaseNotes(): void
{
    Console::header('Writing release notes');
    
    $generator = new ReleaseNotesGenerator(
        $this->outputFolder,
        $this->multipliers,
        $this->results
    );
    $generator->generate();
    
    Console::line1('Release notes generated successfully.');
}
```

## Dependencies

- No new external dependencies required
- No new Composer packages
- Uses existing `StorageOverrideFile` class (already a dependency of `FileCollection` via `BaseOverrideFile`)
- Uses existing `ShipResult` class (already used throughout the build pipeline)
- Uses existing `CargoSizeExtractor` constants for ship type filtering

## Required Components

### Modified Files
- [src/Mods/CargoSizesMod/FOMOD/FileCollection.php](../../../../src/Mods/CargoSizesMod/FOMOD/FileCollection.php) — Add `getExampleShipDescription()`, modify `getPluginDescription()`, add import
- [src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php](../../../../src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php) — Expand constructor, add `formatComparisonTable()`, modify `generate()`, add imports
- [src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php](../../../../src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php) — Update `writeReleaseNotes()` to pass multipliers and results

### Manifest Updates
- [docs/agents/project-manifest/public-api.md](../../../../docs/agents/project-manifest/public-api.md) — Update `getPluginDescription()` docs; update `ReleaseNotesGenerator` constructor and methods
- [docs/agents/project-manifest/data-flows.md](../../../../docs/agents/project-manifest/data-flows.md) — Update FOMOD flow and Release Notes flow

### No New Files
These features require no new classes, interfaces, or files.

## Assumptions

- The `FileCollection` always has at least one `StorageOverrideFile` when `getPluginDescription()` is called (true based on how `registerOverrideFile()` works in the extractor — both storage and flight mechanics files are always added together)
- Ship labels resolved by the extractor are human-readable enough for display in the FOMOD installer (confirmed: they are translated game names like "Vulture Vanguard", "Shuyaku Sentinel", etc.)
- FOMOD readers handle `\n` line breaks in `<description>` elements (standard behavior for all major FOMOD installers including Vortex and MO2)
- The `number_format()` convention with comma-separated thousands is appropriate for both FOMOD and Markdown contexts (consistent with reference docs)
- There is always at least one transport ship in `$this->results` at the time `writeReleaseNotes()` is called (transport is the most common ship category in X4)
- The Nexus Mods release notes field renders Markdown tables (confirmed: Nexus Mods supports Markdown)

## Constraints

- **FOMOD `<description>` is plain text only** — no formatting, no HTML. The example text must be clear without any markup.
- **Synchronous file I/O only** — No issue here; this change doesn't involve file I/O.
- **Strict types** — All new code must use typed parameters and return types.
- **Namespace `Misc\Mods\CargoSizesMod\FOMOD`** — The `FileCollection` class uses this namespace (note: it deviates from the standard `Mistralys` prefix — this is pre-existing and should be kept as-is).
- **Ship name availability** — The `getShipName()` method on `BaseOverrideFile` returns the result of `$this->ship->getShipLabel()`, which may rarely be a raw macro name if translation lookup failed. This is acceptable.

## Out of Scope

- **Image changes** — The FOMOD step images (JPG files in `docs/fomod/`) are not modified
- **Localization of example text** — The "Example:" prefix and FOMOD description format will be in English only. The FOMOD description field is already English-only in the current implementation. Full localization would require a new translation type in `translations.json`, which is unnecessary for a small descriptive helper text.
- **Deterministic ship selection** — No seed-based random selection; each build may feature a different example ship
- **"Unchanged" plugin** — The default "Do not change this ship category" option does not need an example (no multiplier applied)
- **GUI changes** — No changes to the Physics Tuning GUI
- **Multi-category table in release notes** — The comparison table uses transport ships only for simplicity. Adding miner/auxiliary/carrier tables is possible but not in this scope.
- **Release notes for non-transport ships** — Only transport ships appear in the table to keep it concise and focused on the most commonly used category

## Acceptance Criteria

### Feature 1: FOMOD Descriptions
1. **Example text appears** — When building the FOMOD installer, each multiplier plugin's `<description>` includes an example ship with vanilla and adjusted cargo values
2. **Random selection works** — The example ship is randomly selected from the available ships in that size class and category
3. **Cargo values are formatted** — Cargo numbers use comma-separated thousands (e.g., "4,600" not "4600")
4. **Graceful fallback** — If no `StorageOverrideFile` is found in a collection (edge case), the description falls back to the existing format without an example

### Feature 2: Release Notes Table
5. **Table appears** — The generated release notes contain a "Cargo Multiplier Comparison" section with a Markdown table
6. **One ship, all multipliers** — The table uses the same randomly-selected transport ship across all rows
7. **Correct calculations** — Adjusted cargo values equal vanilla cargo × multiplier
8. **Proper formatting** — Table uses comma-separated thousands and m³ unit suffix
9. **Graceful fallback** — If no transport ships exist (theoretical), the table section is omitted gracefully

### Both Features
10. **Build succeeds** — `composer build` completes without errors
11. **PHPStan passes** — `composer analyze` produces no new errors
12. **No test regressions** — Existing PHPUnit tests pass

## Testing Strategy

### Manual Verification — FOMOD
1. Run `composer build`
2. Open the generated FOMOD ZIP file
3. Inspect `fomod/ModuleConfig.xml`
4. Verify each `<plugin>` element's `<description>` contains an example ship line
5. Verify the cargo values are correct (vanilla × multiplier = adjusted)
6. Verify ship names are human-readable

### Manual Verification — Release Notes
1. Run `composer build`
2. Open `build/release-notes-v{VERSION}.md`
3. Verify the "Cargo Multiplier Comparison" table is present
4. Verify the table has one row per configured multiplier
5. Verify all rows use the same transport ship
6. Verify cargo values are correctly computed and formatted
7. Verify the table appears between the changelog content and the footer

### Static Analysis
- Run `composer analyze` (PHPStan) to verify type safety

### Existing Tests
- Run `composer test` to ensure no regressions
- The current tests cover physics calculations, not FOMOD or release notes generation, so no test modifications are needed

### Optional: Unit Test
A unit test for `formatComparisonTable()` could be added by constructing a `ReleaseNotesGenerator` with mock ship data and verifying the output contains the expected table structure. Low priority given the simplicity of the logic.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **FOMOD readers don't render `\n` in descriptions** | Most FOMOD readers (Vortex, MO2) support line breaks. If not, the text still reads correctly as a single line with a period separator. Can be changed to use a space separator if needed. |
| **No StorageOverrideFile in collection** | Defensive check returns empty string; description gracefully falls back to existing format. |
| **Ship label is a raw macro name** | Extremely rare edge case (only when translation lookup fails). The extractor already logs warnings for these. Acceptable for an example. |
| **Non-deterministic build output** | By design — different example ships between builds add variety. If reproducibility is needed later, add a seed parameter. |
| **`number_format` locale issues** | Using explicit format parameters (`0, '.', ','`) to avoid locale dependency, consistent with existing code in reference generators. |
| **No transport ships in results** | Theoretical only — transport is the largest ship category. Defensive check returns empty string; table section omitted gracefully. |
| **Breaking `ReleaseNotesGenerator` constructor** | This is an internal class with a single call site in `CargoSizeExtractor::writeReleaseNotes()`. No external consumers. |
| **Markdown table not rendered by target platform** | Release notes are posted on Nexus Mods (supports Markdown) and GitHub Releases (supports Markdown). Both render tables correctly. |
