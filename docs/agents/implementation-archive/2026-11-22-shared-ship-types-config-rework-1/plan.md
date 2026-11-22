# Plan

## Plan Audit Cycles
- Audits: 2 (Sonnet 5.5 ×2) — Plan Auditor v1.13.1
- Architectural Reviews: 1 (Sonnet 5.5 ×1) — Plan Architect Reviewer v2.5.2

## Prior Project Context
This is the rework of `2026-11-22-shared-ship-types-config` (COMPLETE; synthesis in `docs/agents/implementation-archive/2026-11-22-shared-ship-types-config/synthesis.md`). That project moved ship type definitions into `config/ship-types.json` behind `ShipTypeConfig` and left a list of deferred hardening, test and documentation items. The repository's mid-term vision (keep tooling and codebase current) and long-term vision (prepare the project for community maintenance) both favour the items here: a repeatable build-parity check, a `.gitattributes` rule, a quiet test run and complete validation tests. No stored insight applies to these changes.

## Summary
Close out the deferred items of the shared ship types config project in one developer session. The plan (1) preserves the pristine baseline build zips with CRC32 data before anything else runs, (2) normalises line endings and pins them with a `.gitattributes`, (3) replaces the manual build-parity check with a repeatable, CRC-based comparator plus a matching baseline capture command, both wired to Composer scripts and designed for agents (non-interactive, a one-line verdict, fixed exit codes, documented in `AGENTS.md`), (4) closes the `ShipTypeConfig` test gaps (and the one validation hole they expose: an empty `types` section), (5) pins the ASCII-only alias skip message, (6) removes the PHPUnit notices and deprecation in both suites, and (7) finishes the documentation follow-ups. The WIP v3.2.1 release stays open as a post-run action for the user.

## Architectural Context
- `src/Mods/CargoSizesMod/ShipTypeConfig.php` — shared ship type config (`load()` L211–L251, `validateType()` L259–L295). Singleton via `getInstance()`, test constructor `createFromFile()`, `resetInstance()`.
- `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php` — Composer entry point class (`build()` static method, `composer.json` script `build`); `updateVersion()` drives the build version from `changelog.md`.
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php` — `addMessage()` L544, alias skip message L557.
- `tests/CargoSizesModTests/` — `ShipTypeConfigTest.php`, `ShipTypeResolutionTest.php` (Reflection pattern for private extractor methods), `ExampleShipSelectionTest.php` (12 `createMock()` calls). Fixtures in `tests/files/ship-types/`.
- `gui/backend/tests/Unit/Services/` — `ClassRangeServiceTest.php`, `ShipDataServiceTest.php`.
- `work/baseline-ship-types/` (gitignored) holds the text baseline; `build/v3-2-0-for-v9/` (gitignored) still holds the 26 pristine baseline zips.
- Git: `core.autocrlf=true`, index LF everywhere, working tree CRLF except 14 files; no `.gitattributes`.
- Manifests: `docs/agents/project-manifest/*` and `gui/docs/project-manifest/*`, each with a version header and (root manifests) a Version History table.

## Approach / Architecture
1. **Capture first.** Before any change, copy the pristine v3.2.0 baseline zips out of `build/` into `work/baseline-ship-types/zips/` and write a per-entry `name size crc32` listing. This strengthens the oracle with CRCs without needing the old code, because the zips from the Step 0 baseline run still exist.
2. **Line endings.** Convert the 14 LF working-tree files to CRLF (except generated output, see step 3) and add `.gitattributes` with `* text=auto`. The index is already LF, so no tracked content is re-normalised.
3. **Comparator.** A new class `BuildParityComparator` (`src/Mods/CargoSizesMod/Build/BuildParityComparator.php`) compares a baseline zip set against a fresh build: zip pairing by name with the version normalised, entry-set equality, and per-entry CRC32 of content after normalising version strings and the build date (line endings are never folded, so a line-ending regression is always reported), with an explicit, documented allow-list for entries whose content may legitimately differ (random example ships); allow-listed entries must still be present in both sets. A static entry `CargoSizeBuildTools::compareBaseline()` and a Composer script `build:parity` expose it, following the existing `build` script pattern. The counterpart `CargoSizeBuildTools::captureBaseline()` (Composer script `build:baseline`) builds from the current source and snapshots the result into the baseline folder, so every future refactor starts the same way: `composer build:baseline`, change code, `composer build`, `composer build:parity`. Both commands share one default baseline folder constant and are built for agent use: no prompts, a one-line verdict first, fixed exit codes (0 pass, 1 differences, 2 usage or setup error) and error messages that name the fix. Options are deliberately minimal: `--baseline=<dir>` on both, `--force` on the capture. The workflow is documented for agents in `AGENTS.md`.
4. **Config hardening.** `ShipTypeConfig::load()` rejects an empty `types` section; tests and fixtures cover every validation branch plus case sensitivity.
5. **Message and test hygiene.** An ASCII-only test for the alias skip message; `createMock()` → `createStub()` where no expectations are set, in both suites; locate and remove the deprecation.
6. **Docs** updated last, so they describe the final state.

## Rationale
- Everything here sits inside the blast radius of the previous project's files (`ShipTypeConfig`, the extractor tests, the parity gate), so the hardening is cheap now and rarely funded later.
- The comparator is a class rather than a loose script because its normalisation rules (version, date, allow-list) are behaviour that needs its own tests, and it grows with every future refactor that wants a byte-level oracle. Current consumer: the `build:parity` Composer script and the exit gate of this plan.
- The capture command exists because the comparator is only useful against a baseline taken before the change, and a manual capture is exactly the step an agent forgets or gets subtly wrong (wrong folder, stale build). Its consumers are the agents running future output-preserving refactors, and it produces the layout Step 1 writes by hand.
- The agent-facing surface is kept to what has a current consumer: the verdict line, the exit codes, the overwrite guard and `--baseline`/`--force`. JSON output, a text output cap, a line-ending folding flag and a `--no-build` switch were weighed in the design review and dropped as speculative (one consumer, and the comparator's result object already gives tests a structured view). Each can be added when a named consumer appears.
- A repeatable script plus CRC data makes the parity oracle reliable for contributors, in line with the long-term community-maintenance goal.
- Rejecting an empty `types` section is an intentional behaviour tightening: a config with no ship types is never valid and today fails silently as "no ship matches".

## Considered Alternatives
| Decision | Chosen Shape | Alternatives Considered | Trade-Off Summary |
|----------|--------------|-------------------------|-------------------|
| Baseline CRC source | Compute CRCs from the surviving `build/v3-2-0-for-v9/` zips now (Step 1) | Rebuild a baseline from a git worktree of the pre-change commit; keep size-only listing | The zips are the true baseline and cost minutes; a rebuild needs the old commit and game data and would re-randomise example ships. Size-only listing is the weak oracle the synthesis called out. |
| Parity tool shape | Class `BuildParityComparator` + `CargoSizeBuildTools::compareBaseline()` + Composer script | Standalone `scripts/*.php` file; shell/PowerShell diff | Follows the existing Composer-to-static-method pattern, is unit-testable and cross-platform. A loose script cannot be tested with PHPUnit; shell diff cannot read zip CRCs portably. |
| Capture command behaviour | `build:baseline` always runs the build itself and refuses to overwrite a non-empty baseline folder without `--force` | Require agents to run `composer build` first and then a copy-only command; always overwrite; a `--no-build` switch | Building inside the command guarantees the snapshot matches the current source and removes a two-command ordering mistake. The overwrite guard protects a baseline that is still needed, such as the Step 1 baseline. `--no-build` has no current consumer. |
| Agent interface | Fixed exit codes, one-line verdict first, error hints naming the fix, no prompts; options limited to `--baseline` and `--force` | Full CLI with `--format=json`, a 50-line output cap, `--fold-line-endings`, `--no-build`; comparator command only with a documented manual capture | The verdict and exit code are the load-bearing agent contract. The extra switches have no named consumer and would add a larger option parser and about a dozen more tests (design review decision 1). A manual capture is the step agents get wrong, so the capture command stays. |
| Example-ship nondeterminism | Allow-list of entries whose content may differ; presence still enforced | Seed `array_rand` in `FileCollection.php` and `ReleaseNotesGenerator.php` under a build seed; structural-only comparison of those entries | Seeding changes production selection code in a plan that must be output-neutral. Presence enforcement stops the allow-list from hiding a missing entry. |
| Baseline default folder | One constant, `work/baseline-ship-types`, shared by the comparator and the capture command | Two defaults (`work/baseline-ship-types` and `work/build-baseline`) | Step 1's baseline already lives in `work/baseline-ship-types`; one name means the default workflow needs no `--baseline=`. |
| Line-ending policy | `* text=auto` in `.gitattributes`, convert the 14 outliers to CRLF | `* text=auto eol=crlf`; convert the whole tree to LF; no `.gitattributes` | The index is already LF, so `text=auto` changes no tracked content and cannot disturb parity. Forcing `eol=crlf` would impose Windows endings on other platforms; a tree-wide LF conversion changes every embedded template string in build output and invalidates the baseline. |
| Empty `types` | Throw `ERROR_INVALID_CONFIG` | Keep accepting it and only test it | An empty config is always a mistake and silently disables every ship type; failing at load is consistent with the other strict checks. |
| Archive `SHIP_SIZES` note | One note in `docs/agents/implementation-archive/README.md` | Edit each archived plan | Archived plans are frozen history with a deprecation banner; a single index note avoids rewriting history. |
| Mock cleanup | `createStub()` where there is no `expects()` | Suppress notices in `phpunit.xml`; ignore | Suppression hides real warnings, the very thing the cleanup is for. |

## Pattern Alignment
- Follows the Composer-script-to-static-method pattern (`composer.json` `build` → `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php`).
- Follows the one-fixture-per-failure-mode test pattern (`tests/CargoSizesModTests/ShipTypeConfigTest.php`, `tests/files/ship-types/`).
- Follows the Reflection-based private-method test pattern (`tests/CargoSizesModTests/ShipTypeResolutionTest.php`) for the alias message.
- Follows the manifest rule from `AGENTS.md`: version header plus Version History row per manifest change.
- Departure: none. New files are written CRLF to match the working tree.

## Structural Improvements
| Structure | Observation | Decision | Reason |
|-----------|-------------|----------|--------|
| `config/ship-types.json`, `tests/CargoSizesModTests/ShipTypeConfigTest.php`, `tests/files/ship-types/*.json`, 4 docs and 2 manifest files | LF in a CRLF tree | Promoted to step 2 | Prevents build-output drift and removes the inconsistency. |
| `.gitattributes` (missing) | Line endings depend on each contributor's git setting | Promoted to step 2 | Pins the policy for community contributors. |
| `docs/cargo-size-reference.md` | Generated by `MarkdownReference` on every build | Promoted to step 2 (pin by `.gitattributes` after checking the generator's line endings) | A hand-converted generated file flips back on the next build. |
| `src/Mods/CargoSizesMod/ShipTypeConfig.php` | Empty `types` accepted | Promoted to step 5 | Closes a validation hole next to the strict checks. |
| `build/v3-2-0-for-v9/` baseline zips | Fragile, will vanish on a clean | Promoted to step 1 | Moves the only true baseline to the preserved `work/` location. |
| `tests/CargoSizesModTests/ExampleShipSelectionTest.php`, `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php` | Mocks used as stubs, raising notices | Promoted to step 7 | Makes real warnings visible. |
| `phpunit.xml` schema URL (9.5) vs installed PHPUnit 12 | Possible source of the deprecation | Promoted to step 7 (only if confirmed as the source) | Fix only what the deprecation actually names. |
| `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` `createMock()` (L482, L502) | Not producing notices | Rejected | No notice, so no change; avoids unrelated churn. |
| `CargoSizeExtractor::addMessage()` | Static scan of every message literal for non-ASCII would be broader | Rejected | One targeted test on the known regression is enough; a source scan is speculative. |
| Archived plans mentioning `SHIP_SIZES` | Outdated name | Rejected (replaced by one README note in step 8) | See Considered Alternatives. |

## Detailed Steps
1. **Capture the baseline CRCs (no dependencies — run first, before any other step).**
   - Verify `build/v3-2-0-for-v9/` holds the pristine baseline: 26 zips, all with the v3.2.0 name, and compare each zip's entry names and sizes with the matching `## name` section of `work/baseline-ship-types/build-zip-contents.txt`. Stop and report if they disagree.
   - Copy `build/v3-2-0-for-v9/*.zip` and `build/release-notes-v3.2.0.md` to `work/baseline-ship-types/zips/` (new folder). `work/` is gitignored and no step cleans it.
   - Write `work/baseline-ship-types/build-zip-crc.txt` in the same layout as `build-zip-contents.txt` with a third column, the hex CRC32 of each entry (use `ZipArchive::statIndex()['crc']`).
   - Do not run `composer build` in this step. Every later step that compares build output depends on this one.
2. **Normalise line endings (depends on 1).**
   - Run `git ls-files --eol` and list working-tree files with `w/lf`. Convert these 13 files to CRLF (the generated `docs/cargo-size-reference.md` is the one exclusion and is handled by the next bullet): `README.md`, `config/ship-types.json`, `docs/building.md`, `docs/player-guide.md`, `docs/agents/project-manifest/file-tree.md`, `docs/agents/research/2026-11-22-verification-badge-implementation.md`, `docs/agents/implementation-archive/2026-11-22-shared-ship-types-config/synthesis.md`, `gui/docs/project-manifest/data-flows.md`, `tests/CargoSizesModTests/ShipTypeConfigTest.php`, `tests/files/ship-types/*.json` (4 files).
   - Check how `src/Mods/CargoSizesMod/References/MarkdownReference.php` writes `docs/cargo-size-reference.md`. If the generator emits LF, leave the file as generated and add `docs/cargo-size-reference.md text eol=lf` to `.gitattributes`; if it emits CRLF, convert it. Record the finding in a comment line in `.gitattributes`.
   - Create `.gitattributes` (CRLF) with `* text=auto` plus the generated-file rule from the previous point.
   - Confirm `git ls-files --eol` shows no `i/crlf` and that `git status` lists only intended changes (the pre-existing ` M docs/cargo-size-reference.md` comes from the build, not from this step).
3. **Add the comparator class (depends on 1).** New file `src/Mods/CargoSizesMod/Build/BuildParityComparator.php`, namespace `Mistralys\X4\Mods\CargoSizesMod`, with the standard `@package X4 Mods` / `@subpackage Cargo Sizes` docblock and `declare(strict_types=1)`. Responsibilities. After creating this class (and again after the helper classes in step 4), run `composer dump-autoload`: both `autoload` and `autoload-dev` in `composer.json` use `"classmap": ["src"]`, so new classes are not resolvable until the classmap is regenerated.
   - Defines the single class constant `DEFAULT_BASELINE_DIR = 'work/baseline-ship-types'`, used by the comparator, the capture routine and both Composer entry points. Constructor takes the baseline folder (default that constant) and the build folder (default `build`).
   - Pairs each baseline zip in `zips/` with the build zip of the same name once the `_vX.Y.Z` suffix is normalised.
   - Compares the entry sets first, then the CRC32 of each entry's content after normalising the baseline version → current version and the `Build Date:` line. Line endings are never folded: a line-ending change is reported as a content difference.
   - Takes an explicit allow-list of entry name patterns whose content may differ (the random example ship entries; the randomness comes from `array_rand` in `src/Mods/CargoSizesMod/FOMOD/FileCollection.php` and `src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php`). Entry-set equality is evaluated independently of the allow-list: an allow-listed entry missing from either side is still reported as missing or unexpected, and only its content comparison is waived. The Developer determines the real list from the first run and records each pattern with a one-line justification as a class constant.
   - Compares `physics-diagnostics.txt` after normalising the build date.
   - Returns a structured result (matching entries, differing entries, missing and unexpected entries) rather than printing, so tests can assert on it. The result offers a one-line verdict, a plain text listing of the differences and `toArray()` for tests; there is no JSON renderer and no output cap.
   - Baseline layout (what the capture command writes and what Step 1 produces by hand): `<baseline>/zips/*.zip` and `<baseline>/physics-diagnostics.txt`. The comparator reads CRCs from the zips themselves. Step 1 additionally writes `build-zip-crc.txt` into the folder as a one-off human-readable record of the oracle (AC-01); no code reads it and the capture command does not write it.
4. **Expose the comparator and add the capture command (depends on 3).** In `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php`, following the style of `build()`:
   - `compareBaseline()` with Composer script `build:parity`. Option: `--baseline=<dir>` (default `BuildParityComparator::DEFAULT_BASELINE_DIR`). Output starts with one verdict line, `PARITY PASS` or `PARITY FAIL: <n> differing, <m> missing, <k> unexpected`, followed by the differing entries as plain lines. A missing or empty baseline is a setup error whose message names the fix: `No baseline at <dir>. Run: composer build:baseline`.
   - `captureBaseline()` with Composer script `build:baseline`. It runs the same build as `build()`, then copies the current version's zip folder and `physics-diagnostics.txt` into `<baseline>/`. It refuses to overwrite a non-empty baseline folder unless `--force` is given; the refusal message names both fixes (`--force` to replace, `--baseline=<dir>` for another folder). Options: `--baseline=<dir>` (same default constant), `--force`. Its last output line is the baseline folder path.
   - Both commands: no interactive prompts under any circumstance, exit code 0 on success or parity, 1 on differences, 2 on usage or setup error (unknown option, missing baseline, non-empty baseline without `--force`, missing build output). Unknown options print the list of valid ones.
   - Argument handling: the Developer verifies how Composer passes script arguments to a static method (`Composer\Script\Event::getArguments()` is the expected mechanism; `$argv` as a fallback) and implements one small shared parser for the two options, kept as a minimal helper with its own tests (step 6) rather than a general option framework.
   - Composer scripts `build:baseline` and `build:parity` in `composer.json`, each pointing to its static method.
   - Run `composer dump-autoload` after creating the parser and capture classes.
   - Note in the command help text and in the docs that a build rewrites the tracked file `docs/cargo-size-reference.md` as a side effect; that change is expected.
5. **Harden `ShipTypeConfig` and add the validation tests (depends on 2).**
   - In `ShipTypeConfig::load()`, throw `ERROR_INVALID_CONFIG` ("... has no ship types") when `types` is empty. Keep the existing error constant.
   - New fixtures (CRLF) in `tests/files/ship-types/`: `missing-label.json`, `empty-types.json`, `empty-aliases.json` (valid: one type, `"keywordAliases": {}`), `non-string-alias-target.json` (`"scavenger": 5`).
   - Add tests to `tests/CargoSizesModTests/ShipTypeConfigTest.php`: missing label throws; empty types throws; empty aliases loads and resolves a standard keyword; non-string alias target throws; keyword matching is case-sensitive (`ship_arg_l_TRANS_01_a_macro` resolves to `null` for both `resolveType()` and `resolveMacroKeyword()`; a comment states this is the pinned contract).
6. **Test the comparator (depends on 3).** New `tests/CargoSizesModTests/BuildParityComparatorTest.php`. Build small zips in a temp folder per test (never touch the live `build/` or `work/`) and cover: identical sets pass; a changed entry content is reported with its name; a missing and an unexpected entry are reported; version-only differences pass after normalisation; build-date-only differences pass; allow-listed entries may differ in content but are still reported when missing from either set; a CRLF-vs-LF difference is always reported. Also cover the agent-facing behaviour in the same file or a sibling `BuildBaselineCommandTest.php`: the option parser (defaults, `--baseline`, `--force`, unknown option → exit 2 with the valid list); the verdict line is the first output line and the exit codes map 0/1/2; a missing baseline returns exit 2 with the `composer build:baseline` hint; the capture routine (factored so a test can run it against a temp folder and a fake build folder) writes `zips/` and `physics-diagnostics.txt`, refuses a non-empty baseline without `--force` (exit 2), and overwrites it with `--force`.
7. **Pin the ASCII message and remove the notices (depends on 2).**
   - Add `tests/CargoSizesModTests/AliasSkipMessageTest.php`: build the extractor with `newInstanceWithoutConstructor()` (as in `ShipTypeResolutionTest`), call `analyzeShipMacro()` with a stub `ShipXMLFile` whose `getAliasName()` returns a string, capture the console output with `ob_start()` (verified: `Console::line()` echoes; `Console::$enabled` is private with only a `setEnabled()` setter and no getter, so the prior state cannot be read). The test calls `Console::setEnabled(true)` before capture and restores `true` (the default) in `tearDown`. Assert the message equals the expected text and matches `/^[\x00-\x7F]*$/`. Fallback: if capturing the console output proves awkward (unread `Console` implementation, mutable static enabled flag), assert on the message literal at `CargoSizeExtractor.php` L557 (ASCII regex plus expected text) instead. Do not extract a message-formatter method or add any other production seam purely for testability.
   - Run both suites with PHPUnit's detail flags (`--display-phpunit-notices --display-phpunit-deprecations`, or the PHPUnit 12 equivalent) to locate every notice and the deprecation.
   - In `tests/CargoSizesModTests/ExampleShipSelectionTest.php` and `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php`, change `createMock()` to `createStub()` wherever the test sets no `expects()`; keep `createMock()` where it does. Fix the deprecation at its named source (the `phpunit.xml` schema URL if that is the source); if it comes from a third-party package, record that in the Developer's pipeline notes and leave it.
8. **Documentation and manifests (depends on 3–7).** See Documentation Updates.
9. **Exit gate (depends on 1–8).**
   - Run `composer build`, then `composer build:parity` (default folder, which holds the Step 1 baseline): it must pass with only the documented allow-list differing in content. Because line endings were normalised in step 2, a pass also proves step 2 left the build output byte-identical.
   - Smoke-test the capture command end to end into a throwaway folder: `composer build:baseline -- --baseline=work/smoke-baseline`, then `composer build:parity -- --baseline=work/smoke-baseline` must pass with exit code 0; then delete `work/smoke-baseline`. Never use `work/baseline-ship-types` as a capture target.
   - Run `composer test`, `composer analyze`, and in `gui/backend` its PHPUnit and PHPStan. Targets: root and GUI suites green, zero notices, zero deprecations (or the third-party exception recorded), PHPStan clean.
   - Run `git ls-files --eol` and confirm no tracked file is reported `w/lf` except the generated one pinned in `.gitattributes`.

## Dependencies
- Step 1 has no dependencies and must finish before any step that alters source, line endings or builds; steps 2, 3 and 9 consume its output.
- Step 4 and step 6 depend on step 3. Step 5 and step 7 depend on step 2 (so they are authored in the final line-ending state). Step 8 depends on steps 3–7. Step 9 depends on everything.
- Existing: PHP 8.4, PHPUnit 12.x, `ext-zip` (verify it is enabled; the comparator needs `ZipArchive`), `gui/backend/vendor` installed (it is).

## Required Components
- New: `.gitattributes`; `src/Mods/CargoSizesMod/Build/BuildParityComparator.php`; a small shared parser for `--baseline`/`--force` and a baseline capture routine under `src/Mods/CargoSizesMod/Build/` (class names chosen by the Developer, one responsibility each, called from `CargoSizeBuildTools`); `tests/CargoSizesModTests/BuildParityComparatorTest.php`; `tests/CargoSizesModTests/BuildBaselineCommandTest.php`; `tests/CargoSizesModTests/AliasSkipMessageTest.php`; fixtures `tests/files/ship-types/{missing-label,empty-types,empty-aliases,non-string-alias-target}.json`; `work/baseline-ship-types/zips/` and `work/baseline-ship-types/build-zip-crc.txt` (gitignored).
- Modified: `src/Mods/CargoSizesMod/ShipTypeConfig.php`; `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php`; `composer.json`; `tests/CargoSizesModTests/ShipTypeConfigTest.php`; `tests/CargoSizesModTests/ExampleShipSelectionTest.php`; `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php`; possibly `phpunit.xml`; the 14 line-ending files; documentation files listed below.

## Assumptions
- `build/v3-2-0-for-v9/` still contains the pristine baseline zips at the start of the run (verified by mtime and by the Step 1 listing comparison). If the check fails, step 1 stops and the CRC goal falls back to a baseline rebuilt from the pre-change code, which is a user decision, not an in-run step.
- Game data (`X4_EXTRACTED_CAT_FILES_FOLDER`) and `dev-config.php` are available so `composer build` runs.
- `ext-zip` is available in the PHP CLI.

## Constraints
- Constraints from `docs/agents/project-manifest/constraints.md` apply: strict types, synchronous file I/O, no database.
- New and changed files in the working tree are CRLF.
- The comparator and its tests never write to `build/` or `work/`, except for the explicit step 1 capture.
- Do not edit archived plans (history is frozen).

## Out of Scope
- Finalising and releasing v3.2.1 (user action).
- Cross-platform build parity (a Linux contributor's LF checkout would differ byte-wise from the CRLF baseline).
- A baseline-worktree test run (the synthesis marked it moot: no failures existed).
- Any change to `config/ship-types.json` content or to GUI behaviour.
- Rewriting archived plans.

## Human Actions
| # | Action | When | Why an agent cannot do it |
|---|--------|------|---------------------------|
| 1 | Finalise the v3.2.1 release: remove the WIP marker in `changelog.md`, tag and publish | After the run | Releasing and tagging belong to the user. |
| 2 | Run the pending Knowledge Archiver for `2026-11-22-shared-ship-types-config` with the paths listed in the synthesis "Next Steps" | After the run | The dispatch tool was unavailable to the previous run; the user triggers it. |

## Acceptance Criteria
- AC-01: `work/baseline-ship-types/zips/` holds the 26 pristine v3.2.0 zips and `work/baseline-ship-types/build-zip-crc.txt` lists name, size and CRC32 for every entry; the entry names and sizes match the existing `build-zip-contents.txt`.
- AC-02: `.gitattributes` exists with `* text=auto` and the generated-file rule; no tracked file other than the pinned generated one is `w/lf` in `git ls-files --eol`.
- AC-03: `BuildParityComparator` reports identical sets as passing, and reports content, missing and unexpected entry differences by entry name.
- AC-04: The comparator normalises version and build date, supports an allow-list with a justification per pattern (allow-listed entries are still checked for presence), and always reports line-ending differences.
- AC-05: `composer build:parity` exists, starts its output with a one-line verdict, uses the shared default baseline folder constant, and exits 0 on parity, 1 on differences and 2 on usage or setup errors.
- AC-13: `composer build:baseline` builds from the current source, writes the baseline layout (`zips/`, `physics-diagnostics.txt`) into the same default folder constant, refuses to overwrite a non-empty baseline without `--force`, and never prompts.
- AC-14: Both commands are documented for agents in `AGENTS.md` (workflow, the two options and the default folder, exit codes, the `docs/cargo-size-reference.md` side effect, when not to use them), and the documentation tells an agent which command to run first.
- AC-06: After the final `composer build`, `composer build:parity` passes against the Step 1 baseline with only the documented allow-list differing.
- AC-07: `ShipTypeConfig` throws `CargoSizeException` (`ERROR_INVALID_CONFIG`) for an empty `types` section.
- AC-08: Tests pin case-sensitive keyword matching and cover missing label, empty types, empty aliases and non-string alias target.
- AC-09: A test asserts the alias skip message is ASCII-only.
- AC-10: The root and GUI PHPUnit suites report zero notices and zero deprecations (or a documented third-party deprecation), and all tests pass.
- AC-11: PHPStan is clean in the root project and in `gui/backend`.
- AC-12: Manifests and docs are updated and versioned per the Documentation Updates list, including the "Shared Config Pattern" re-verification, the archive `SHIP_SIZES` note and the GUI `public-api.md` version history table.

## Testing Strategy
Unit tests for every new code path (comparator, validation, message). The comparator is exercised on synthetic zips in temp folders. The real-world proof is the exit gate: a fresh build compared against the CRC baseline, run after the line-ending change. Both PHPUnit suites and PHPStan finish clean.

## Test Plan
- `tests/CargoSizesModTests/BuildParityComparatorTest.php::testIdenticalSetsPass` — no differences — AC-03
- `…::testChangedEntryContentIsReported` — differing entry named — AC-03
- `…::testMissingAndUnexpectedEntriesAreReported` — both lists populated — AC-03
- `…::testVersionOnlyDifferencePasses` — v3.2.0 vs v3.2.1 normalised — AC-04
- `…::testBuildDateOnlyDifferencePasses` — date line normalised — AC-04
- `…::testAllowListedEntryMayDifferInContent` — allow-list waives content only — AC-04
- `…::testAllowListedEntryMissingIsStillReported` — allow-listed entry absent from one side is reported missing or unexpected — AC-04
- `…::testLineEndingDifferenceIsAlwaysReported` — CRLF vs LF content is a difference — AC-04
- `tests/CargoSizesModTests/BuildBaselineCommandTest.php::testOptionParserDefaultsAndUnknownOption` — defaults (the shared constant), `--baseline`, `--force`, unknown option → exit 2 listing valid options — AC-05, AC-13
- `…::testVerdictLineIsFirstAndExitCodesMap` — first line is the verdict; 0 on parity, 1 on differences, 2 on setup error — AC-05
- `…::testMissingBaselineReturnsSetupErrorWithHint` — exit 2, message names `composer build:baseline` — AC-05
- `…::testCaptureWritesBaselineLayout` — `zips/` and `physics-diagnostics.txt` written in a temp folder; the result compares as parity — AC-13
- `…::testCaptureRefusesNonEmptyBaselineWithoutForce` and `…::testCaptureOverwritesWithForce` — AC-13
- `tests/CargoSizesModTests/ShipTypeConfigTest.php::testMissingLabelThrows` — fixture `missing-label.json` — AC-08
- `…::testEmptyTypesThrows` — fixture `empty-types.json` — AC-07, AC-08
- `…::testEmptyAliasesIsValid` — fixture `empty-aliases.json` — AC-08
- `…::testNonStringAliasTargetThrows` — fixture `non-string-alias-target.json` — AC-08
- `…::testKeywordMatchingIsCaseSensitive` — uppercase keyword resolves to `null` — AC-08
- `tests/CargoSizesModTests/AliasSkipMessageTest.php::testAliasSkipMessageIsAscii` — expected text and ASCII-only — AC-09
- Existing root suite (`composer test`) and GUI suite (`gui/backend`) — notices removed, still green — AC-10
- `composer analyze` (root and `gui/backend`) — AC-11
- Exit gate: `composer build` then `composer build:parity` against the Step 1 baseline — AC-01, AC-02, AC-05, AC-06
- Exit gate smoke test: `composer build:baseline` into `work/smoke-baseline`, then `composer build:parity` against it, exit 0 — AC-13
- Manual review of the `AGENTS.md` section against the command behaviour — AC-14
- `git ls-files --eol` inspection — AC-02
- Manual diff of the documentation files against the list below — AC-12

## Documentation Updates
- `docs/agents/project-manifest/public-api.md` — add `BuildParityComparator`, `CargoSizeBuildTools::compareBaseline()`, `CargoSizeBuildTools::captureBaseline()`, the option-parser class and the baseline capture class created in step 4, and the `ShipTypeConfig` empty-`types` error; bump version header and add a Version History row.
- `docs/agents/project-manifest/file-tree.md` — add `.gitattributes`, `BuildParityComparator.php`, the option-parser and baseline capture class files, the three new test files (`BuildParityComparatorTest.php`, `BuildBaselineCommandTest.php`, `AliasSkipMessageTest.php`) and the four new fixtures; bump version and history.
- `docs/agents/project-manifest/tech-stack.md` — re-verify the "Shared Config Pattern (Ship Types)" wording against the final code (already reads as migrated; adjust only if it differs); add a short "Build parity check" note with the `composer build:baseline` and `composer build:parity` commands and the new Build-namespace classes; bump version and history.
- `docs/agents/project-manifest/constraints.md` — add a line-ending rule (CRLF working tree, `.gitattributes`, generated-file exception) and the empty-`types` validation rule; bump version and history.
- `docs/agents/project-manifest/data-flows.md` — the file has a "Complete Build Flow (Detailed)" section (Steps 1-10) but no parity section. Add a new section "Parity Check Flow" after Step 10 (ZIP Packaging) and before "Key Data Transformations": `composer build:baseline` (build, snapshot `zips/` and `physics-diagnostics.txt`) then, after changes, `composer build` and `composer build:parity` (baseline zips → comparator → verdict and exit code). Add a Version History row.
- `AGENTS.md` — add a section "Output-parity check for refactors" (agent-facing, CRLF): when to use it (refactors and cleanups that must not change the mod output; not for features that intentionally change it); the workflow (1 `composer build:baseline` before editing anything, 2 make the change, 3 `composer build`, 4 `composer build:parity`); the two options (`--baseline`, `--force`) and the single default folder `work/baseline-ship-types`; the exit codes 0/1/2; the overwrite guard (after this plan the v3.2.0 baseline there has served its purpose and may be replaced with `--force`, or a new refactor can use `--baseline=work/<name>`); that the baseline lives in the gitignored `work/` folder; that a build modifies the tracked `docs/cargo-size-reference.md`; and the allow-list rationale for the random example ships. Add a row to the "Change → Document Mapping Table" and a line under "Common Tasks"; bump the file's version and last-updated date.
- `docs/building.md` — one short paragraph pointing contributors to the `AGENTS.md` section; no detailed workflow (the feature is for agents).
- `docs/agents/implementation-archive/README.md` — add a note that `SHIP_SIZES` in archived plans is now `ShipTypeConfig::SIZES`.
- `gui/docs/project-manifest/public-api.md` — bump the version header and add a Version History table in the CLI manifests' format, seeded with the rows for 1.7 and the current version (history before 1.7 is not reconstructed).
- `gui/docs/project-manifest/*` (other files) — only if they reference changed test files; otherwise unchanged.
- `changelog.md` — no entry (no user-facing change); the WIP v3.2.1 marker stays as is.

## Deferred Items
| # | Deferred Item | Origin | Reason Deferred | Notes |
|---|---------------|--------|-----------------|-------|
| 1 | Cross-platform byte-parity (LF checkouts on Linux/macOS) | Own finding, line-endings area | Needs a decision on a canonical build line ending and a baseline per platform | Reconsider when a non-Windows contributor builds the mod. |
| 2 | Baseline-worktree test run | Synthesis, WP-004 | No failures existed, so it adds nothing | Closed as moot. |
| 3 | Finalising v3.2.1 and the pending Knowledge Archiver run | Synthesis, Release / Next Steps | User-owned | Listed under Human Actions. |
| 4 | Parity command extras: `--format=json`, a text output cap, `--fold-line-endings`, `--no-build`, and a `build-zip-crc.txt` listing written by the capture command | Design review decisions 1 and 3 | No named consumer beyond this plan's exit gate; each adds option-parser surface and tests | Reconsider when a second consumer (CI, a cross-platform baseline) needs machine-readable output or line-ending folding. |
| 5 | Seeding the random example ship selection so the oracle needs no allow-list | Design review decision 2 | Touches production selection code in `FileCollection.php` and `ReleaseNotesGenerator.php`, which this plan keeps output-neutral | Reconsider in a plan that already changes example ship selection. |

## Risks & Mitigations
| Risk | Mitigation |
|------|------------|
| **An agent captures over a baseline that is still needed** | `build:baseline` refuses a non-empty folder without `--force`; the docs name the default folder and warn against reusing a baseline folder; the smoke test uses its own throwaway folder. |
| **Composer script argument passing differs from expectation** | Step 4 makes verifying it the first task, with `$argv` as a fallback, and the option parser is unit-tested independent of Composer. |
| **A baseline is captured from an already-modified source tree** | The `AGENTS.md` workflow orders `composer build:baseline` before any edit, and the docs state this as the first rule. |
| **Baseline zips in `build/` are overwritten or deleted before step 1** | Step 1 runs first with no dependencies and verifies the zips against the existing text listing; the plan stops if they disagree. |
| **CRLF conversion changes embedded build strings** | Only non-source docs and test files are converted; the exit gate compares CRCs after the conversion, so any effect is caught. |
| **Allow-list hides a real regression** | Each entry carries a justification; the list is a named constant limited to the random example ship entries and reviewed in code review. Entry-set equality is checked independently, so an allow-listed entry cannot go missing unnoticed; only its content comparison is waived. |
| **Empty-`types` rejection breaks a consumer that relies on it** | Both consumers load the shipped config with four types; the existing suites in both layers run in the exit gate. |
| **Alias message capture does not work with `Console`** | `Console::line()` echoes, so `ob_start()` captures it; the test enables the console and restores the default `true` in `tearDown`. If capture still proves awkward, fall back to asserting on the message literal. No production seam is added for testability. |
| **The deprecation comes from a third-party package** | Record the cause and leave it; AC-10 allows a documented third-party exception. |
| **`.gitattributes` surprises other tooling** | The index is already LF, so `text=auto` is a no-op on tracked content; verified via `git ls-files --eol` and `git status` in step 2. |

## Recommended Workflow
- **Workflow:** standalone
- **Rationale:** The work is one coherent hardening pass inside a single repository and a well-understood pattern set, so a single developer session with self-review suffices.
