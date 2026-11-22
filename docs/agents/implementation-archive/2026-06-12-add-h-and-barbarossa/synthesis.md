## Synthesis

### Completion Status
- Date: 2026-06-12
- Status: COMPLETE
- Completed by: Standalone Developer Agent

### Implementation Summary
- Added early alias intercept to `CargoSizeExtractor::resolveShipType()`: macro names containing `scavenger` return `SHIP_TYPE_TRANSPORT`; those containing `terraformer` return `SHIP_TYPE_MINER`. This runs before the standard keyword lookup.
- Added the same alias intercept to `ShipDataService::determineShipType()` to keep the GUI layer in sync: `scavenger` → `transport`, `terraformer` → `mining`.
- Added a pure-alias macro guard to `CargoSizeExtractor::analyzeShipMacro()`: macros with a non-empty `alias=` attribute in their `<macro>` element are skipped. This was required because the timelines DLC variant `ship_xen_l_terraformer_01_b_macro` and the story variant `ship_pir_l_scavenger_01_a_storyhighcapacity_macro` are pure aliases with no `<physics>` element of their own. The game inherits mod overrides through alias resolution automatically.
- Both Barbarossa (`ship_pir_l_scavenger_01_a_macro`) and Xenon H (`ship_xen_l_terraformer_01_a_macro`) are now fully extracted, appear in `physics-diagnostics.txt`, and have override files written under the `transport_l_*` and `miner_l_*` FOMOD directories respectively.

### Documentation Updates
- `docs/agents/project-manifest/tech-stack.md` — Added Ship Type Alias Mapping table and alias skip rule to Pattern 1 (Extractor-Builder). Bumped to v1.4.
- `docs/agents/project-manifest/data-flows.md` — Updated Step 4 flow to document alias macro skip and the two-step type resolution (alias intercept then standard lookup). Bumped to v1.5.
- `docs/agents/project-manifest/constraints.md` — Added §5 "Ship Type Filtering Rules" to the Build System Constraints section, documenting the alias keyword mapping table and the alias macro skip rule. Bumped to v1.5.
- `changelog.md` — Added three bullet points to v3.1.0 entry for the two new ships and the alias skip behaviour.
- `gui/docs/project-manifest/public-api.md` — Expanded `ShipDataService::getShipsByType()` docblock to mention the `determineShipType()` alias intercept.
- `gui/docs/project-manifest/data-flows.md` — Added alias intercept step to the "Get Ships by Type" flow diagram. Bumped to v1.3.

### Verification Summary
- Tests run: `composer test` — 66 tests, 146 assertions, PASS (pre-existing notices/deprecation unrelated to changes).
- Static analysis run: not run separately; no new code paths were introduced beyond basic conditionals and `in_array` calls, which are not subject to PHPStan violations at the project's configured level.
- Build run: `composer build` — completed successfully; `physics-diagnostics.txt` explicitly contains `Ship: Barbarossa (ship_pir_l_scavenger_01_a_macro)` and `Ship: H (ship_xen_l_terraformer_01_a_macro)`; override files written to `transport_l_x*` and `miner_l_x*` folders.
- Result: PASS — all acceptance criteria met.

### Code Insights
- [medium] (debt) `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`: The alias intercept table (scavenger→transport, terraformer→miner) is embedded as inline string literals inside `resolveShipType()`. If more hybrid ship classes are added in future, this will grow into an unmaintainable list of `if(in_array(...))` branches. Consider extracting a `const array SHIP_TYPE_ALIASES = ['scavenger' => self::SHIP_TYPE_TRANSPORT, ...]` map and looping over it.
- [low] (convention) `gui/backend/src/Services/ShipDataService.php`: `determineShipType()` is documented as "Uses the same logic as `CargoSizeExtractor::resolveShipType()`" but there is no automated enforcement of that contract. A shared helper or at minimum a cross-reference test would prevent the two implementations from drifting again in future.
- [low] (improvement) `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` `analyzeShipMacro()`: The alias skip message uses an em-dash (`—`) rendered via UTF-8 in the source file, which displayed as a mojibake (`ÔÇô`) in the Windows console during testing. Consider using ` - ` (ASCII hyphen-surrounded by spaces) for cross-platform terminal safety.

### Additional Comments
- The plan assumed both ships would pass through without issues ("relies purely on standard generation formulas"). In practice, pure alias DLC/story variants of the new ships required an additional alias guard in `analyzeShipMacro()`. This guard is architecturally sound and beneficial beyond just these two ships — it prevents future crashes if any other alias-only macros are encountered.
- The pure alias guard was added to `CargoSizeExtractor::analyzeShipMacro()` (not to `ShipDataService::determineShipType()`) because the GUI service only uses macro IDs from the X4 Core `ShipDefs` database, which already filters out alias-only entries; no equivalent guard is needed there.
