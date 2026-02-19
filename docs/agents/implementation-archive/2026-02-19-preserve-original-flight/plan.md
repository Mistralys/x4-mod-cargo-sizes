# Plan: Preserve Original Flight — Full Refactoring

## Summary

Replace the entire tier-based physics adjustment system with a mathematically minimal **acceleration-only** approach. The X4 physics model shows that only acceleration is mass-dependent; drag (top speed), inertia, jerk, and steering are fixed XML values unrelated to mass. The current system modifies all of these, actively degrading flight fidelity. This plan removes the tier-based system entirely — including all drag/inertia/jerk/steering adjustment classes, config keys, GUI controls, and tests — and replaces it with a single acceleration scaling calculation: `newAccel = originalAccel × massRatio × responsiveness`.

Backward compatibility is a non-issue. We have complete implementation and refactoring freedom.

## Approach / Architecture

### Before (Current State)
```
build-config.json → 9 flight-mechanics keys → BuildConfig → FlightMechanicsOverrideFile
  → PhysicsOverrideDef (replaces entire <physics>: drag + inertia + accel)
  → JerkOverrideDef (replaces entire <jerk>)
  → Steering curve overrides
  → ~60 lines of XML per ship per multiplier
```

### After (Target State)
```
build-config.json → 1 flight-mechanics key → BuildConfig → FlightMechanicsOverrideFile
  → AccelerationOverrideDef (replaces only <acceleration>)
  → ~10 lines of XML per ship per multiplier
```

### Key Principles
1. **Remove, don't branch** — No config flag, no dual-mode. The tier-based system is deleted.
2. **Acceleration is the only lever** — `newAccel = originalAccel × massRatio × responsiveness`
3. **One knob** — `accelerationResponsiveness` (default `1.0`) is the single tuning parameter
4. **Smaller surface** — Generated XML targets only `properties/thruster/acceleration`, not `properties/physics` or `properties/jerk`

### XML Output (New)

```xml
<diff>
  <replace sel="...properties/thruster/acceleration">
    <acceleration forward="7.88" reverse="1.38" horizontal="2.17" vertical="2.76" />
    <!-- AccelerationFactor scaled by mass ratio 3.94x (responsiveness: 1.0) -->
    <!-- Original: forward=2.0, reverse=0.35, horizontal=0.55, vertical=0.70 -->
  </replace>
</diff>
```

## Rationale

The X4 physics model makes this refactoring not just safe, but **corrective**:

- **Top speed** (`v_max = thrust / drag`): Mass not a factor. The current drag reduction is a pure speed buff, not compensation. **Removing drag override: no impact on intended behavior.**
- **Handling** (inertia/jerk/steering): Fixed XML values, not derived from mass. The current modifications actively worsen handling beyond what mass change causes. **Removing these overrides: ships handle better, not worse.**
- **Acceleration** (`a = thrust × accelFactor / mass`): The only mass-dependent metric. Scaling `AccelerationFactors` by the mass ratio preserves the original acceleration at full cargo. **This is the only override needed.**

The existing complexity (9 config parameters, 5 tier-based classes, 6 adjusted-value classes, 2 override defs, 60+ lines of XML per ship) exists to solve a problem that doesn't exist in X4's physics model.

## Detailed Steps

### Phase 1: CLI — New AccelerationOverrideDef

1. **Create `AccelerationOverrideDef`**
   - New file: `src/Mods/CargoSizesMod/Output/Physics/AccelerationOverrideDef.php`
   - Namespace: `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`
   - Extends: `TagOverrideDef`
   - Constructor: `(string $macroName, AdjustedAccelerationFactors $accelerationFactors)`
   - Targets path: `properties/thruster/acceleration`
   - Tag name: `acceleration`
   - XML template with `forward`, `reverse`, `horizontal`, `vertical` attributes
   - Comments showing original vs. adjusted values and scaling factor
   - Follows pattern of existing `PhysicsOverrideDef` / `JerkOverrideDef` (custom `renderTag()` with template)

### Phase 2: CLI — Simplify FlightMechanicsOverrideFile

2. **Rewrite `FlightMechanicsOverrideFile`**
   - File: `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php`
   - **Remove** methods: `overridePhysics()`, `overrideJerk()`, `overrideSteeringCurve()`, `resolveInertiaValues()`, `resolveDragValues()`
   - **Remove** properties: `$dragReductionMultiplier`, `$jerkReductionPercent`, `$steeringIncreaseMultiplier`, `$inertiaIncreaseMultiplier`
   - **Remove** imports: `AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk`, `ReductionTier`, `PhysicsOverrideDef`, `JerkOverrideDef`, `Jerk`, `JerkBoost`, `BaseJerkMovement`
   - **Simplify** `preRender()` to:
     1. Calculate `MassAdjustment` (mass ratio)
     2. Calculate acceleration scaling: `massRatio × responsiveness`
     3. Create `AdjustedAccelerationFactors`
     4. Add `AccelerationOverrideDef` via `addCustomOverride()`
     5. Add explanatory comments
   - **Simplify** `calculateMassAdjustment()` — remove all tier lookup logic, remove legacy factor-based branch
   - **Keep**: `resolveAccelerationValues()`, `getXMLFile()`, `getName()`, diagnostics logging (updated)

3. **Simplify `DiagnosticsLogger`**
   - File: `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php`
   - **Remove** `ReductionTier` parameters from `logShip()`
   - **Remove** tier-specific logging (drag tier, jerk tier, inertia impact factor)
   - **Simplify** to log: ship name, mass ratio, acceleration scaling factor, responsiveness
   - Update `generateReport()` to remove tier-specific sections

### Phase 3: CLI — Simplify BuildConfig

4. **Simplify `build-config.json`**
   - File: `config/build-config.json`
   - **Remove** keys: `dragReductionFactor`, `steeringIncreaseFactor`, `inertiaIncreaseFactor`, `dragReductionTiers`, `jerkReductionTiers`, `inertiaImpactFactor`, `useEffectiveRatioCap`
   - **Keep**: `cargo-multipliers`, `accelerationResponsiveness`
   - Result:
     ```json
     {
       "cargo-multipliers": [2, 4, 6, 8, 10],
       "flight-mechanics": {
         "accelerationResponsiveness": 1.0
       }
     }
     ```

5. **Simplify `BuildConfig` class**
   - File: `src/Mods/CargoSizesMod/Build/BuildConfig.php`
   - **Remove** constants: `KEY_DRAG_REDUCTION_FACTOR`, `KEY_STEERING_INCREASE_FACTOR`, `KEY_INERTIA_INCREASE_FACTOR`, `KEY_DRAG_REDUCTION_TIERS`, `KEY_JERK_REDUCTION_TIERS`, `KEY_INERTIA_IMPACT_FACTOR`, `KEY_USE_EFFECTIVE_RATIO_CAP`
   - **Remove** properties: `$dragReductionTiers`, `$jerkReductionTiers`
   - **Remove** methods: `getDragReductionFactor()`, `getSteeringIncreaseFactor()`, `getInertiaIncreaseFactor()`, `getDragReductionTiers()`, `getJerkReductionTiers()`, `findDragTierForMultiplier()`, `findJerkTierForMultiplier()`, `hasTierBasedConfiguration()`, `getInertiaImpactFactor()`, `getUseEffectiveRatioCap()`
   - **Keep**: `getMultipliers()`, `getAccelerationResponsiveness()`
   - **Simplify** constructor to only parse `cargo-multipliers` and `accelerationResponsiveness`

6. **Simplify `config/custom-builds/irukandji.json`**
   - Remove the same tier-based keys to match new schema

### Phase 4: CLI — Delete Obsolete Classes

7. **Delete tier-based and adjusted-value files**
   - **Delete**: `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php`
   - **Delete**: `src/Mods/CargoSizesMod/Output/Jerk/JerkOverrideDef.php`
   - **Delete**: `src/Mods/CargoSizesMod/Build/ReductionTier.php`
   - **Keep**: `AdjustedAccelerationFactors.php`, `AdjustedValuesInterface.php`, `AdjustedValuesTrait.php` (still used by acceleration)
   - **Keep**: `MassAdjustment.php`, `PhysicsCalculator.php` (still needed for mass ratio calculations)

8. **Simplify `PhysicsCalculator`**
   - File: `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php`
   - **Remove**: `$useEffectiveRatioCap` constructor parameter
   - **Remove**: `getEffectiveRatio()`, `formatEffectiveRatio()` methods (effective ratio cap concept removed)
   - **Keep**: `getMassRatio()`, `getBaseMass()`, `getOriginalFullMass()`, `getAdjustedFullMass()`, `getMassIncrease()`, `getMassIncreasePercent()`, `getInverseMassRatio()`, `getMassRatioSquared()`, `getValidationWarnings()`, `formatMassRatio()`, `getDebugInfo()`

### Phase 5: CLI — Update Tests

9. **Delete obsolete test files**
   - **Delete**: `tests/CargoSizesModTests/DragAdjustmentTest.php`
   - **Delete**: `tests/CargoSizesModTests/InertiaAdjustmentTest.php`
   - **Delete**: `tests/CargoSizesModTests/JerkAdjustmentTest.php`
   - **Delete**: `tests/CargoSizesModTests/TierSystemTest.php`

10. **Update remaining test files**
    - **Update**: `tests/CargoSizesModTests/AccelerationAdjustmentTest.php` — Remove any effective-ratio-cap tests; ensure pure mass-ratio scaling tests remain
    - **Update**: `tests/CargoSizesModTests/PhysicsCalculatorTest.php` — Remove effective-ratio-cap tests; remove tier references

11. **Add new test file**
    - **Create**: `tests/CargoSizesModTests/AccelerationOverrideTest.php`
    - Test cases:
      - `testAccelerationOverrideXMLTargetsCorrectPath` — verify XPath is `properties/thruster/acceleration`
      - `testAccelerationOverrideRendersAllFourAxes` — verify `forward`, `reverse`, `horizontal`, `vertical` in output
      - `testAccelerationScalingMatchesMassRatio` — verify `newAccel = originalAccel × massRatio × responsiveness`
      - `testResponsivenessAttenuation` — verify `responsiveness < 1.0` reduces acceleration scaling
      - `testResponsivenessDefault` — verify `responsiveness = 1.0` gives pure mass ratio scaling

### Phase 6: GUI Backend — Simplify Services

12. **Simplify `PhysicsRequest` DTO**
    - File: `gui/backend/src/DTOs/PhysicsRequest.php`
    - **Remove** properties: `$useEffectiveRatioCap`, `$dragReductionFactor`, `$inertiaImpactFactor`, `$dragReductionTiers`, `$jerkReductionTiers`
    - **Keep**: `$baseMass`, `$originalCargo`, `$adjustedCargo`, `$cargoMultiplier`, `$accelerationResponsiveness`, `$shipId`, `$engineId`
    - Update `fromArray()` accordingly

13. **Simplify `ClassRangeRequest` DTO**
    - File: `gui/backend/src/DTOs/ClassRangeRequest.php`
    - **Remove** same tier-related properties as `PhysicsRequest`
    - **Keep**: `$shipType`, `$cargoMultiplier`, `$accelerationResponsiveness`, `$engineId`

14. **Simplify `PhysicsResponse` DTO**
    - File: `gui/backend/src/DTOs/PhysicsResponse.php`
    - **Remove**: All drag original/adjusted/percentChange fields, all inertia original/adjusted/percentChange fields, all jerk original/adjusted/percentChange fields, `$effectiveRatio`, `$activeTier`
    - **Keep**: `$massRatio`, `$originalFullMass`, `$adjustedFullMass`, `$massIncrease`, `$accelerationScalingFactor`, `$enginePerformance` (top speed, TWR, acceleration)
    - Add new field: `$accelerationResponsiveness` for display

15. **Delete obsolete DTOs**
    - **Delete**: `gui/backend/src/DTOs/PhysicsData.php` (wrapped drag/inertia/jerk adjustment objects — all removed)
    - **Delete**: `gui/backend/src/DTOs/ReductionTiers.php` (wrapped two `ReductionTier` instances)

16. **Simplify `PhysicsResponseData` DTO**
    - File: `gui/backend/src/DTOs/PhysicsResponseData.php`
    - **Remove**: `PhysicsData` and `ReductionTiers` parameters
    - **Keep**: `PhysicsCalculator`, `PhysicsRequest`, `?EnginePerformance`

17. **Simplify `PhysicsService`**
    - File: `gui/backend/src/Services/PhysicsService.php`
    - **Remove** imports: `AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk`, `ReductionTier`, `Drag`, `Inertia`, `Jerk`, `JerkForward`, `JerkBoost`, `JerkTravel`
    - **Remove** from `calculatePhysics()`: drag tier lookup, jerk tier lookup, drag adjustment, inertia adjustment, jerk adjustment
    - **Simplify** to: create `PhysicsCalculator` → optionally calculate engine performance (using **original** drag for top speed) → build simplified response
    - **Remove** `PhysicsCalculationHelper` trait usage (its methods are no longer needed here)
    - Engine performance: use original drag for `topSpeed = thrust × engineCount / forwardDrag` (drag is no longer modified)

18. **Simplify `ClassRangeService`**
    - File: `gui/backend/src/Services/ClassRangeService.php`
    - **Remove** imports: `AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk`, `ReductionTier`, `Drag`, `Inertia`
    - **Remove** tier lookup, drag/inertia/jerk adjustment logic
    - **Simplify** to: iterate ships → calculate mass ratio per ship → compute acceleration scaling → aggregate min/max/median
    - **Remove** `PhysicsCalculationHelper` trait usage

19. **Delete `PhysicsCalculationHelper` trait**
    - **Delete**: `gui/backend/src/Utils/PhysicsCalculationHelper.php`
    - Methods `findTierForMultiplier()`, `calculateAverageDragChange()`, `calculateAverageInertiaChange()` are no longer needed
    - `calculatePercentChange()` can be inlined where still needed

20. **Simplify `ConfigService`**
    - File: `gui/backend/src/Services/ConfigService.php`
    - **Remove** validation for: `inertiaImpactFactor`, `useEffectiveRatioCap`, `dragReductionTiers`, `jerkReductionTiers`, and legacy factor keys
    - **Keep** validation for: `cargo-multipliers` (array of positive numbers) and `accelerationResponsiveness` (float, 0.1–5.0)

### Phase 7: GUI Backend — Update Tests

21. **Delete obsolete test file**
    - **Delete**: `gui/backend/tests/Unit/Utils/PhysicsCalculationHelperTest.php` (tests for deleted trait)

22. **Update remaining GUI backend tests**
    - **Update**: `gui/backend/tests/Unit/Services/PhysicsServiceTest.php` — Remove tier/drag/inertia/jerk assertions; test acceleration-only response
    - **Update**: `gui/backend/tests/Unit/Services/ConfigServiceTest.php` — Remove tier validation tests; test simplified config schema
    - **Update**: `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php` — Remove tier/drag/inertia assertions; test mass-ratio-based acceleration ranges
    - **Update**: `gui/backend/tests/Unit/DTOs/PhysicsResponseDataTest.php` — Remove `PhysicsData` and `ReductionTiers` from test setup

### Phase 8: GUI Frontend — Simplify UI

23. **Simplify TypeScript types**
    - File: `gui/frontend/src/types/config.d.ts`
    - **Remove**: `dragReductionFactor`, `steeringIncreaseFactor`, `inertiaIncreaseFactor`, `dragReductionTiers`, `jerkReductionTiers`, `inertiaImpactFactor`, `useEffectiveRatioCap`
    - **Keep**: `cargoMultipliers` (or equivalent), `accelerationResponsiveness`

    - File: `gui/frontend/src/types/physics.d.ts`
    - **Remove**: All drag/inertia/jerk adjusted types/interfaces, `effectiveRatio`, `activeTier`, tier request parameters
    - **Keep**: `massRatio`, `accelerationScalingFactor`, `enginePerformance`

24. **Simplify `useConfig` hook**
    - File: `gui/frontend/src/hooks/useConfig.ts`
    - **Remove** defaults for: all tier-based config keys
    - **Keep**: `cargoMultipliers` defaults, `accelerationResponsiveness` default

25. **Simplify `App.tsx`**
    - File: `gui/frontend/src/App.tsx`
    - **Remove**: State/props for drag/inertia/jerk/tier configs
    - **Simplify** physics request payload to: `baseMass`, `originalCargo`, `adjustedCargo`, `cargoMultiplier`, `accelerationResponsiveness`, `shipId`, `engineId`

26. **Simplify `ConfigPanel`**
    - File: `gui/frontend/src/components/ConfigPanel/ConfigPanel.tsx`
    - **Remove**: `TierEditor` for drag tiers
    - **Remove**: `TierEditor` for jerk tiers
    - **Remove**: `SliderInput` for `inertiaImpactFactor`
    - **Remove**: `SliderInput` for `dragReductionFactor` (if present)
    - **Remove**: `ToggleInput` for `useEffectiveRatioCap`
    - **Keep**: `CargoMultiplierSelector`
    - **Keep**: `SliderInput` for `accelerationResponsiveness` (the single tuning knob)
    - **Keep**: `ActionButtons` (save/reset)

27. **Delete `TierEditor` component** (if only used for drag/jerk tiers)
    - **Delete**: `gui/frontend/src/components/ConfigPanel/TierEditor.tsx`
    - Verify no other usage first

28. **Simplify `ResultsPanel`**
    - File: `gui/frontend/src/components/ResultsPanel/ResultsPanel.tsx` and children
    - **Remove**: Drag comparison cards/sections
    - **Remove**: Inertia comparison cards/sections
    - **Remove**: Jerk comparison cards/sections
    - **Keep**: Mass ratio display
    - **Keep**: Acceleration scaling display
    - **Keep**: Engine performance display (top speed, TWR, acceleration)
    - **Update**: `DiagnosticsPanel.tsx` — remove tier-specific diagnostics

29. **Simplify `ClassRangePanel`**
    - File: `gui/frontend/src/components/ResultsPanel/ClassRangePanel.tsx`
    - **Remove**: Drag/inertia/jerk range displays
    - **Keep**: Acceleration range, mass ratio range, engine performance ranges

### Phase 9: Documentation

30. **Update CLI manifest documents**
    - `docs/agents/project-manifest/tech-stack.md`:
      - Remove Tier-Based Adjustment System (Section 11)
      - Replace with Acceleration-Only Override description
      - Update Physics Calculations section (remove drag/inertia/jerk formulas)
      - Update Configuration Parameters section
      - Remove all tier references
    - `docs/agents/project-manifest/public-api.md`:
      - Remove: `ReductionTier`, `AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk*`, `PhysicsOverrideDef`, `JerkOverrideDef` entries
      - Add: `AccelerationOverrideDef` entry
      - Update: `BuildConfig` (remove tier methods/constants), `FlightMechanicsOverrideFile` (simplified methods), `PhysicsCalculator` (remove effective ratio)
    - `docs/agents/project-manifest/data-flows.md`:
      - Simplify Physics Calculation Flow — remove tier lookup, drag/inertia/jerk adjustment steps
      - Simplify Override File Generation flow — only acceleration override
      - Remove tier-system rationale section
      - Update physics formulas to acceleration-only
    - `docs/agents/project-manifest/constraints.md`:
      - Remove `AdjustedValuesInterface` examples for drag/inertia (keep for acceleration)
      - Update build configuration constraints
    - `docs/agents/project-manifest/file-tree.md`:
      - Remove deleted files from tree
      - Add `AccelerationOverrideDef.php`

31. **Update GUI manifest documents**
    - `gui/docs/project-manifest/public-api.md` — Update all service/DTO signatures
    - `gui/docs/project-manifest/tech-stack.md` — Remove tier references
    - `gui/docs/project-manifest/data-flows.md` — Simplify physics calculation flows
    - `gui/docs/project-manifest/constraints.md` — Update config validation rules
    - `gui/docs/API.md` — Update REST API request/response schemas
    - `gui/docs/ARCHITECTURE.md` — Update type definitions

32. **Update project-level docs**
    - `docs/physics-tuning-guide.md` — Rewrite for acceleration-only approach (single `responsiveness` knob)
    - `README.md` — Update flight mechanics section, remove tier config examples

## Dependencies

```
Phase 1 (AccelerationOverrideDef) ← no dependencies
Phase 2 (FlightMechanicsOverrideFile) ← depends on Phase 1
Phase 3 (BuildConfig + config JSON) ← no dependencies, but informally linked to Phase 2
Phase 4 (Delete obsolete classes) ← depends on Phases 2+3 (consumers removed first)
Phase 5 (CLI tests) ← depends on Phases 1-4
Phase 6 (GUI backend) ← depends on Phases 3+4 (shared classes)
Phase 7 (GUI backend tests) ← depends on Phase 6
Phase 8 (GUI frontend) ← depends on Phase 6 (API contract change)
Phase 9 (Documentation) ← depends on all code phases
```

**Recommended execution order**: Phases 1→2→3→4→5 (CLI complete), then 6→7→8 (GUI complete), then 9 (docs).

## Required Components

### New Files (2)
| File | Purpose |
|------|---------|
| `src/Mods/CargoSizesMod/Output/Physics/AccelerationOverrideDef.php` | Acceleration-only XML override definition |
| `tests/CargoSizesModTests/AccelerationOverrideTest.php` | Acceleration override test suite |

### Files to Delete (18)
| File | Reason |
|------|--------|
| `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php` | Drag not modified |
| `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php` | Inertia not modified |
| `src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php` | Replaced by AccelerationOverrideDef |
| `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php` | Jerk not modified |
| `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php` | Jerk not modified |
| `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php` | Jerk not modified |
| `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php` | Jerk not modified |
| `src/Mods/CargoSizesMod/Output/Jerk/JerkOverrideDef.php` | Jerk not overridden |
| `src/Mods/CargoSizesMod/Build/ReductionTier.php` | Tier system removed |
| `tests/CargoSizesModTests/DragAdjustmentTest.php` | Tests deleted class |
| `tests/CargoSizesModTests/InertiaAdjustmentTest.php` | Tests deleted class |
| `tests/CargoSizesModTests/JerkAdjustmentTest.php` | Tests deleted class |
| `tests/CargoSizesModTests/TierSystemTest.php` | Tests deleted system |
| `gui/backend/src/DTOs/PhysicsData.php` | Wrapped deleted adjustment classes |
| `gui/backend/src/DTOs/ReductionTiers.php` | Wrapped deleted tier class |
| `gui/backend/src/Utils/PhysicsCalculationHelper.php` | Tier/drag/inertia helper methods |
| `gui/backend/tests/Unit/Utils/PhysicsCalculationHelperTest.php` | Tests deleted trait |
| `gui/frontend/src/components/ConfigPanel/TierEditor.tsx` | Tier editor UI (verify no other usage) |

### Files to Modify — CLI (8)
| File | Change |
|------|--------|
| `config/build-config.json` | Remove 8 keys, keep 2 |
| `config/custom-builds/irukandji.json` | Remove tier-based keys |
| `src/Mods/CargoSizesMod/Build/BuildConfig.php` | Remove tier methods/constants/properties |
| `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php` | Rewrite to acceleration-only |
| `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php` | Remove effective ratio cap |
| `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php` | Remove tier logging |
| `tests/CargoSizesModTests/AccelerationAdjustmentTest.php` | Remove cap-related tests |
| `tests/CargoSizesModTests/PhysicsCalculatorTest.php` | Remove effective ratio tests |

### Files to Modify — GUI Backend (7)
| File | Change |
|------|--------|
| `gui/backend/src/DTOs/PhysicsRequest.php` | Remove 5 tier properties |
| `gui/backend/src/DTOs/ClassRangeRequest.php` | Remove tier properties |
| `gui/backend/src/DTOs/PhysicsResponse.php` | Remove drag/inertia/jerk/tier fields |
| `gui/backend/src/DTOs/PhysicsResponseData.php` | Remove PhysicsData/ReductionTiers params |
| `gui/backend/src/Services/PhysicsService.php` | Remove tier/drag/inertia/jerk logic |
| `gui/backend/src/Services/ClassRangeService.php` | Remove tier/drag/inertia/jerk logic |
| `gui/backend/src/Services/ConfigService.php` | Simplify validation |

### Files to Modify — GUI Backend Tests (4)
| File | Change |
|------|--------|
| `gui/backend/tests/Unit/Services/PhysicsServiceTest.php` | Rewrite for acceleration-only |
| `gui/backend/tests/Unit/Services/ConfigServiceTest.php` | Simplify for new schema |
| `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php` | Rewrite for acceleration-only |
| `gui/backend/tests/Unit/DTOs/PhysicsResponseDataTest.php` | Remove PhysicsData/tier setup |

### Files to Modify — GUI Frontend (7+)
| File | Change |
|------|--------|
| `gui/frontend/src/types/config.d.ts` | Remove tier type definitions |
| `gui/frontend/src/types/physics.d.ts` | Remove drag/inertia/jerk types |
| `gui/frontend/src/hooks/useConfig.ts` | Remove tier defaults |
| `gui/frontend/src/App.tsx` | Remove tier state/props |
| `gui/frontend/src/components/ConfigPanel/ConfigPanel.tsx` | Remove tier/drag/inertia controls |
| `gui/frontend/src/components/ResultsPanel/ResultsPanel.tsx` | Remove drag/inertia/jerk sections |
| `gui/frontend/src/components/ResultsPanel/DiagnosticsPanel.tsx` | Remove tier diagnostics |

### Files to Modify — Documentation (10+)
| File | Change |
|------|--------|
| `docs/agents/project-manifest/tech-stack.md` | Replace tier system with acceleration-only |
| `docs/agents/project-manifest/public-api.md` | Remove deleted classes, add new class |
| `docs/agents/project-manifest/data-flows.md` | Simplify physics flow |
| `docs/agents/project-manifest/constraints.md` | Update config constraints |
| `docs/agents/project-manifest/file-tree.md` | Remove deleted files, add new file |
| `gui/docs/project-manifest/public-api.md` | Update service/DTO signatures |
| `gui/docs/project-manifest/tech-stack.md` | Remove tier references |
| `gui/docs/project-manifest/data-flows.md` | Simplify physics flows |
| `gui/docs/API.md` | Update REST API schemas |
| `docs/physics-tuning-guide.md` | Rewrite for single-knob approach |
| `README.md` | Update flight mechanics section |

## Assumptions

- The X4 game engine does **not** dynamically scale inertia/jerk based on current cargo mass (they are fixed XML values). The research paper's code analysis strongly supports this.
- `AccelerationFactors` are located inside `<thruster>` → `<acceleration>`, NOT inside `<physics>`. The acceleration-only override targets `properties/thruster/acceleration`.
- The `responsiveness` parameter (range 0.1–5.0, default 1.0) remains the single tuning parameter. At `1.0`, flight characteristics are mathematically identical to vanilla at full cargo.
- The `Jerk/` subdirectory under `Output/` can be deleted entirely once all 4 files in it are removed. (Verify no other files exist in that directory.)
- GUI test infrastructure (PHPUnit 12.5+) is functional and tests can be rewritten.

## Constraints

- All code must follow `declare(strict_types=1)`, full type hints, `camelCase` methods, `PascalCase` classes
- All exceptions must extend `CargoSizeException`
- No async I/O, no databases, no `eval()`
- Namespace: `Mistralys\X4\Mods\CargoSizesMod\*`
- New `AccelerationOverrideDef` must follow the Override Definition Pattern (extend `TagOverrideDef`, self-rendering with custom template)
- GUI backend follows Slim Framework 4 patterns; frontend follows React + TypeScript + TailwindCSS v4 patterns
- All manifest documents must be updated after implementation
- Run `composer dump-autoload` after adding/removing classes

## Out of Scope

- Engine-specific calibration — the formula `accel = thrust × accelFactor / mass` holds regardless of engine choice
- Dynamic XML (runtime cargo-aware values) — X4 only supports static XML overrides
- GUI layout redesign beyond control removal — simplify existing structure, don't redesign
- In-game testing validation — plan covers build and unit testing; in-game manual testing is separate
- Changes to `StorageOverrideFile` — cargo capacity overrides are unaffected
- Changes to FOMOD generation — file structure shrinks (fewer override types per ship) but FOMOD logic is unchanged
- Changes to reference documentation generators — they show cargo values, not physics adjustments
- XML value objects (`Drag`, `Inertia`, `Jerk`, `SteeringCurve`, etc. in `XML/ShipXML/`) — these READ ship data from game files and remain useful for ship data browsing in the GUI

## Acceptance Criteria

- [ ] Running `composer build` generates mod files that override **only** `properties/thruster/acceleration` per ship — no `<physics>`, `<jerk>`, or steering curve overrides
- [ ] Generated acceleration values equal `originalAccel × massRatio × responsiveness`
- [ ] `build-config.json` contains only `cargo-multipliers` and `flight-mechanics.accelerationResponsiveness`
- [ ] `AccelerationOverrideDef` renders valid XML with correct XPath and all 4 acceleration axes
- [ ] All deleted classes are removed from disk and no import references remain in living code
- [ ] `BuildConfig` has only `getMultipliers()` and `getAccelerationResponsiveness()` methods (plus constructor)
- [ ] `PhysicsCalculator` no longer has effective ratio cap logic
- [ ] All CLI tests pass: `composer test`
- [ ] All GUI backend tests pass
- [ ] GUI shows only `accelerationResponsiveness` slider and cargo multiplier selector (no drag/inertia/jerk/steering/tier controls)
- [ ] GUI results panel shows acceleration scaling and engine performance (no drag/inertia/jerk comparisons)
- [ ] All manifest documents are updated
- [ ] `composer dump-autoload` completes without errors
- [ ] Static analysis passes: `composer analyze` (PHPStan)

## Testing Strategy

### CLI Tests (PHPUnit 9.5+)

1. **AccelerationOverrideDef Tests** (new file):
   - Verify XML renders with correct XPath `properties/thruster/acceleration`
   - Verify all 4 axes present (`forward`, `reverse`, `horizontal`, `vertical`)
   - Verify values match `original × scalingFactor`
   - Verify XML comments include original values

2. **AccelerationAdjustmentTest** (updated):
   - Verify pure mass-ratio scaling
   - Verify responsiveness attenuation
   - Remove any effective-ratio-cap tests

3. **PhysicsCalculatorTest** (updated):
   - Verify mass ratio calculation
   - Verify mass increase percentage
   - Verify inverse mass ratio and squared
   - Remove effective ratio tests
   - Remove `useEffectiveRatioCap` parameter tests

### GUI Backend Tests (PHPUnit 12.5+)

4. **PhysicsServiceTest** (rewritten):
   - Verify response contains only mass ratio + acceleration scaling
   - Verify engine performance uses original drag for top speed
   - Verify no drag/inertia/jerk fields in response

5. **ConfigServiceTest** (simplified):
   - Verify simplified config schema validation
   - Verify `accelerationResponsiveness` range validation
   - Verify rejection of unknown keys

6. **ClassRangeServiceTest** (rewritten):
   - Verify acceleration range calculation across ship class
   - Verify no drag/inertia/jerk ranges in response

### Manual Testing

7. Run `composer build` → inspect generated XML files → confirm acceleration-only overrides
8. Start GUI → verify simplified config panel → verify results display
9. Load mod in X4 game → verify ships fly normally with modified cargo

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **X4 engine may internally scale inertia/jerk with mass** | If true, the approach is still correct: we preserve XML values and let the engine apply its own scaling. No code change needed. |
| **`<acceleration>` XPath may vary across DLCs** | During implementation, verify XPath against ship XMLs from multiple DLCs (vanilla, Terran, Split, Boron, etc.). The research paper found it consistent. |
| **Players miss the "heavier feel"** | `responsiveness < 1.0` simulates reduced acceleration at full cargo. Document this clearly in the tuning guide and GUI tooltip. |
| **Removing the `Jerk/` directory breaks autoloading** | Run `composer dump-autoload` after deletion. Verify no `use` statements reference deleted classes. |
| **GUI frontend build breaks after type changes** | TypeScript compiler will catch all type mismatches. Run `npm run build` to verify. |
| **`ClassRangeService` loses too much functionality** | The service remains useful: it still computes mass-ratio ranges, acceleration scaling ranges, and engine performance ranges across all ships of a class. Only drag/inertia/jerk ranges are removed. |
| **Other mods that depend on `<thruster>` section** | Acceleration-only override touches a smaller subset of XML, reducing conflict potential. This is a benefit. |
| **Floating-point precision** | Continue using `dec2()` (2 decimal places) for acceleration values, matching existing `AdjustedAccelerationFactors`. |

## Impact Summary

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Config parameters | 9 | 1 | -89% |
| Adjusted value classes | 8 | 1 | -88% |
| Override def classes | 2 | 1 | -50% |
| Build support classes | 1 (ReductionTier) | 0 | -100% |
| XML output per ship | ~60 lines | ~10 lines | -83% |
| XML sections touched | 3 (physics, jerk, steeringcurve) | 1 (thruster/acceleration) | -67% |
| GUI config controls | ~8 | 2 | -75% |
| CLI test files | 6 | 3 | -50% |
| Total files deleted | — | 18 | — |
| Total files modified | — | ~36 | — |
| Total files created | — | 2 | — |
