
## Synthesis

### Completion Status
- Date: 2026-11-22
- Status: COMPLETE
- Completed by: Standalone Developer Agent
- Research brief: used
- Archived in Ledger: 2026-11-22

### Outcome Summary

The deferred hardening items of the shared ship types config project are closed. The pristine v3.2.0 baseline zips were preserved with CRC32 data first. Line endings are pinned by a `.gitattributes`. A repeatable, CRC-based parity comparator and a matching baseline capture command were added as Composer scripts, and a fresh build compares clean against the preserved baseline. `ShipTypeConfig` validation gaps are closed, and both PHPUnit suites run without notices or deprecations.

### Implementation Summary
- Baseline: the 26 pristine zips, the release notes and a `build-zip-crc.txt` listing (name, size, CRC32) are stored in `work/baseline-ship-types/`; entry names and sizes were verified against the existing text listing before copying.
- Line endings: 13 LF working-tree files converted to CRLF; `.gitattributes` has `* text=auto` plus `docs/cargo-size-reference.md text eol=lf` (the generator emits LF).
- `composer build:parity` / `composer build:baseline`: `BuildParityComparator` and `BuildParityResult` compare by per-entry CRC32 (version and generated dates normalised, line endings never folded, allow-list limited to `fomod/ModuleConfig.xml`, presence always enforced). `BaselineOptions`, `BaselineCapture`, `BaselineCommand` and `BaselineCommandResult` provide the agent-facing surface: no prompts, verdict line first, exit codes 0/1/2, an overwrite guard checked before building, and error messages that name the fix. `CargoSizeBuildTools::build()` now shares a `runBuild()` that reports failure to the capture command.
- `ShipTypeConfig::load()` rejects an empty `types` section; new `CargoSizeException::ERROR_PARITY_SETUP` for parity setup errors.
- Tests: comparator, command/capture, alias skip message (ASCII), and the `ShipTypeConfig` gaps (missing label, empty types, empty aliases, non-string alias target, case sensitivity) with four new fixtures.
- Test hygiene: `createMock()` to `createStub()` where no expectation is set in both suites; PHPUnit configuration migrated to the 12.5 schema, which removed the only deprecation.
- Exit gate: `composer build` then `composer build:parity` passes against the Step 1 baseline; the capture command was smoke-tested end to end into a throwaway folder (capture, parity, overwrite refusal), which was then removed.

### Documentation Updates
- `AGENTS.md`: new section "Output-parity check for refactors", mapping-table row, Common Tasks entry, version bump.
- Project manifest: `public-api.md` (new classes, entry points, error constant, empty-types rule), `file-tree.md` (new files, tests and fixtures, `.gitattributes`), `tech-stack.md` (Build Parity Check, scripts; Shared Config Pattern re-verified and left unchanged), `constraints.md` (line endings, empty types), `data-flows.md` (Parity Check Flow); each with version header and history row.
- `docs/building.md`: short pointer to the AGENTS.md section.
- `docs/agents/implementation-archive/README.md`: `SHIP_SIZES` is now `ShipTypeConfig::SIZES`.
- `gui/docs/project-manifest/public-api.md`: version bump and a new Version History table.
- No `changelog.md` entry (no user-facing change, per the plan); the WIP v3.2.1 entry is untouched.

### Verification Summary
- Tests run: root PHPUnit suite (`vendor/bin/phpunit` / `composer test`, with notice and deprecation display flags) and the `gui/backend` PHPUnit suite.
- Static analysis run: `composer analyze` in the root project and PHPStan in `gui/backend`.
- Result: PASS. Both suites green with zero notices, warnings and deprecations; PHPStan reports no errors in either project; `composer build:parity` exits 0 after a fresh build; `git ls-files --eol` shows only the pinned generated file as `w/lf`.

### Code Insights

#### Implementation Decisions
- [MEDIUM] (decision) `src/Mods/CargoSizesMod/Build/BuildParityComparator.php`: The real builds differ beyond what the plan listed. `content.xml` carries the version in compact form (`version="320"`) and a `date` attribute, and `_readme.txt` has a `Generated on:` line. All are normalised along with `Build Date:`. Version normalisation applies to text entries only, so binary FOMOD images are compared by stored CRC. Only `fomod/ModuleConfig.xml` (random example ships) needed the allow-list.
- [MEDIUM] (decision) `gui/backend/tests/Unit/Services/ShipDataServiceTest.php`: The plan rejected changing `createMock()` at L482/L502 on the premise that they raise no notices. They do, and the acceptance criterion requires zero notices, so both became `createStub()`.
- [MEDIUM] (decision) `tests/CargoSizesModTests/ExampleShipSelectionTest.php`: One test called `->with()` on a stub, which is deprecated and has no effect. It stays a real mock with `expects(atLeastOnce())` so the lookup arguments remain verified; the other `createMock()` calls became `createStub()`.
- [LOW] (decision) `.gitattributes`: `MarkdownReference` emits LF, so `docs/cargo-size-reference.md` is pinned `text eol=lf` rather than converted; everything else is `* text=auto`.
- [LOW] (decision) `phpunit.xml`: The only deprecation was the 9.5 schema. `phpunit --migrate-configuration` moved it to 12.5 (`<coverage>` became `<source>`).
- [LOW] (decision) `tests/CargoSizesModTests/AliasSkipMessageTest.php`: `Console` has no enabled flag or setter (the plan assumed one), so the test captures output with `ob_start()`. No production seam was added.

#### Follow-Up Items
- [MEDIUM] (improvement) tooling / `AGENTS.md`: Git Bash `sed -i` strips CR from CRLF files, silently converting a CRLF file to LF on any in-place edit (this hit three test files during the session and was repaired). A line was added to the AGENTS.md parity section; consider a broader Windows note.
- [LOW] (improvement) `BuildParityComparator.php`: The allow-list waives the whole content of `fomod/ModuleConfig.xml`. A stricter oracle would normalise only the `Example: ... cargo changes from ...` lines and compare the rest.
- [LOW] (debt) `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`: `extract()` prints a hard-coded "DEBUG: writeFiles() completed successfully" line on every build; remove it.
- [LOW] (convention) `src/Mods/CargoSizesMod/Build/`: `BuildConfig` uses the `...\Build` namespace while the other classes use the root `CargoSizesMod` namespace. The new classes follow the majority and the plan.
- [LOW] (debt) `tests/CargoSizesModTests/BuildParityComparatorTest.php`: There is no autoloaded shared test support (`autoload-dev` only classmaps `src`), so the temp folder and zip helpers are duplicated in `BuildBaselineCommandTest`. Add a `tests/` autoload entry and extract a helper when a third consumer appears.

### Additional Comments
- Composer prints a `> ...::compareBaseline` line before the command output, so the verdict is the first line of the command's own output, not of the terminal.
- A build rewrites the tracked `docs/cargo-size-reference.md` as LF; it shows as modified in `git status` after the exit gate. That is expected.
- Step 1's baseline in `work/baseline-ship-types/` still holds the v3.2.0 output; `build:baseline --force` replaces only `zips/` and `physics-diagnostics.txt` there.
- Human actions remain: finalise v3.2.1 and run the pending Knowledge Archiver for `2026-11-22-shared-ship-types-config`.
