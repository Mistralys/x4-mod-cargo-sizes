# Research Report

## Problem Statement

The X4 Cargo Sizes Mod increases ship cargo capacity (2x–10x), which increases the ship's loaded mass. The mod currently applies **tier-based percentage adjustments** to drag, inertia, jerk, steering, and acceleration factors to "compensate" for the mass change. However, testing with the Physics Tuning GUI revealed that **most ships can be adjusted to perfectly match their original acceleration, top speed, and handling without modifying engines**. The question is: can we restructure the implementation to **guarantee** that modified ships retain their original flight characteristics?

## Problem Decomposition

1. **What determines each flight metric in X4, and which are mass-dependent?**
2. **Which current adjustments help preserve originals, and which actively deviate from them?**
3. **What is the minimal set of overrides needed to guarantee preservation?**
4. **How should the architecture change to support a "preserve original" mode?**

## Context & Constraints

- **X4 Physics Model** (from code analysis):
  - Top Speed: `v_max = thrust_kN × engineCount / dragCoefficient` — **mass is NOT a factor**
  - Acceleration: `a = (thrust_kN × 1000 × engineCount) / totalMass` — mass IS a factor
  - Handling (turning): determined by inertia, jerk, steering curve values — **fixed XML values, not derived from mass**
  - Boost/Travel speed: **speed multipliers** on base top speed (not thrust values)
- **Static Modification Limitation**: The mod can only set static XML values; it cannot make values dynamic based on current cargo load
- **Engine Independence**: The mod must NOT modify engine definitions (players choose engines as equipment)
- **Architectural Constraint**: The build system generates per-ship XML override files

## Prior Art & Known Patterns

### Pattern 1: Tier-Based Flat Percentage (Current System)

- **Description:** All ships at a given cargo multiplier receive the same percentage adjustment to drag, inertia, jerk, and steering, regardless of their individual mass-to-cargo ratio. Acceleration factors are scaled by per-ship mass ratio.
- **Where used:** Current implementation in `FlightMechanicsOverrideFile`, configured via `build-config.json` tiers
- **Strengths:** Simple, predictable, uniform behavior across ship types, safe (prevents extreme outliers)
- **Weaknesses:** 
  - **Drag reduction increases top speed beyond vanilla** (drag determines top speed, and reducing it is a pure speed buff, not compensation)
  - **Inertia/jerk changes worsen handling** beyond what mass alone would cause (since these are fixed XML values, not derived from mass)
  - **Steering increases attempt to compensate** for inertia changes that shouldn't exist
  - Net effect: ships are actively pushed AWAY from their original characteristics
- **Fit:** Poor for the "preserve original" goal; designed for a different philosophy ("compensate for heavier feel")

### Pattern 2: Acceleration-Only Override (Proposed)

- **Description:** Only modify `AccelerationFactors` (scaled by per-ship mass ratio). Leave drag, inertia, jerk, and steering at their original values.
- **Where used:** This is the mathematical minimum intervention required
- **Strengths:**
  - **Top speed: identical to vanilla** (drag unchanged → `thrust/drag` unchanged)
  - **Acceleration at full modded cargo: identical to vanilla at full original cargo** (AccelFactor/Mass ratio preserved exactly)
  - **Handling: identical to vanilla** (inertia/jerk/steering unchanged)
  - Smallest possible mod files (only acceleration override per ship)
  - Least likely to conflict with other mods
  - Simplest to reason about and verify
- **Weaknesses:**
  - When ship is **empty or partially loaded**, acceleration is higher than vanilla empty ship (because AccelFactor is scaled up for worst-case, but current mass is lower). This is an inherent limitation of static XML modification.
  - No "heavier feel" — ships with 10x cargo fly identically to ships with 1x cargo (at matching loads). Some players might want the feel of heaviness.
- **Fit:** Excellent for "preserve original" goal. The empty-cargo acceleration bonus is a reasonable trade-off and arguably a player benefit.

### Pattern 3: Per-Ship Exact Calibration

- **Description:** For each ship and cargo multiplier, compute the exact adjustment needed for EVERY physics parameter to hit specific target metrics (e.g., "95% of original top speed, 100% of original acceleration").
- **Where used:** Advanced physics simulation mods in other games
- **Strengths:** Fine-grained control, can achieve any target profile
- **Weaknesses:** 
  - Overkill: since top speed/handling are not mass-dependent in X4, there's nothing to "calibrate" — just don't change them
  - Complex implementation for no measurable benefit over Pattern 2
  - Requires per-ship engine assumptions (X4 lets players choose engines)
- **Fit:** Over-engineered for this problem. The X4 physics model makes this unnecessary.

## Alternative & Creative Approaches

### Hybrid Approach: Preserve + Optional Flavor

- **Approach:** Default to Pattern 2 (acceleration-only), but allow optional "flavor" adjustments via config for players who want heavier-feeling ships. These would be explicitly labeled as "deviations from original" rather than "compensations."
- **Rationale:** Some players may prefer a slight handling penalty to sell the fantasy of hauling more cargo. Having this as an opt-in preserves the guarantee for the default while allowing customization.
- **Risk:** Adds complexity to messaging. Players might enable flavor adjustments and then complain ships don't match original.

### No-Override Mode (Config-Only Fix)

- **Approach:** Simply set all current config values to "zero effect": drag tiers to 0%, jerk tiers to 0%, inertia factor to 0, steering factor to 0, responsiveness to 1.0. No code changes needed.
- **Rationale:** The current architecture already supports this configuration. Setting reduction percentages to 0 means "no change from original."
- **Risk:** Still generates XML overrides that write original values back (wasteful, larger mod files, potential for floating-point drift). The `PhysicsOverrideDef` replaces the entire `<physics>` section even when values are unchanged.

## Comparative Evaluation

| Criterion | Tier-Based (Current) | Acceleration-Only | Per-Ship Exact | Config-Only Fix |
|---|---|---|---|---|
| **Top Speed Fidelity** | ❌ Increased (drag reduced) | ✅ Identical | ✅ Identical | ⚠️ Near-identical (FP drift) |
| **Acceleration Fidelity** | ⚠️ Depends on responsiveness | ✅ Exact at full cargo | ✅ Exact | ✅ Exact at full cargo |
| **Handling Fidelity** | ❌ Worsened (inertia up, jerk down) | ✅ Identical | ✅ Identical | ⚠️ Near-identical (FP drift) |
| **Mod File Size** | Large (all params) | Small (accel only) | Large (all params) | Large (all params) |
| **Other-Mod Conflicts** | High (replaces physics/jerk) | Low (only accel section) | High | High |
| **Implementation Effort** | 0 (existing) | Medium (refactor override) | High | None |
| **Correctness Guarantee** | No | **Yes** (mathematically proven) | Yes | Weak (relies on config) |
| **Player Customization** | High (many knobs) | Low (just responsiveness) | High | High |
| **Backward Compatibility** | N/A (is current) | Via config flag | Via config flag | ✅ Full |

## Recommendation

**Implement Pattern 2 (Acceleration-Only Override) as a new build mode, defaulting to ON.**

### Rationale

The key insight from the X4 physics model is that **top speed and handling are not affected by mass** — they are determined solely by drag coefficients, inertia values, jerk values, and steering curves, all of which are fixed XML properties. The only flight metric affected by mass is **acceleration** (F=ma), which is exactly what `AccelerationFactors` compensate for.

The current tier-based system actually **hurts** flight fidelity by modifying parameters that don't need modification:
- Reducing drag **increases** top speed beyond vanilla (a buff, not compensation)
- Increasing inertia **worsens** turning beyond what mass change causes (in X4, inertia is not derived from mass)
- Reducing jerk **worsens** responsiveness unnecessarily
- Increasing steering tries to undo the inertia damage — a fix for a self-created problem

By only modifying `AccelerationFactors` (scaled by the per-ship mass ratio), we get a **mathematical guarantee**:
- At full modded cargo: `acceleration = thrust × (accelFactor × massRatio) / (baseMass + adjustedCargo) = thrust × accelFactor / (baseMass + originalCargo) = vanilla_full_cargo_acceleration` ✓
- Top speed: `thrust / drag` = unchanged ✓
- Handling: inertia/jerk/steering = unchanged ✓

The only measurable difference: when a ship is empty or partially loaded, its acceleration is **better** than vanilla (`massRatio × vanilla_empty_accel`). This is an inherent limitation of static XML modification and is actually player-friendly (a subtle bonus when not fully loaded).

### Proof-of-Concept Outline

1. **Add `preserveOriginalFlight` boolean to `build-config.json`** (default: `true`)
2. **Add `BuildConfig::getPreserveOriginalFlight(): bool` accessor**
3. **In `FlightMechanicsOverrideFile::preRender()`**: when preserve mode is ON:
   - Calculate mass adjustment (existing)
   - Calculate AccelerationFactor scaling: `massRatio × responsiveness` (existing calculation)
   - **Skip**: drag override, inertia override, jerk override, steering curve override
   - **Only emit**: An acceleration-only override (new `AccelerationOverrideDef` class) that targets only `properties/thruster/acceleration`
4. **Create `AccelerationOverrideDef`**: a lightweight `TagOverrideDef` that replaces only the `<acceleration>` tag within `<thruster>`, without touching `<physics>` or `<jerk>`
5. **When preserve mode is OFF**: fall through to existing tier-based logic (backward compatibility)
6. **Update GUI**: Add a toggle for "Preserve Original Flight" mode that disables drag/inertia/jerk/steering controls and shows only acceleration scaling
7. **Verify**: Run existing PhysicsCalculator tests; add new tests asserting that in preserve mode, drag/inertia/jerk output equals original values

### XML Output Comparison

**Current (Tier-Based) — per ship per multiplier, ~60 lines:**
```xml
<diff>
  <replace sel="...properties/physics">
    <physics mass="650">
      <drag forward="119" ... />           <!-- CHANGED: 30% reduction -->
      <inertia pitch="15.5" ... />          <!-- CHANGED: 55% increase -->
      <accfactors forward="7.88" ... />     <!-- CHANGED: scaled by 3.94x -->
    </physics>
  </replace>
  <replace sel="...properties/jerk">
    <jerk>
      <forward accel="42.5" ... />          <!-- CHANGED: 15% reduction -->
      ...
    </jerk>
  </replace>
  <replace sel="...steeringcurve/point[@position='0.5']/@value">
    ...                                     <!-- CHANGED: steering compensation -->
  </replace>
</diff>
```

**Proposed (Acceleration-Only) — per ship per multiplier, ~10 lines:**
```xml
<diff>
  <replace sel="...properties/thruster/acceleration">
    <acceleration forward="7.88" reverse="1.38" horizontal="2.17" vertical="2.76" />
    <!-- AccelerationFactor scaled by mass ratio 3.94x to preserve responsiveness -->
    <!-- Original: forward=2.0, reverse=0.35, horizontal=0.55, vertical=0.70 -->
  </replace>
</diff>
```

This is ~85% smaller, touches only what's needed, and has minimal conflict potential with other mods.

### Implementation Considerations

**Where `<acceleration>` lives in the XML:**
The `<acceleration>` tag is inside `<thruster>`, NOT inside `<physics>`. This means:
- A new `AccelerationOverrideDef` targets `properties/thruster/acceleration` (separate from `properties/physics`)
- No need to touch the `<physics>` section at all (mass, drag, inertia stay at game defaults)
- No need to override `<jerk>` or `<steeringcurve>`
- The `StorageOverrideFile` still overrides cargo capacity (unchanged)

**`responsiveness` parameter:**
Keep this as the single tunable parameter in preserve mode. At `1.0`: perfect preservation. Below `1.0`: ships feel heavier at full cargo (intentional degradation). Above `1.0`: ships feel lighter (unrealistic buff). This gives users one clear knob instead of five confusing ones.

## Open Questions

- **Does X4 dynamically adjust inertia/jerk based on current cargo mass?** The code assumes not (inertia/jerk are fixed XML values). If the game engine does scale them internally with mass, then leaving them at original values is correct. If not, there may be a subtle difference in rotational behavior at extreme cargo loads. In-game testing should confirm.
- **How do AccelerationFactors interact with different engine types?** The formula `accel = thrust × accelFactor / mass` should hold regardless of engine choice, but testing with various engines (combat vs. travel-optimized) would increase confidence.
- **Should the tier-based system be removed entirely or kept as a "legacy" mode?** Keeping it allows backward compatibility and player choice, but adds maintenance burden. Recommend keeping it behind the config flag initially, then deprecating if preserve mode proves superior.
- **How should the GUI reflect this change?** The GUI currently has extensive controls for drag/inertia/jerk tiers. In preserve mode, most of these become irrelevant. The GUI could show a simplified view with just the `responsiveness` slider and the computed absolute metrics.

## References

- [PhysicsCalculator.php](../../src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php) — Mass ratio calculations
- [FlightMechanicsOverrideFile.php](../../src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php) — Current override orchestration
- [PhysicsOverrideDef.php](../../src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php) — Current XML template (replaces entire `<physics>` section)
- [AdjustedAccelerationFactors.php](../../src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php) — AccelerationFactor scaling (already per-ship mass ratio based)
- [PhysicsService.php](../../gui/backend/src/Services/PhysicsService.php) — GUI top speed/acceleration formula: `v_max = thrust / drag`
- [physics-tuning-guide.md](../physics-tuning-guide.md) — FAQ: "acceleration factors control responsiveness, not top speed"
- [build-config.json](../../config/build-config.json) — Current tier-based configuration
