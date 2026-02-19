# Synthesis Report — Preserve Original Flight

> **Plan:** Preserve Original Flight — Full Refactoring  
> **Date:** February 19, 2026  
> **Status:** ✅ COMPLETE  
> **Work Packages:** 9 / 9 COMPLETE  
> **Total Rework Events:** 2  

---

## Executive Summary

The `preserve-original-flight` plan delivered a complete architectural replacement of the tier-based physics adjustment system across the CLI build tool and the Physics Tuning GUI. The fundamental insight driving the refactor is correct and validated: in X4's physics model, only acceleration is mass-dependent. The previous implementation modified drag, inertia, jerk, and steering — all of which are fixed XML values unrelated to mass — causing active flight fidelity degradation. This plan removed the entire tier system and replaced it with a single formula:

```
newAccel = originalAccel × massRatio × responsiveness
```

The scope covered three distinct layers:

1. **CLI build tool** — Deleted 10 dead source files (Jerk namespace, AdjustedDrag, AdjustedInertia, PhysicsOverrideDef, ReductionTier), simplified `BuildConfig` from 12 down to 2 public methods, rewrote `FlightMechanicsOverrideFile` and `DiagnosticsLogger`, created the new `AccelerationOverrideDef`, and reconstructed the test suite.

2. **GUI backend (PHP)** — Rewrote `PhysicsService`, `ClassRangeService`, `ConfigService`, and all associated DTOs; deleted 3 dead utility classes.

3. **GUI frontend (TypeScript / React)** — Simplified 11 components and type definitions across `physics.d.ts`, `config.d.ts`, `App.tsx`, `ConfigPanel`, `ResultsPanel`, and `ClassRangePanel`; deleted `TierEditor.tsx`.

4. **Documentation** — Updated 15+ manifest, user-facing, and API documents to reflect the simplified acceleration-only approach.

**Generated XML footprint reduced from ~60 lines per ship per multiplier to ~10 lines.**

---

## Metrics

### CLI Test Suite

| WP | Tests | Assertions | Failures | PHPStan |
|----|-------|-----------|----------|---------|
| WP-001 baseline | 22 pass | — | 0 | 0 errors |
| WP-004 final | 59 pass | 145 | 0 | 0 errors (51 files) |
| WP-005 final | **47 pass** | — | 0 | 0 errors (51 files) |

> Note: The test count drop (59 → 47) between WP-004 and WP-005 reflects the correct deletion of obsolete `InertiaAdjustmentTest` (the inertia class was removed). WP-005 also added the new `AccelerationOverrideTest` (5 tests).

### GUI Backend Test Suite

| WP | Tests | Assertions | Failures | PHPStan |
|----|-------|-----------|----------|---------|
| WP-006 | 49 pass | — | 0 | 0 errors (30 files) |
| WP-007 final | **49 pass** | **986** | 0 | 0 errors (30 files) |

### GUI Frontend Build

| WP | Modules | TypeScript Errors |
|----|---------|------------------|
| WP-008 | 111 transformed | 0 |

### Combined Final State

| Layer | Tests Passing | Static Analysis |
|-------|--------------|-----------------|
| CLI | 47 / 47 | ✅ Clean |
| GUI Backend | 49 / 49 | ✅ Clean |
| GUI Frontend | Build: 111 modules | ✅ 0 TS errors |
| **Total** | **96 tests** | **✅ All clean** |

### Code Review Scores

| WP | Score | Critical Issues |
|----|-------|----------------|
| WP-001 | 92/100 | 0 |
| WP-002 | 88/100 | 0 |
| WP-003 | 90/100 | 0 |
| WP-004 (1st pass) | 70/100 | 1 — BLOCKING (tautological test) |
| WP-004 (2nd pass) | **100/100** | 0 |
| WP-005 | 90/100 | 0 |
| WP-006 | 90/100 | 0 |
| WP-007 | 90/100 | 0 |
| WP-008 | 90/100 | 0 |
| WP-009 | **90/100** | 0 |

---

## Failures & Rework

Two work packages required rework before completion.

### WP-004 — Code Review FAIL → Rework → PASS

**Blocking issue:** `InertiaAdjustmentTest::testMassRatioDrivesInertia` was a tautological test. Both `$inertiaA` and `$inertiaB` were derived from the same `PhysicsCalculator` instance with the same formula. The assertion `assertEqualsWithDelta($inertiaA, $inertiaB, 0.1)` was trivially true and tested nothing. The fix replaced it with two distinct calculator instances (low mass-ratio ~2.818x vs high mass-ratio ~9.956x) with a meaningful cross-comparison.

**Additional fixes in the same pass:** 5 non-blocking issues — stale `WithCap` method name, stale comment, dead assignment, `strpos` → `str_contains`, and method rename to `testFormatMassRatio`.

**Impact:** +1 assertion (145 vs 144), +10 points in review score.

### WP-009 — QA FAIL → Rework → PASS

**Blocking issue:** `gui/docs/project-manifest/public-api.md` TypeScript types section (lines 1007–1322) still documented the old tier-based interfaces (`AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk`, `Tier`, plus full tier fields in `PhysicsConfig`, `PhysicsResponse`, `ClassRangeRequest`, `FlightMechanics`, `ShipMetricSummary`).

**Additional issues fixed:** `docs/agents/project-manifest/README.md` still showed Jerk/ directory and stale class counts; `constraints.md` used deleted `AdjustedDrag` as illustrative example in 3 places.

**Impact:** All 3 bugs resolved. Stale `PhysicsCalculationHelper` trait block in `gui/docs/ARCHITECTURE.md` also cleaned in the documentation pass.

---

## Technical Debt Registered (Deferred)

The following items were explicitly logged as deferred by agents during the session. They do not block the plan but represent known gaps.

| Priority | Location | Issue |
|----------|----------|-------|
| **Medium** | `BuildConfig::validate()`, `PhysicsCalculator::validate()`, `ConfigService` | `ERROR_UNHANDLED_SHIP_TYPE` is reused for numeric/config validation errors everywhere. A dedicated `ERROR_INVALID_CONFIG` or `ERROR_INVALID_CALCULATOR_PARAMS` constant is needed across both the CLI and GUI backend. |
| **Medium** | `BuildConfig::validate()` | No guard against an empty `cargo-multipliers` array. A build with an empty array silently produces no output. Should throw on empty. |
| **Medium** | `gui/backend/coverage/` | Stale auto-generated HTML coverage reports reference the old tier-based code. Should be regenerated after re-running backend tests. |
| **Low** | `FlightMechanicsOverrideFile` | Static `$diagnosticsLogger` property causes PHPUnit test isolation risk. States persist across tests unless `clearDiagnosticsLogger()` is explicitly called in `tearDown()`. |
| **Low** | `FlightMechanicsOverrideFile::calculateMassAdjustment()` | `logToDiagnostics()` is called as a side effect inside a calculation method. Should be moved to `preRender()` for SRP compliance. |
| **Low** | `PhysicsRequest`, `PhysicsResponse` | Mixed readonly/mutable constructor properties — inconsistent with the value object convention established by `PhysicsResponseData` and `ClassRangeRequest` (both all-readonly). Align all DTO constructor params to `readonly`. |
| **Low** | `AccelerationOverrideDef::renderTag()` | `getValues()` is called twice (once for keys, once for values). Assign to a local variable to eliminate the redundant call. Also: `@return array<string,string>` annotation is missing. |
| **Low** | `docs/agents/project-manifest/README.md` | Top-level `Total Classes: 49 PHP files` counter and namespace class counts are not reconciled with the deleted Jerk namespace. Slightly incorrect. |
| **Low** | `gui/docs/project-manifest/public-api.md` | TypeScript interfaces for `ClassRangeRequest`, `RangeMetric`, `ShipMetricSummary`, `ClassRangeResponse` are not documented in the TS types section. Minor doc gap. |
| **Low** | `ClassRangeServiceTest::testCalculateClassRangeWithMockedService()` | Misleading inline comment says "this test will fail without full X4 Core infrastructure" — it doesn't fail; it correctly expects a `GUIException`. |
| **Low** | `ConfigServiceTest::testTempFileCleanup()` | Calls `$this->tearDown()` directly inside a test body — anti-pattern for PHPUnit lifecycle hooks. Should inline the `unlink()` call. |
| **Low** | `DiagnosticsLogger::generateReport()` | ~70 line monolithic method. Consider extracting `buildReportHeader()`, `buildShipSection()`, `buildReportSummary()` private helpers for future maintainability. |

---

## Strategic Recommendations — Gold Nuggets

These cross-cutting insights emerged from agent review comments and are worth addressing as a cohesive batch rather than individual one-off fixes.

### 🥇 Gold Nugget 1: Error Code Architecture Overhaul (High Value)

The `ERROR_UNHANDLED_SHIP_TYPE` constant is being recycled as a catch-all for at least 5 semantically distinct failure conditions across `BuildConfig`, `PhysicsCalculator`, and `ConfigService`. This makes error handling opaque in production and debugging painful. A single housekeeping WP should:

1. Add `ERROR_INVALID_CONFIG` to `CargoSizeException`
2. Add `ERROR_INVALID_CALCULATOR_PARAMS` to `CargoSizeException`
3. Add `ERROR_CONFIG_INVALID` to `GUIException`
4. Replace all misused `ERROR_UNHANDLED_SHIP_TYPE` occurrences in validation paths
5. Add the empty-multipliers guard in `BuildConfig::validate()`

**Estimated scope:** 1 WP, 4–5 files.

### 🥈 Gold Nugget 2: DTO Value Object Hardening (Medium Value)

Three out of four physics-domain DTOs are full value objects (all-`readonly`). The exception is `PhysicsRequest` and `PhysicsResponse`, which have mixed mutability. This inconsistency creates a subtle maintenance risk and was flagged independently by both the CLI Reviewer (WP-002) and the GUI Reviewer (WP-006). A single WP should declare all constructor parameters `readonly` on both DTOs and re-run PHPStan to confirm no accidental post-construction mutation exists.

**Estimated scope:** 1 WP, 2–3 files.

### 🥉 Gold Nugget 3: FlightMechanicsOverrideFile SRP Cleanup (Low-Medium Value)

Two related low-priority issues compound to a meaningful structural improvement opportunity:

1. `logToDiagnostics()` as a side effect inside `calculateMassAdjustment()` violates SRP.
2. The static `$diagnosticsLogger` creates test isolation risk as the test suite grows.

Combined fix: make `$diagnosticsLogger` an instance property (or inject it via constructor/setter per-instance), and move the logging call to `preRender()`. This will also remove the test isolation risk without requiring explicit `tearDown()` clearing.

**Estimated scope:** 1 WP, 1 source file + 1 test update.

### 💡 Gold Nugget 4: ClassRangeService Internal DTO (Future Consideration)

`ClassRangeService::calculateShipMetrics()` returns a complex raw array type. The Reviewer (WP-006) flagged that introducing a small internal `ShipMetricsRow` struct would improve type safety, reduce docblock verbosity, and make the service more extensible when new metrics are added. This is not urgent now but is the right call before the next feature that adds a new class-range metric.

---

## Files Produced / Modified

### New Files
- `src/Mods/CargoSizesMod/Output/Physics/AccelerationOverrideDef.php`
- `tests/CargoSizesModTests/AccelerationOverrideTest.php`

### Deleted Files (CLI)
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php`
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php`
- `src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php`
- `src/Mods/CargoSizesMod/Output/Physics/Jerk/` (entire directory — 5 files)
- `src/Mods/CargoSizesMod/Build/ReductionTier.php`
- `tests/CargoSizesModTests/TierSystemTest.php`
- `tests/CargoSizesModTests/DragAdjustmentTest.php`
- `tests/CargoSizesModTests/JerkAdjustmentTest.php`
- `tests/CargoSizesModTests/InertiaAdjustmentTest.php`

### Deleted Files (GUI)
- `gui/backend/src/DTOs/PhysicsData.php`
- `gui/backend/src/DTOs/ReductionTiers.php`
- `gui/backend/src/Utils/PhysicsCalculationHelper.php`
- `gui/backend/tests/Unit/Utils/PhysicsCalculationHelperTest.php`
- `gui/frontend/src/components/ConfigPanel/TierEditor.tsx`

### Significantly Rewritten (CLI)
- `src/Mods/CargoSizesMod/Build/BuildConfig.php` — 12 → 2 public methods
- `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php` — tier/drag/inertia/jerk logic removed
- `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php` — tier reporting removed
- `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php` — effective ratio cap removed

### Significantly Rewritten (GUI)
- `gui/backend/src/Services/PhysicsService.php`
- `gui/backend/src/Services/ClassRangeService.php`
- `gui/backend/src/Services/ConfigService.php`
- `gui/backend/src/DTOs/PhysicsRequest.php`, `PhysicsResponse.php`, `PhysicsResponseData.php`, `ClassRangeRequest.php`
- `gui/frontend/src/types/physics.d.ts`, `config.d.ts`
- `gui/frontend/src/components/ResultsPanel/DiagnosticsPanel.tsx`, `ComparisonView.tsx`, `ClassRangePanel.tsx`

### Configuration
- `config/build-config.json` — 9 flight-mechanics keys → 1 key
- `config/custom-builds/irukandji.json` — aligned schema + key rename fix

### Documentation (15 files)
All CLI and GUI project manifests, `GUI API.md`, `ARCHITECTURE.md`, `physics-tuning-guide.md`, and root `README.md` updated to reflect the acceleration-only approach.

---

## Next Steps — Recommendations for Planner

In priority order:

1. **Housekeeping WP: Error Code Architecture** — The `ERROR_UNHANDLED_SHIP_TYPE` misuse spans CLI and GUI backend. A single focused WP fixes all instances plus adds the empty-multipliers guard (see Gold Nugget 1).

2. **Housekeeping WP: DTO Readonly Alignment** — Two DTO classes (`PhysicsRequest`, `PhysicsResponse`) need all constructor params declared `readonly` to match the value object convention in the codebase (see Gold Nugget 2).

3. **Housekeeping WP: FlightMechanicsOverrideFile SRP** — Move `logToDiagnostics()` call from `calculateMassAdjustment()` to `preRender()`, and consider deprivatizing the static logger (see Gold Nugget 3).

4. **Regenerate GUI coverage reports** — Run `composer test` under `gui/backend/` to regenerate the HTML coverage directory and flush stale tier-based references.

5. **Reconcile README.md class counts** — `docs/agents/project-manifest/README.md` top-level class counts are slightly off after the Jerk namespace deletion. Worth a 15-minute pass.

---

*Synthesis generated by Head of Operations (Synthesis Agent v3.1.2) — February 19, 2026.*
