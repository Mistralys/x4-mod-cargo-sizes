# Plan


## Plan Audit Cycles
- Audits: 5 — Plan Auditor v1.5.0
- Architectural Reviews: 2 — Plan Architect Reviewer v1.6.0


## Summary
Integrate the Xenon H ("terraformer" class) and Barbarossa ("scavenger" class) ships into the X4 Cargo Sizes Mod. This enables the mod's build process to apply cargo size multipliers to these hybrid-role ships and package them into the appropriate `transport` and `miner` outputs for the FOMOD installer.


## Architectural Context
The build system in the `x4-mod-cargo-sizes` project processes raw XML game data to generate mod files. The pipeline selectively filters which ships to assess by parsing their internal macro names (e.g., `ship_xen_l_terraformer_01_a_macro`) via `CargoSizeExtractor::resolveShipType()`. This normalization ensures that correctly processed ships land in predefined directories and FOMOD steps without requiring complex GUI or installer changes.


## Approach / Architecture
We will use an early alias mapping inside `resolveShipType()` within `CargoSizeExtractor`. By prepending an alias check in `resolveShipType()`, we structurally map the `scavenger` internal keyword to `self::SHIP_TYPE_TRANSPORT` and `terraformer` to `self::SHIP_TYPE_MINER` directly based on the `$macroName`. This intercepts the ship classes early, treating them as standard types transparently throughout the rest of the extraction pipeline. This architectural approach avoids bloating whitelist configurations, prevents the need for fake constants, and gracefully adopts the two hybrid ships without refactoring the `FOModBuilder` or `FileCollection` output chains.


## Rationale
Modifying the type resolution function to alias early leverages the current extensible `Extractor-Builder` pattern. It introduces zero new architecture concepts, prevents logic-bloat by reusing native extraction types seamlessly, and avoids bloating the FOMOD installer interface with confusing sub-categories. 


## Considered Alternatives

| Decision | Chosen Shape | Alternatives Considered | Trade-Off Summary |
|----------|--------------|-------------------------|-------------------|
| Output Organization | Normalize via alias directly to `transport` and `miner`. | Creating standalone `scavenger` and `terraformer` UI folders & FOMOD steps. | Normalization prevents cluttering the FOMOD installer, keeping the UX simple while utilizing pre-existing extraction chains correctly. |
| Type Translation | Translate internal identifiers to standard categories synchronously during `resolveShipType()`. | Add independent whitelist keys and normalize downstream. | Early translation prevents logic-bloat and fake constants, treating them seamlessly as their output categories natively. |


## Pattern Alignment
- Extends the `Extractor-Builder` pattern (`src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`) by utilizing an early type resolution intercept.
- Uses existing `array_find` with `ConvertHelper::explodeTrim('_', $macroName)` to reliably check for keywords within macro names instead of raw wildcard string matching.
- Maintains purely synchronous File I/O constraints inside the builder workflow.


## Detailed Steps
1. Update `resolveShipType()` in `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`.
2. Prepend logic to inspect the exploded parts array of `$macroName` using `ConvertHelper::explodeTrim('_', $macroName)`. If `"scavenger"` is present in the parts array, return `self::SHIP_TYPE_TRANSPORT`. If `"terraformer"` is present, return `self::SHIP_TYPE_MINER`.
3. Update `determineShipType()` in `gui/backend/src/Services/ShipDataService.php` to apply the same alias logic. This method's docblock states it "Uses the same logic as CargoSizeExtractor::resolveShipType()" and must be kept in sync. Add early checks for `scavenger` and `terraformer` keywords in the exploded parts, mapping them to `self::SHIP_TYPE_TRANSPORT` and `self::SHIP_TYPE_MINING` respectively.
4. Validate that this builds correctly via `composer build`.


## Dependencies
- Existing `Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor` class to be modified.


## Required Components
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`
- `gui/backend/src/Services/ShipDataService.php`


## Assumptions
- The internal connection nodes for cargo storage (`<connection ref="con_storage...">`) in the Barbarossa and Xenon H XML files strictly follow standard conventions and require no special physical offsets.


## Constraints
- File I/O remains strictly synchronous; no database connections included.
- Must remain fully compliant with strict_types standard.


## Out of Scope
- Adjusting physics calculations explicitly for these ships (relying purely on standard generation formulas).
- Adding custom translation strings for "Terraformer" or "Scavenger" to the core `x4-core` translations database.


## Acceptance Criteria
- Running `composer build` successfully executes without crashing.
- The `physics-diagnostics.txt` file explicitly logs Barbarossa (`ship_pir_l_scavenger_01_a`) and Xenon H (`ship_xen_l_terraformer_01_a`).
- Mod output folders contain the modified cargo definitions for those ships under the transport/miner groupings.


## Testing Strategy
Execute `composer test` for PHPUnit suites to guarantee nothing broke system-wide. Run `composer build` manually to observe diagnostics output arrays ensuring `scavenger` and `terraformer` hit extraction pipelines. Since `resolveShipType()` is private, use Reflection to explicitly test this unit logic, or test indirectly via `extract()`.


## Test Plan
- Create `tests/CargoSizesModTests/ShipTypeResolutionTest.php` with the following test cases (tested via Reflection on the private `resolveShipType()` method):
  - `testScavengerResolvesToTransport` — macro name containing `scavenger` returns `SHIP_TYPE_TRANSPORT`.
  - `testTerraformerResolvesToMiner` — macro name containing `terraformer` returns `SHIP_TYPE_MINER`.
  - `testExistingTypesUnchanged` — standard macro names (e.g., containing `trans`, `miner`, `resupplier`, `carrier`) still resolve to their original types.
- Run existing Test Suite ensuring `composer test` yields passing suite: `composer test`


## Documentation Updates
- `docs/agents/project-manifest/tech-stack.md` — Capture the "Filter Pattern" adjustment, noting the new aliases.
- `docs/agents/project-manifest/data-flows.md` — Document the internal translation of `scavenger` to `transport` and `terraformer` to `miner` in the ship type filtering flow.
- `docs/agents/project-manifest/constraints.md` — Update ship type filtering rules to include the early alias resolution for `scavenger` and `terraformer`.
- `changelog.md` — Add a modification line detailing the addition of Xenon H and Barbarossa integration.
- `gui/docs/project-manifest/public-api.md` — Update `ShipDataService::determineShipType()` documentation to reflect the new alias handling for `scavenger` and `terraformer` keywords.
- `gui/docs/project-manifest/data-flows.md` — Document the scavenger/terraformer alias mapping in the GUI's ship type resolution flow.


## Risks & Mitigations
| Risk | Mitigation |
|------|------------|
| **FOMOD build script fails due to unexpected type keys.** | Using early alias translation in `resolveShipType()` completely disguises these types as `transport` or `miner` before they reach downstream components, evading failure. |
