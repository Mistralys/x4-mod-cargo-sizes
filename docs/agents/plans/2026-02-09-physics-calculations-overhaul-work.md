# Physics Calculations Overhaul - Work Packages

> **Source Plan:** [2026-02-09-physics-calculations-overhaul.md](2026-02-09-physics-calculations-overhaul.md)  
> **Created:** February 9, 2026  
> **Status:** Ready for Implementation  
> **Total Work Packages:** 9

---

## 🎯 Executive Summary

This document breaks down the physics calculations overhaul into 9 implementable work packages. The overhaul fixes critical issues preventing player-controlled cargo ships from using travel mode by correcting backwards physics formulas and implementing a tier-based adjustment system with safety caps.

**Critical Problem Being Solved:**  
Player-controlled large cargo ships (e.g., Shuyaku Vanguard) cannot enter travel mode effectively. The ship enters travel mode but gains no speed.

**Root Causes:**
1. Backwards jerk calculations (INCREASE jerk when mass increases)
2. Insufficient drag reduction for fixed engine thrust
3. Formula-based approach causes extreme variations by ship type
4. Arbitrary 2x travel jerk penalty
5. Acceleration factors not compensating for mass

**Solution Approach:**
- **Tier-based system**: All ships with same cargo multiplier get same adjustments (predictable, safe, tunable)
- **Safety caps**: Maximum 70% drag reduction to prevent undriveable ships
- **Physics-correct formulas**: Jerk decreases, inertia increases, acceleration factors scale with mass
- **Full configurability**: Users can tune each tier independently

---

## 📋 Work Package Overview

| WP | Title | Effort | Dependencies | Risk |
|----|-------|--------|--------------|------|
| **WP-1** | Physics Calculator Foundation | 1.5h | None | Low |
| **WP-2** | Configuration System Upgrade | 2h | None | Low |
| **WP-3** | Drag Calculations Overhaul | 1.5h | WP-1, WP-2 | Low |
| **WP-4** | Jerk Calculations Fix | 1.5h | WP-1, WP-2 | Low |
| **WP-5** | Inertia and Acceleration | 1.5h | WP-1, WP-2 | Medium |
| **WP-6** | XML Comments and Diagnostics | 2h | WP-1, WP-2 | Low |
| **WP-7** | Testing Infrastructure | 2.5h | WP-1 to WP-5 | Low |
| **WP-8** | Documentation Updates | 3h | WP-1 to WP-6 | Low |
| **WP-9** | Verification and Integration | 2.5h | WP-1 to WP-8 | Medium |

**Total Estimated Effort:** 18 hours  
**Critical Path:** WP-1 → WP-2 → WP-3/4/5 (parallel) → WP-6 → WP-7 → WP-8 → WP-9

---

## 🔧 Work Package Definitions

---

### WP-1: Physics Calculator Foundation

**Priority:** HIGH - Foundation for all other work  
**Effort:** 1.5 hours  
**Dependencies:** None  
**Risk:** Low

#### Objective
Create clear physics calculation infrastructure to replace confusing "multiplier" terminology. Provide accurate mass ratio calculations that all other components will use.

#### Context
Current code uses backwards multipliers (values < 1.0) that lead to confusion and errors. Need clear, physics-based calculations:
- **massRatio** = adjustedMass / originalMass (>1.0 when cargo increases)
- **cargoMultiplier** = user's chosen multiplier (2x, 4x, 10x)
- **effectiveRatio** = capped ratio to prevent extremes

#### Deliverables

1. **Create:** `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php`

```php
<?php
declare(strict_types=1);

namespace Mods\CargoSizesMod\Output\Physics;

class PhysicsCalculator
{
    public function __construct(
        private float $baseMass,
        private float $originalCargo,
        private float $adjustedCargo,
        private float $cargoMultiplier,
        private bool $useEffectiveRatioCap
    ) {}
    
    // Core calculations
    public function getMassRatio(): float;           // adjustedFullMass / originalFullMass
    public function getCargoMultiplier(): float;     // User's chosen multiplier
    public function getEffectiveRatio(): float;      // min(massRatio, cargoMultiplier) if capped
    public function getBaseMass(): float;            // Ship mass without cargo
    public function getOriginalFullMass(): float;    // baseMass + originalCargo
    public function getAdjustedFullMass(): float;    // baseMass + adjustedCargo
    public function getMassIncrease(): float;        // adjustedFullMass - originalFullMass
    public function getMassIncreasePercent(): float; // (massRatio - 1.0) * 100
    
    // Derived calculations
    public function getInverseMassRatio(): float;    // 1.0 / massRatio (for jerk)
    public function getMassRatioSquared(): float;    // massRatio² (for squared drag mode)
    
    // Validation
    public function validate(): array;               // Returns warnings if any
}
```

**Key Methods:**
- All ratios must be > 0
- massRatio typically 1.0-10.0 (flag if > 10.0 as extreme)
- effectiveRatio respects useEffectiveRatioCap flag

2. **Refactor:** `src/Mods/CargoSizesMod/Output/MassAdjustment.php`

**Remove confusing method:**
```php
public function getMultiplier(): float  // Returns < 1.0, causes confusion
```

**Add clear methods:**
```php
public function getMassRatio(): float
public function getInverseMassRatio(): float
public function getMassRatioSquared(): float
public function getMassIncrease(): float
public function getMassIncreasePercent(): float
```

**Update all calling code** to use new terminology.

#### Verification
- [ ] PhysicsCalculator class compiles without errors
- [ ] All new methods return expected values for test cases
- [ ] MassAdjustment refactored successfully
- [ ] No references to old `getMultiplier()` method remain
- [ ] Mass ratio > 1.0 for all test cases
- [ ] Effective ratio cap works correctly

#### Test Cases
```php
// Test 1: Combat ship (low cargo)
baseMass: 106, originalCargo: 240, adjustedCargo: 2400 (10x)
Expected massRatio: ~9.78x
Expected effectiveRatio (capped): 10.0x

// Test 2: Cargo ship (high cargo)
baseMass: 205, originalCargo: 42000, adjustedCargo: 420000 (10x)
Expected massRatio: ~204.6x
Expected effectiveRatio (capped): 10.0x (prevents extreme calculations)

// Test 3: Moderate case
baseMass: 650, originalCargo: 1000, adjustedCargo: 4000 (4x)
Expected massRatio: 1.22x
Expected effectiveRatio (no cap applied): 1.22x
```

#### Files Modified
- `src/Mods/CargoSizesMod/Output/MassAdjustment.php` (refactor)
- `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php` (create)

#### Notes for Implementation
- Follow strict_types declaration (PHP 8.2+)
- Use readonly properties where appropriate
- Add comprehensive PHPDoc comments
- Consider extracting value objects if complexity grows

---

### WP-2: Configuration System Upgrade

**Priority:** HIGH - Required for tier-based system  
**Effort:** 2 hours  
**Dependencies:** None  
**Risk:** Low

#### Objective
Replace simple factor-based configuration with tier-based system that allows independent tuning per cargo multiplier. Add new parameters for all physics adjustments.

#### Context
Current configuration only has simple factors (dragReductionFactor, etc.). New tier-based system allows:
- **Different adjustments per cargo multiplier tier** (2x, 4x, 8x, 10x)
- **Safety caps** (max 70% drag reduction)
- **Independent tuning** of drag vs jerk vs inertia
- **User-friendly configuration** without formula understanding

#### Deliverables

1. **Update:** `config/build-config.json`

**Remove:**
```json
"flight-mechanics": {
  "dragReductionFactor": 1,
  "steeringIncreaseFactor": 1,
  "inertiaIncreaseFactor": 1
}
```

**Add:**
```json
"flight-mechanics": {
  "dragReductionTiers": [
    { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
    { "maxMultiplier": 4.0, "reductionPercent": 0.30 },
    { "maxMultiplier": 8.0, "reductionPercent": 0.50 },
    { "maxMultiplier": 999, "reductionPercent": 0.70 }
  ],
  "jerkReductionTiers": [
    { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
    { "maxMultiplier": 4.0, "reductionPercent": 0.15 },
    { "maxMultiplier": 8.0, "reductionPercent": 0.25 },
    { "maxMultiplier": 999, "reductionPercent": 0.35 }
  ],
  "inertiaImpactFactor": 0.5,
  "steeringIncreaseFactor": 1.0,
  "useEffectiveRatioCap": true,
  "accelerationResponsiveness": 1.0
}
```

**Tier System Explanation:**
- Ship with 4x cargo finds tier where `maxMultiplier >= 4.0`
- Applies that tier's `reductionPercent` (30% for drag, 15% for jerk)
- **All ships with 4x cargo get same adjustment** regardless of their base mass/cargo ratio
- Last tier has `maxMultiplier: 999` as catchall (safety cap)

2. **Create:** `src/Mods/CargoSizesMod/Build/ReductionTier.php`

```php
<?php
declare(strict_types=1);

namespace Mods\CargoSizesMod\Build;

class ReductionTier
{
    public function __construct(
        private float $maxMultiplier,
        private float $reductionPercent
    ) {
        $this->validate();
    }
    
    public function getMaxMultiplier(): float;
    public function getReductionPercent(): float;
    public function appliesToMultiplier(float $multiplier): bool;
    
    private function validate(): void {
        // maxMultiplier must be > 0
        // reductionPercent must be 0.0-1.0
    }
}
```

3. **Update:** `src/Mods/CargoSizesMod/Build/BuildConfiguration.php`

**Add methods:**
```php
public function getDragReductionTiers(): array;           // Returns ReductionTier[]
public function getJerkReductionTiers(): array;           // Returns ReductionTier[]
public function findDragTierForMultiplier(float $multiplier): ReductionTier;
public function findJerkTierForMultiplier(float $multiplier): ReductionTier;
public function getInertiaImpactFactor(): float;          // Default 0.5
public function getSteeringIncreaseFactor(): float;       // Default 1.0
public function getUseEffectiveRatioCap(): bool;          // Default true
public function getAccelerationResponsiveness(): float;   // Default 1.0
```

**Add validation:**
- Tier arrays must have at least one entry
- maxMultiplier must be positive and in ascending order
- reductionPercent must be 0.0-1.0
- inertiaImpactFactor must be 0.0-2.0
- accelerationResponsiveness must be 0.1-5.0

4. **Update:** `dev-config.dist.php` (if needed for schema documentation)

#### Verification
- [ ] build-config.json parses successfully
- [ ] BuildConfiguration loads tiers correctly
- [ ] ReductionTier validation works
- [ ] findTierForMultiplier() returns correct tier for each multiplier
- [ ] Tier with maxMultiplier=999 acts as catchall
- [ ] Invalid configurations are rejected with clear error messages

#### Test Cases
```php
// Test 1: Find tier for 4x cargo
Expected: tier with maxMultiplier=4.0, reductionPercent=0.30

// Test 2: Find tier for 10x cargo (extreme)
Expected: tier with maxMultiplier=999, reductionPercent=0.70

// Test 3: Find tier for 3x cargo (between tiers)
Expected: tier with maxMultiplier=4.0 (first tier that covers 3x)

// Test 4: Invalid tier (reduction > 1.0)
Expected: Validation exception thrown

// Test 5: Invalid tier order (descending maxMultiplier)
Expected: Validation exception thrown
```

#### Files Modified
- `config/build-config.json` (update schema)
- `src/Mods/CargoSizesMod/Build/BuildConfiguration.php` (add methods)
- `src/Mods/CargoSizesMod/Build/ReductionTier.php` (create)

#### Notes for Implementation
- Ensure backwards compatibility if possible (graceful fallback)
- Tier lookup should be efficient (array ordered ascending)
- Consider caching tier lookups if performance matters
- Add clear error messages for configuration issues

---

### WP-3: Drag Calculations Overhaul

**Priority:** HIGH - Critical for travel mode fix  
**Effort:** 1.5 hours  
**Dependencies:** WP-1 (PhysicsCalculator), WP-2 (Configuration)  
**Risk:** Low

#### Objective
Replace conservative linear drag reduction with tier-based system that aggressively reduces drag to compensate for fixed engine thrust. Apply to all 7 drag components.

#### Context
**Problem:** Current drag reduction too conservative (10% at 10x cargo). Fixed engine thrust means ships need much more drag reduction to maintain performance.

**Solution:** Tier-based drag reduction with safety caps:
- 2x cargo: 10% reduction (90% drag remains)
- 4x cargo: 30% reduction (70% drag remains)
- 8x cargo: 50% reduction (50% drag remains)
- 10x+: 70% reduction (30% drag remains) **← SAFETY CAP**

**Why This Works:**
```
v_max = Thrust / DragCoefficient
```
Thrust is fixed (engine property), so reducing drag increases top speed to compensate for heavier ship.

#### Deliverables

1. **Update:** `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php`

**Current (Conservative):**
```php
$dragMultiplier = $massMultiplier * $factor;  // < 1.0
$newDrag = $originalDrag * (1 - $dragMultiplier);
```

**New (Tier-Based):**
```php
// Get appropriate tier for this cargo multiplier
$tier = $config->findDragTierForMultiplier($cargoMultiplier);
$reductionPercent = $tier->getReductionPercent();

// Apply tier-based reduction
$newDrag = $originalDrag * (1.0 - $reductionPercent);

// Example: 4x cargo → 30% reduction → drag becomes 70% of original
```

2. **Apply to all 7 drag components:**
- Forward drag
- Reverse drag
- Horizontal drag (strafe)
- Vertical drag
- Pitch drag (rotation)
- Yaw drag (rotation)
- Roll drag (rotation)

3. **Add XML comments explaining calculations** (preparation for WP-6)

#### Verification
- [ ] All drag values decrease (never increase)
- [ ] Tier-based reduction applied correctly
- [ ] 4x cargo → 30% reduction verified
- [ ] 10x cargo → 70% reduction (capped) verified
- [ ] All 7 drag components adjusted
- [ ] Drag never becomes zero or negative
- [ ] XML output shows new drag values

#### Test Cases
```php
// Test 1: Combat ship with 2x cargo
originalDrag: 15.0
cargoMultiplier: 2.0
Expected tier: 10% reduction
Expected newDrag: 13.5 (90% of 15.0)

// Test 2: Cargo ship with 4x cargo
originalDrag: 17.9
cargoMultiplier: 4.0
Expected tier: 30% reduction
Expected newDrag: 12.53 (70% of 17.9)

// Test 3: Extreme cargo (10x)
originalDrag: 20.0
cargoMultiplier: 10.0
Expected tier: 70% reduction (safety cap)
Expected newDrag: 6.0 (30% of 20.0)

// Test 4: All drag components reduced consistently
Original: forward=17.9, vertical=35.1, pitch=10.5
4x cargo (30% reduction):
Expected: forward=12.53, vertical=24.57, pitch=7.35
```

#### Files Modified
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php` (overhaul)

#### Integration Points
- Uses PhysicsCalculator for cargo multiplier (WP-1)
- Uses BuildConfiguration tier lookup (WP-2)
- Called from FlightMechanicsOverrideFile during build

#### Notes for Implementation
- Comment why tier-based approach chosen (prevents extreme adjustments for cargo-heavy ships)
- Consider adding diagnostic logging of drag calculations
- Validate drag components never go below reasonable minimum (e.g., 1% of original)
- All 7 drag components share same tier logic (consistency)

---

### WP-4: Jerk Calculations Fix

**Priority:** CRITICAL - Fixes backwards physics  
**Effort:** 1.5 hours  
**Dependencies:** WP-1 (PhysicsCalculator), WP-2 (Configuration)  
**Risk:** Low

#### Objective
Fix backwards jerk calculations that INCREASE jerk when mass increases (opposite of physics). Remove arbitrary 2x travel mode penalty. Apply tier-based reductions.

#### Context
**Problem:** Current code increases jerk when mass increases:
```php
$value = $original * (1 + $multiplier);  // WRONG: jerk goes UP
```

**Physics:** Heavier objects have LOWER jerk (slower rate of acceleration change).

**Solution:** Tier-based jerk reduction:
```php
$tier = $config->findJerkTierForMultiplier($cargoMultiplier);
$reductionPercent = $tier->getReductionPercent();
$newJerk = $originalJerk * (1.0 - $reductionPercent);
```

**Travel Mode:** Remove arbitrary 2x penalty, use same tier-based reduction as regular jerk.

#### Deliverables

1. **Update:** `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php`

**Current (Wrong):**
```php
$value = $original * (1 + $multiplier);  // Increases jerk
```

**New (Correct):**
```php
$tier = $config->findJerkTierForMultiplier($cargoMultiplier);
$reductionPercent = $tier->getReductionPercent();
$newJerk = $originalJerk * (1.0 - $reductionPercent);
// Example: 4x cargo → 15% reduction → jerk becomes 85% of original
```

**Apply to:**
- Strafe jerk
- Angular jerk

2. **Update:** `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php`

Same tier-based formula:
```php
$tier = $config->findJerkTierForMultiplier($cargoMultiplier);
$reductionPercent = $tier->getReductionPercent();

$newAccel = $originalAccel * (1.0 - $reductionPercent);
$newDecel = $originalDecel * (1.0 - $reductionPercent);
```

3. **Update:** `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php`

Same tier-based formula:
```php
$newBoostAccel = $originalBoostAccel * (1.0 - $reductionPercent);
```

4. **Update:** `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php` **← CRITICAL**

**Current (Wrong + Penalty):**
```php
$value = $original * (1 + $multiplier * 2);  // Increases + 2x penalty
```

**New (Correct, No Penalty):**
```php
$tier = $config->findJerkTierForMultiplier($cargoMultiplier);
$reductionPercent = $tier->getReductionPercent();

$newAccel = $originalAccel * (1.0 - $reductionPercent);
$newDecel = $originalDecel * (1.0 - $reductionPercent);

// No arbitrary 2x penalty - tier-based system handles travel mode correctly
```

**Why This Fixes Travel Mode:**
- Old code made travel jerk WORSE (higher values = more abrupt)
- New code makes travel jerk BETTER (lower values = smoother acceleration ramp)
- Combined with aggressive drag reduction → ship reaches travel speeds

#### Verification
- [ ] All jerk values decrease (not increase)
- [ ] 4x cargo → 15% reduction verified
- [ ] 10x cargo → 35% reduction verified
- [ ] Travel jerk has NO 2x penalty
- [ ] All jerk components (strafe, angular, forward, boost, travel) adjusted
- [ ] Jerk never becomes zero or negative

#### Test Cases
```php
// Test 1: Strafe jerk with 2x cargo
originalJerk: 5.8
cargoMultiplier: 2.0
Expected tier: 5% reduction
Expected newJerk: 5.51 (95% of 5.8)

// Test 2: Forward jerk with 4x cargo
originalAccel: 3.9, originalDecel: 5.8
cargoMultiplier: 4.0
Expected tier: 15% reduction
Expected: accel=3.32, decel=4.93 (85% of originals)

// Test 3: Travel jerk with 10x cargo (no 2x penalty!)
originalAccel: 0.41, originalDecel: 0.82
cargoMultiplier: 10.0
Expected tier: 35% reduction (NOT 70% with 2x penalty)
Expected: accel=0.27, decel=0.53 (65% of originals)

// Test 4: Verify direction (jerk decreases, not increases)
For ANY cargo multiplier > 1.0:
newJerk < originalJerk (must be true)
```

#### Files Modified
- `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php` (fix)
- `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php` (fix)
- `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php` (fix)
- `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php` (fix + remove penalty)

#### Integration Points
- Uses PhysicsCalculator for cargo multiplier (WP-1)
- Uses BuildConfiguration tier lookup (WP-2)
- Called from FlightMechanicsOverrideFile during build

#### Notes for Implementation
- **Comment explicitly** that old code was backwards
- Explain why tier-based approach prevents extreme jerk reductions
- Travel jerk reduction should NOT be more aggressive than other jerk (old 2x penalty was wrong)
- Add XML comments explaining jerk decrease is physics-correct

---

### WP-5: Inertia and Acceleration

**Priority:** HIGH - Completes physics model  
**Effort:** 1.5 hours  
**Dependencies:** WP-1 (PhysicsCalculator), WP-2 (Configuration)  
**Risk:** MEDIUM (acceleration factor behavior researched but requires in-game validation)

#### Objective
Implement physics-correct inertia increases and acceleration factor scaling. Inertia scales with mass, acceleration factors scale to maintain responsiveness.

#### Context

**Inertia (Rotational Resistance):**
- **Physics:** Heavier ships turn more slowly (inertia ∝ mass)
- **Implementation:** Scale inertia with dampening factor for playability

**Acceleration Factors (Responsiveness):**
- **Research Confirmed:** Acceleration factors control time-to-speed (responsiveness), NOT top speed
- **Physics Formula:** `Δv ∝ (AccelerationFactor / Mass)`
- **When mass increases 4x:** Ratio becomes 1/4 → ship takes 4x longer to reach speed
- **Solution:** Scale acceleration factor WITH mass to maintain `AccelFactor/Mass` ratio

#### Deliverables

1. **Update:** `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php`

**Current (Backwards):**
```php
$value = $original * (1 + $massMultiplier);  // Slight increase
```

**New (Physics-Correct with Dampening):**
```php
$massRatio = $physicsCalc->getMassRatio();
$impactFactor = $config->getInertiaImpactFactor();  // Default 0.5

// Example: 2x mass, factor 0.5
//   massIncrease = 2.0 - 1.0 = 1.0
//   impact = 1.0 * 0.5 = 0.5
//   newInertia = original * 1.5 (150% of original)

$massIncrease = $massRatio - 1.0;
$dampedIncrease = $massIncrease * $impactFactor;
$newInertia = $originalInertia * (1.0 + $dampedIncrease);
```

**Apply to all 3 inertia components:**
- Pitch inertia
- Yaw inertia
- Roll inertia

2. **Update:** `src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php`

**Current (Incorrect):**
```php
$value = $original * (1 + $massMultiplier);  // Wrong formula
```

**New (Research-Backed, Maintains Responsiveness):**
```php
$massRatio = $physicsCalc->getMassRatio();
$responsivenessFactor = $config->getAccelerationResponsiveness();  // Default 1.0

// Scale acceleration factor proportionally to mass
// This maintains the AccelFactor/Mass ratio → preserves responsiveness
$newAccelFactor = $originalAccelFactor * $massRatio * $responsivenessFactor;

// Example: 4x mass with factor 1.0
//   newAccel = oldAccel * 4.0
//   Result: (4×AccelFactor) / (4×Mass) = AccelFactor/Mass (maintained!)

// Example: 4x mass with factor 0.7 (heavier feel)
//   newAccel = oldAccel * 4.0 * 0.7 = oldAccel * 2.8
//   Result: (2.8×AccelFactor) / (4×Mass) = 0.7 × original ratio (30% less responsive)
```

**Apply to all 4 components:**
- Forward acceleration
- Reverse acceleration
- Horizontal acceleration (strafe)
- Vertical acceleration

**Add XML Comments Explaining:**
```xml
<!-- ACCELERATION FACTORS: Scaled to maintain responsiveness -->
<!-- Physics: Δv ∝ (AccelerationFactor / Mass) -->
<!-- Mass increased 4x, so acceleration factor increased 4x -->
<!-- Result: Same AccelFactor/Mass ratio = same time-to-speed -->
<!-- Responsiveness factor: 1.0 (vanilla feel) -->
```

#### Verification
- [ ] Inertia values increase (never decrease)
- [ ] Dampening factor works correctly (0.5 → half impact)
- [ ] Acceleration factors scale proportionally with mass
- [ ] AccelFactor/Mass ratio maintained with responsiveness=1.0
- [ ] All 3 inertia components adjusted
- [ ] All 4 acceleration components adjusted
- [ ] Values never become zero or negative

#### Test Cases
```php
// Test 1: Inertia with 2x mass, default dampening (0.5)
originalInertia: 5.0
massRatio: 2.0
impactFactor: 0.5
Expected: 5.0 * 1.5 = 7.5 (50% increase, not 100%)

// Test 2: Inertia with 4x mass, full physics (1.0)
originalInertia: 3.0
massRatio: 4.0
impactFactor: 1.0
Expected: 3.0 * 4.0 = 12.0 (300% increase)

// Test 3: Acceleration with 4x mass, full responsiveness (1.0)
originalAccel: 2.0
massRatio: 4.0
responsiveness: 1.0
Expected: 2.0 * 4.0 = 8.0
Verify ratio: (8.0 / 4.0) = 2.0 = (2.0 / 1.0) ✓

// Test 4: Acceleration with 4x mass, heavier feel (0.7)
originalAccel: 2.0
massRatio: 4.0
responsiveness: 0.7
Expected: 2.0 * 4.0 * 0.7 = 5.6
Verify ratio: (5.6 / 4.0) = 1.4 = 0.7 × (2.0 / 1.0) ✓

// Test 5: Extreme case (10x mass with capped effectiveRatio)
baseMass: 205, cargo: 420000, massRatio: 204x
useEffectiveRatioCap: true
effectiveRatio: min(204, 10) = 10
Expected calculations use 10, not 204 (safety cap)
```

#### Files Modified
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php` (overhaul)
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php` (overhaul)

#### Integration Points
- Uses PhysicsCalculator for mass ratio (WP-1)
- Uses BuildConfiguration for factors (WP-2)
- Called from FlightMechanicsOverrideFile during build

#### Notes for Implementation
- **Acceleration factors are experimental** - research is solid but in-game feel needs validation
- Responsiveness factor allows users to tune if default doesn't feel right
- Inertia dampening balances realism vs playability
- Add clear comments explaining the physics reasoning
- Consider adding warnings in diagnostics if extreme values detected

---

### WP-6: XML Comments and Diagnostics

**Priority:** MEDIUM - Improves debuggability and transparency  
**Effort:** 2 hours  
**Dependencies:** WP-1 (PhysicsCalculator), WP-2 (Configuration)  
**Risk:** Low

#### Objective
Generate comprehensive XML comments explaining all physics calculations and create diagnostic logging for testing and tuning. Users should be able to understand what the mod changed and why.

#### Context
Users need transparency into calculations to:
- Understand what changed from vanilla
- Tune configuration effectively
- Debug issues (travel mode not working, ships too sluggish)
- Verify mod is working correctly

Developers need diagnostics to:
- Identify problematic ships
- Validate calculations
- Track extreme cases
- Support users

#### Deliverables

1. **Enhance:** `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php`

**Add comprehensive XML comments:**
```xml
<?xml version="1.0" encoding="utf-8"?>
<diff>
  <!-- ===================================================================== -->
  <!-- CARGO SIZE MOD: Physics Adjustments -->
  <!-- Ship: Shuyaku Vanguard (ship_arg_xl_carrier_01_a) -->
  <!-- Type: XL Transport -->
  <!-- Cargo multiplier: 4.0x -->
  <!-- ===================================================================== -->
  
  <!-- MASS CALCULATIONS -->
  <!-- Base mass: 650 kg (ship hull without cargo) -->
  <!-- Original cargo: 1,000 | Adjusted cargo: 4,000 -->
  <!-- Original full mass: 1,650 kg | Adjusted full mass: 4,650 kg -->
  <!-- Mass ratio: 2.82x (ship is 182% heavier) -->
  
  <replace sel="//macros/macro[@name='ship_arg_xl_carrier_01_a_macro']/properties/physics">
    <physics mass="4650">
      
      <!-- DRAG: Tier-based reduction -->
      <!-- Cargo multiplier: 4.0x → Tier: 30% reduction -->
      <!-- Original forward drag: 17.9 → Adjusted: 12.5 (70% remains) -->
      <!-- Purpose: Compensate for fixed engine thrust -->
      <!-- Physics: v_max = Thrust / Drag (thrust fixed, reduce drag) -->
      <drag forward="12.5" reverse="18.7" horizontal="23.4" vertical="35.1"
            pitch="10.5" yaw="10.5" roll="7.8"/>
      
      <!-- INERTIA: Increased with dampening -->
      <!-- Mass ratio: 2.82x | Dampening factor: 0.5 | Impact: 91% increase -->
      <!-- Original pitch inertia: 5.0 → Adjusted: 9.5 (2.82x mass, dampened) -->
      <!-- Physics: Heavier ships turn more slowly (dampened for playability) -->
      <inertia pitch="9.5" yaw="9.5" roll="4.0"/>
      
      <!-- ACCELERATION FACTORS: Scaled to maintain responsiveness -->
      <!-- Mass increased 2.82x → Acceleration factors increased 2.82x -->
      <!-- Original forward: 2.0 → Adjusted: 5.6 -->
      <!-- Physics: Δv ∝ (AccelFactor / Mass) - ratio maintained -->
      <!-- Responsiveness factor: 1.0 (vanilla feel) -->
      <accfactors forward="5.6" reverse="4.2" horizontal="7.0" vertical="10.5"/>
    </physics>
  </replace>
  
  <replace sel="//macros/macro[@name='ship_arg_xl_carrier_01_a_macro']/properties/jerk">
    <!-- JERK: Tier-based reduction -->
    <!-- Cargo multiplier: 4.0x → Tier: 15% reduction -->
    <!-- Original strafe jerk: 5.8 → Adjusted: 4.9 (85% remains) -->
    <!-- Physics: Heavier ships have slower acceleration changes -->
    
    <jerk strafe="4.9" angular="7.3">
      <!-- Forward jerk: General movement -->
      <!-- Original: accel=3.9, decel=5.8 → Adjusted: accel=3.3, decel=4.9 -->
      <forward accel="3.3" decel="4.9"/>
      
      <!-- Boost jerk: Boost mode -->
      <!-- Original: accel=7.8 → Adjusted: accel=6.6 -->
      <forward_boost accel="6.6"/>
      
      <!-- Travel jerk: Critical for travel mode -->
      <!-- Original: accel=0.41, decel=0.82 → Adjusted: accel=0.35, decel=0.70 -->
      <!-- Note: Removed arbitrary 2x penalty from old calculations -->
      <forward_travel accel="0.35" decel="0.70"/>
    </jerk>
  </replace>
  
  <!-- END CARGO SIZE MOD -->
</diff>
```

**Key Information to Include:**
- Ship identification (name, macro ID, type)
- Mass calculations (base, original full, adjusted full, ratio)
- Tier applied for each adjustment type
- Original vs adjusted values for key properties
- Brief physics explanation for each adjustment
- Note about removed penalties or fixes

2. **Create:** `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php`

```php
<?php
declare(strict_types=1);

namespace Mods\CargoSizesMod\Output;

class DiagnosticsLogger
{
    private array $ships = [];
    private array $warnings = [];
    
    public function logShip(
        ShipDef $ship,
        PhysicsCalculator $physics,
        ReductionTier $dragTier,
        ReductionTier $jerkTier,
        BuildConfiguration $config
    ): void;
    
    public function addWarning(string $shipName, string $warning): void;
    public function getWarnings(): array;
    public function generateReport(): string;
    public function writeToFile(string $filePath): void;
}
```

**Report Format:**
```
================================================================================
CARGO SIZE MOD - Physics Diagnostics Report
Build Date: 2026-02-09 14:32:15
Configuration: Tier-based system (default tiers)
================================================================================

Ship: Shuyaku Vanguard (ship_arg_xl_carrier_01_a)
Class: XL Transport
-------------------------------------------------
Mass:
  Base mass: 650 kg
  Original cargo: 1,000 | New cargo: 4,000 (4.0x multiplier)
  Original full: 1,650 kg | New full: 4,650 kg
  Mass ratio: 2.82x (182% increase)

Tiers Applied:
  Drag tier: 4.0x → 30% reduction (70% drag remains)
  Jerk tier: 4.0x → 15% reduction (85% jerk remains)

Configuration:
  Inertia impact factor: 0.5 (inertia increased 91%)
  Acceleration responsiveness: 1.0 (vanilla feel)
  Effective ratio cap: ENABLED
  
Status: ✓ OK

================================================================================

Ship: Magnetar (ship_ter_xl_miner_01_a)
Class: XL Miner
-------------------------------------------------
Mass:
  Base mass: 205 kg
  Original cargo: 42,000 | New cargo: 420,000 (10.0x multiplier)
  Original full: 42,205 kg | New full: 420,205 kg
  Mass ratio: 9.96x (896% increase) → Capped at 10.0x

Tiers Applied:
  Drag tier: 10.0x → 70% reduction (30% drag remains) [SAFETY CAP]
  Jerk tier: 10.0x → 35% reduction (65% jerk remains)

Configuration:
  Inertia impact factor: 0.5 (inertia increased 450%)
  Acceleration responsiveness: 1.0 (vanilla feel)
  Effective ratio cap: ENABLED (prevented 896x ratio from being used)
  
Status: ⚠️ WARNING - High mass ratio (>5.0x), test carefully in-game

================================================================================

Summary:
  Total ships: 87
  Ships with mass ratio < 2.0x: 42 (low impact)
  Ships with mass ratio 2.0-5.0x: 31 (moderate impact)
  Ships with mass ratio 5.0-10.0x: 11 (high impact - test carefully)
  Ships with mass ratio > 10.0x: 3 (extreme - effective ratio cap applied)

Warnings: 14 ships flagged for testing

Configuration Used:
  Drag reduction tiers: 4 tiers (10%, 30%, 50%, 70% max)
  Jerk reduction tiers: 4 tiers (5%, 15%, 25%, 35% max)
  Inertia impact factor: 0.5
  Acceleration responsiveness: 1.0
  Effective ratio cap: ENABLED

================================================================================
```

3. **Integration:**
- Call DiagnosticsLogger during build process
- Write diagnostics file to `build/physics-diagnostics.txt`
- Display summary in console after build completion

#### Verification
- [ ] XML comments present and accurate
- [ ] All key information included (mass, tiers, values)
- [ ] Diagnostics file generated successfully
- [ ] Report shows all processed ships
- [ ] Warnings generated for extreme cases
- [ ] Console displays summary after build

#### Files Modified
- `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php` (enhance comments)
- `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php` (create)
- Build scripts (integrate logger)

#### Integration Points
- Reads data from PhysicsCalculator (WP-1)
- Uses tier information from BuildConfiguration (WP-2)
- Called from build process after each ship processed

#### Notes for Implementation
- Comments should be concise but informative
- Diagnostics file helps users understand mod behavior
- Warnings help identify ships that need extra testing
- Consider adding configuration validation to diagnostics
- Performance: Logging should not significantly slow build

---

### WP-7: Testing Infrastructure

**Priority:** HIGH - Ensures correctness  
**Effort:** 2.5 hours  
**Dependencies:** WP-1 to WP-5 (all physics implementations)  
**Risk:** Low

#### Objective
Create comprehensive unit tests verifying all physics calculations are correct, formulas work as expected, and edge cases are handled properly.

#### Context
Testing critical because:
- Physics formulas are easy to get backwards
- Edge cases (extreme cargo) can cause issues
- Tier-based system has multiple code paths
- Regression prevention for future changes

#### Deliverables

1. **Create:** `tests/CargoSizesModTests/PhysicsCalculatorTest.php`

```php
<?php
declare(strict_types=1);

namespace CargoSizesModTests;

use PHPUnit\Framework\TestCase;
use Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;

class PhysicsCalculatorTest extends TestCase
{
    public function testMassRatioIsGreaterThanOne(): void
    {
        // When cargo increases, mass ratio should be > 1.0
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $this->assertGreaterThan(1.0, $calc->getMassRatio());
    }
    
    public function testMassRatioCalculation(): void
    {
        // baseMass: 650, originalCargo: 1000, adjustedCargo: 4000
        // originalFullMass: 1650, adjustedFullMass: 4650
        // massRatio: 4650 / 1650 = 2.818...
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $this->assertEqualsWithDelta(2.818, $calc->getMassRatio(), 0.001);
    }
    
    public function testEffectiveRatioCap(): void
    {
        // Extreme cargo-heavy ship: base 205, cargo 42000 → 420000
        // Mass ratio would be ~9.96, but capped at cargoMultiplier (10.0)
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $this->assertEqualsWithDelta(10.0, $calc->getEffectiveRatio(), 0.1);
    }
    
    public function testEffectiveRatioNoCap(): void
    {
        // Same scenario but cap disabled
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, false);
        $this->assertGreaterThan(9.0, $calc->getEffectiveRatio());
        $this->assertLessThan(10.0, $calc->getEffectiveRatio());
    }
    
    public function testInverseMassRatio(): void
    {
        // Mass ratio 2.0 → inverse should be 0.5
        $calc = new PhysicsCalculator(100, 100, 300, 3.0, true);
        // originalFull: 200, adjustedFull: 400, ratio: 2.0
        $this->assertEqualsWithDelta(0.5, $calc->getInverseMassRatio(), 0.01);
    }
    
    public function testMassRatioSquared(): void
    {
        // Mass ratio 2.0 → squared should be 4.0
        $calc = new PhysicsCalculator(100, 100, 300, 3.0, true);
        $this->assertEqualsWithDelta(4.0, $calc->getMassRatioSquared(), 0.01);
    }
    
    public function testMassIncreasePercent(): void
    {
        // Mass ratio 2.0 → 100% increase
        $calc = new PhysicsCalculator(100, 100, 300, 3.0, true);
        $this->assertEqualsWithDelta(100.0, $calc->getMassIncreasePercent(), 1.0);
    }
}
```

2. **Create:** `tests/CargoSizesModTests/TierSystemTest.php`

```php
class TierSystemTest extends TestCase
{
    public function testFindTierForMultiplier(): void
    {
        $config = $this->createConfigWithDefaultTiers();
        
        // 4x cargo should find tier with maxMultiplier=4.0
        $tier = $config->findDragTierForMultiplier(4.0);
        $this->assertEquals(0.30, $tier->getReductionPercent());
    }
    
    public function testFindTierForBetweenValues(): void
    {
        // 3x cargo should find first tier >= 3.0 (which is 4.0 tier)
        $config = $this->createConfigWithDefaultTiers();
        $tier = $config->findDragTierForMultiplier(3.0);
        $this->assertEquals(4.0, $tier->getMaxMultiplier());
    }
    
    public function testFindTierForExtreme(): void
    {
        // 10x cargo should find safety cap tier (999)
        $config = $this->createConfigWithDefaultTiers();
        $tier = $config->findDragTierForMultiplier(10.0);
        $this->assertEquals(0.70, $tier->getReductionPercent());
    }
    
    public function testTierValidationRejectsInvalidPercent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ReductionTier(4.0, 1.5);  // > 1.0 should fail
    }
}
```

3. **Create:** `tests/CargoSizesModTests/DragAdjustmentTest.php`

```php
class DragAdjustmentTest extends TestCase
{
    public function testDragDecreasesWithTierSystem(): void
    {
        // Original drag: 15.0, 4x cargo → 30% reduction
        $originalDrag = 15.0;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $originalDrag * (1.0 - $tier->getReductionPercent());
        
        $this->assertLessThan($originalDrag, $newDrag);
        $this->assertEqualsWithDelta(10.5, $newDrag, 0.1);  // 70% of 15.0
    }
    
    public function testDragNeverNegative(): void
    {
        // Even with extreme reduction, drag should stay positive
        $originalDrag = 5.0;
        $tier = new ReductionTier(999, 0.70);  // 70% reduction
        
        $newDrag = $originalDrag * (1.0 - $tier->getReductionPercent());
        
        $this->assertGreaterThan(0, $newDrag);
    }
    
    public function testSafetyCap(): void
    {
        // Max reduction is 70% (30% remains)
        $originalDrag = 20.0;
        $tier = new ReductionTier(999, 0.70);  // Safety cap
        
        $newDrag = $originalDrag * (1.0 - $tier->getReductionPercent());
        
        $this->assertEqualsWithDelta(6.0, $newDrag, 0.1);  // 30% of 20.0
        $this->assertGreaterThan(0.25 * $originalDrag, $newDrag);  // Never below 25%
    }
}
```

4. **Create:** `tests/CargoSizesModTests/JerkAdjustmentTest.php`

```php
class JerkAdjustmentTest extends TestCase
{
    public function testJerkDecreases(): void
    {
        // Verify jerk goes DOWN, not UP
        $originalJerk = 5.8;
        $tier = new ReductionTier(4.0, 0.15);  // 15% reduction
        
        $newJerk = $originalJerk * (1.0 - $tier->getReductionPercent());
        
        $this->assertLessThan($originalJerk, $newJerk);
        $this->assertGreaterThan(0, $newJerk);
    }
    
    public function testTravelJerkNoExtraPenalty(): void
    {
        // Travel jerk should use same tier as regular jerk (no 2x penalty)
        $originalJerk = 0.82;
        $jerkTier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $originalJerk * (1.0 - $jerkTier->getReductionPercent());
        
        // Should be 85% of original, not 70% (which would be 2x penalty)
        $this->assertEqualsWithDelta(0.697, $newJerk, 0.01);
        $this->assertGreaterThan(0.7 * $originalJerk, $newJerk);  // More than 70%
    }
}
```

5. **Create:** `tests/CargoSizesModTests/InertiaAdjustmentTest.php`

```php
class InertiaAdjustmentTest extends TestCase
{
    public function testInertiaIncreases(): void
    {
        // Verify inertia goes UP with mass
        $originalInertia = 5.0;
        $massRatio = 2.0;
        $impactFactor = 0.5;
        
        $massIncrease = $massRatio - 1.0;  // 1.0
        $dampedIncrease = $massIncrease * $impactFactor;  // 0.5
        $newInertia = $originalInertia * (1.0 + $dampedIncrease);  // 7.5
        
        $this->assertGreaterThan($originalInertia, $newInertia);
        $this->assertEqualsWithDelta(7.5, $newInertia, 0.1);
    }
    
    public function testInertiaFullPhysics(): void
    {
        // With factor 1.0, inertia should scale fully with mass
        $originalInertia = 3.0;
        $massRatio = 4.0;
        $impactFactor = 1.0;
        
        $newInertia = $originalInertia * $massRatio;  // 12.0
        
        $this->assertEqualsWithDelta(12.0, $newInertia, 0.1);
    }
}
```

6. **Create:** `tests/CargoSizesModTests/AccelerationAdjustmentTest.php`

```php
class AccelerationAdjustmentTest extends TestCase
{
    public function testAccelerationScalesWithMass(): void
    {
        // Verify acceleration factor increases proportionally
        $originalAccel = 2.0;
        $massRatio = 4.0;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertGreaterThan($originalAccel, $newAccel);
        $this->assertEqualsWithDelta(8.0, $newAccel, 0.1);
    }
    
    public function testResponsivenessRatioMaintained(): void
    {
        // With responsiveness=1.0, AccelFactor/Mass ratio should be constant
        $originalAccel = 2.0;
        $originalMass = 1000.0;
        $massRatio = 4.0;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;  // 8.0
        $newMass = $originalMass * $massRatio;  // 4000.0
        
        $originalRatio = $originalAccel / $originalMass;  // 0.002
        $newRatio = $newAccel / $newMass;  // 0.002
        
        $this->assertEqualsWithDelta($originalRatio, $newRatio, 0.0001);
    }
    
    public function testHeavierFeelWithLowerResponsiveness(): void
    {
        // With responsiveness < 1.0, ship should feel heavier
        $originalAccel = 2.0;
        $massRatio = 4.0;
        $responsiveness = 0.7;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;  // 5.6
        
        // New ratio should be 70% of original (heavier feel)
        $this->assertEqualsWithDelta(5.6, $newAccel, 0.1);
        $this->assertLessThan($originalAccel * $massRatio, $newAccel);
    }
}
```

#### Verification
- [ ] All tests pass
- [ ] PHPUnit coverage report shows good coverage
- [ ] Edge cases tested (extreme multipliers, zero values, negative scenarios)
- [ ] Tier system thoroughly tested
- [ ] Physics direction verified (jerk down, inertia up, drag down)

#### Files Created
- `tests/CargoSizesModTests/PhysicsCalculatorTest.php`
- `tests/CargoSizesModTests/TierSystemTest.php`
- `tests/CargoSizesModTests/DragAdjustmentTest.php`
- `tests/CargoSizesModTests/JerkAdjustmentTest.php`
- `tests/CargoSizesModTests/InertiaAdjustmentTest.php`
- `tests/CargoSizesModTests/AccelerationAdjustmentTest.php`

#### Notes for Implementation
- Use PHPUnit data providers for multiple test scenarios
- Test both with and without effective ratio cap
- Verify tier boundaries carefully
- Test extreme values (0, very large, negative)
- Ensure backwards compatibility tests if needed

---

### WP-8: Documentation Updates

**Priority:** CRITICAL - Users need tuning guide  
**Effort:** 3 hours  
**Dependencies:** WP-1 to WP-6 (all implementations)  
**Risk:** Low

#### Objective
Update all user-facing and developer-facing documentation to explain tier-based system, physics calculations, and provide comprehensive tuning guide for users.

#### Context
**User Documentation Critical Because:**
- Tier-based system is new concept
- Users need to tune for their gameplay preferences
- Travel mode issues require configuration changes
- Users need to understand what each parameter does

**Developer Documentation Critical Because:**
- Future maintainers need to understand architecture
- Data flow documentation must be updated
- Manifest documents require updates per AGENTS.md

#### Deliverables

1. **Create:** `docs/physics-tuning-guide.md`

**Complete user guide covering:**
- Overview of tier-based system
- Why tier-based approach chosen (mass ratio varies wildly)
- Configuration parameters explained (drag tiers, jerk tiers, factors)
- Effects of each parameter (higher drag reduction = faster, etc.)
- Common tuning scenarios:
  - Travel mode not working
  - Ships too sluggish
  - Ships too responsive (unrealistic)
  - Different behavior by ship size
- Testing workflow (baseline → identify issues → incremental tuning)
- Advanced tier customization
- FAQ section
- Full configuration reference with value ranges

**Template already provided in plan - implement exactly as specified.**

2. **Update:** `docs/agents/project-manifest/data-flows.md`

**Add section:** "Physics Calculation Flow"

```markdown
### Physics Calculation Flow

1. **Extract Ship Data**
   - Mass, drag (7 components), inertia (3 components), jerk, acceleration factors

2. **Extract Cargo Data**
   - Original capacity, adjusted capacity (multiplied)

3. **Calculate Mass Physics**
   - baseMass = ship mass
   - originalFullMass = baseMass + originalCargo
   - adjustedFullMass = baseMass + adjustedCargo
   - massRatio = adjustedFullMass / originalFullMass (> 1.0)
   - effectiveRatio = min(massRatio, cargoMultiplier) if capped

4. **Find Appropriate Tiers**
   - dragTier = findDragTierForMultiplier(cargoMultiplier)
   - jerkTier = findJerkTierForMultiplier(cargoMultiplier)

5. **Calculate Tier-Based Adjustments**
   - Drag: `newDrag = originalDrag * (1.0 - dragTier.reductionPercent)`
   - Jerk: `newJerk = originalJerk * (1.0 - jerkTier.reductionPercent)`
   - Inertia: `newInertia = originalInertia * (1 + (massRatio-1) * impactFactor)`
   - Accel: `newAccel = originalAccel * massRatio * responsiveness`

6. **Generate XML Overrides**
   - Replace physics section with adjusted values
   - Replace jerk section with adjusted values
   - Include comprehensive comments explaining calculations

7. **Log Diagnostics**
   - Record all calculations for each ship
   - Flag warnings for extreme cases
   - Generate diagnostics report
```

3. **Update:** `README.md`

**Add section:** "How It Works - Tier-Based Physics"

```markdown
## How It Works - Tier-Based Physics

### Why Tier-Based?

Ships vary wildly in cargo-to-mass ratios:
- **Combat ships**: Small cargo vs heavy hull → 10x cargo = ~10% heavier
- **Cargo ships**: Massive cargo vs light hull → 10x cargo = ~900% heavier!

Formula-based adjustments would make cargo ships undriveable (99% drag reduction). Tier-based system treats all ships with same cargo multiplier equally (predictable, safe, tunable).

### Configuration

Adjustments organized into **tiers** by cargo multiplier:

```json
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },  // 2x cargo: 10% reduction
  { "maxMultiplier": 4.0, "reductionPercent": 0.30 },  // 4x cargo: 30% reduction
  { "maxMultiplier": 8.0, "reductionPercent": 0.50 },  // 8x cargo: 50% reduction
  { "maxMultiplier": 999, "reductionPercent": 0.70 }   // 10x+: 70% reduction (safety cap)
]
```

All ships with 4x cargo get **30% drag reduction** regardless of their mass ratio.

### What Gets Adjusted

1. **Mass** - Directly increased by cargo difference
2. **Drag** (tier-based) - Reduced to compensate for fixed engine thrust
3. **Jerk** (tier-based) - Reduced for heavier feel
4. **Inertia** (dampened) - Increased proportionally to mass
5. **Acceleration** (scaled) - Maintains responsiveness despite mass increase

### Physics Formulas

- **Drag reduction:** `newDrag = originalDrag × (1 - tierPercent)`
- **Jerk reduction:** `newJerk = originalJerk × (1 - tierPercent)`
- **Inertia increase:** `newInertia = originalInertia × (1 + (massRatio-1) × dampFactor)`
- **Accel scaling:** `newAccel = originalAccel × massRatio × responsiveness`

### Tuning Your Experience

See [Physics Tuning Guide](docs/physics-tuning-guide.md) for:
- Detailed parameter explanations
- Common tuning scenarios (travel mode issues, too sluggish, etc.)
- Testing workflow
- Value ranges and safety limits

### Travel Mode

Travel mode works by:
1. **Aggressive drag reduction** (70% for high-tier cargo) enables reaching speed
2. **Jerk reduction** (35% for high-tier) smooths acceleration ramp
3. **Acceleration scaling** maintains responsiveness

**Note:** Travel speed depends on engine thrust (player-chosen equipment). Ships with weak engines may need upgrades for high cargo multipliers.
```

4. **Update:** `docs/agents/project-manifest/tech-stack.md`

**Add entry:** "Tier-Based Adjustment System"

```markdown
### 12. Tier-Based Adjustment System

**Purpose:** Provide uniform, predictable physics adjustments regardless of ship's cargo-to-mass ratio.

**Problem Solved:** Mass ratio varies wildly by ship type (combat: 1.1x, cargo: 9x for same cargo multiplier). Formula-based adjustments would create extreme values for cargo-heavy ships.

**Structure:**
- Configuration defines tiers by cargo multiplier threshold
- Each tier has independent reduction percentage
- Ships find appropriate tier, apply that tier's adjustment
- Safety caps prevent extreme adjustments

**Example:**
```json
"dragReductionTiers": [
  { "maxMultiplier": 4.0, "reductionPercent": 0.30 }
]
```
All ships with ≤4x cargo get 30% drag reduction (70% drag remains).

**Benefits:**
- Uniform behavior across all ship types
- Independently tunable per cargo multiplier
- Safety caps built in (max 70% drag reduction)
- User-friendly configuration
```

5. **Update:** `docs/agents/project-manifest/constraints.md` (if changes to build process)

Add any new constraints introduced by tier system.

6. **Update:** `docs/agents/project-manifest/public-api.md`

Add new classes, methods:
- `PhysicsCalculator` class methods
- `ReductionTier` class methods
- `BuildConfiguration` new methods for tiers
- `DiagnosticsLogger` class methods

#### Verification
- [ ] Physics tuning guide is complete and clear
- [ ] data-flows.md updated with physics flow
- [ ] README.md explains tier-based system
- [ ] tech-stack.md documents pattern
- [ ] public-api.md shows new signatures
- [ ] All documentation consistent with implementation
- [ ] No broken links

#### Files Modified
- `docs/physics-tuning-guide.md` (create)
- `docs/agents/project-manifest/data-flows.md` (update)
- `docs/agents/project-manifest/tech-stack.md` (update)
- `docs/agents/project-manifest/public-api.md` (update)
- `README.md` (update)

#### Notes for Implementation
- **Physics tuning guide is CRITICAL** - template provided in plan
- Follow X4 Core AGENTS.md manifest rules
- Update "Last Updated" dates
- Maintain consistent terminology (tier-based, safety cap, mass ratio)
- Include practical examples in all docs

---

### WP-9: Verification and Integration

**Priority:** CRITICAL - Ensures everything works  
**Effort:** 2.5 hours  
**Dependencies:** WP-1 to WP-8 (complete implementation)  
**Risk:** MEDIUM (in-game testing required)

#### Objective
Verify complete implementation works correctly through build testing, unit tests, XML inspection, and in-game validation. Document any issues found and verify fixes.

#### Context
Final integration testing ensures:
- Build process completes successfully
- Generated XML is correct
- Diagnostics are useful
- In-game behavior matches expectations
- Configuration changes work as intended

#### Deliverables

1. **Build Verification**

```bash
# Clean build
composer build

# Verify:
✓ Build completes without errors
✓ XML files generated in build/ directory
✓ All cargo multipliers processed (2x, 4x, 6x, 8x, 10x)
✓ physics-diagnostics.txt created
✓ Console shows summary statistics
```

2. **Unit Test Verification**

```bash
# Run all tests
composer test

# Verify:
✓ All PhysicsCalculatorTest tests pass
✓ All TierSystemTest tests pass
✓ All DragAdjustmentTest tests pass
✓ All JerkAdjustmentTest tests pass
✓ All InertiaAdjustmentTest tests pass
✓ All AccelerationAdjustmentTest tests pass
✓ No regression in existing tests
```

3. **Static Analysis**

```bash
# Run PHPStan
composer analyze

# Verify:
✓ No type errors
✓ All method signatures correct
✓ No undefined methods/properties
```

4. **XML Inspection**

**Steps:**
1. Open generated XML file (e.g., `build/v2-1-1-for-v7-6/2x/extensions/mistralys_cargosizes_2x/content.xml`)
2. Search for a large cargo ship (e.g., "Shuyaku" or "ship_arg_xl_carrier")
3. **Verify XML comments present and comprehensive**
4. **Verify drag values decreased** (not increased)
5. **Verify jerk values decreased** (not increased)
6. **Verify inertia values increased**
7. **Verify acceleration factor values increased**
8. **Verify calculations explained in comments**

**Example checks:**
```xml
<!-- Original drag: 17.9 → Adjusted: 12.5 -->
☑️ Drag decreased (17.9 → 12.5)

<!-- Original jerk: 5.8 → Adjusted: 4.9 -->
☑️ Jerk decreased (5.8 → 4.9)

<!-- Travel jerk: accel=0.35 -->
☑️ No mention of "2x penalty" in comments

<!-- Mass ratio: 2.82x -->
☑️ Mass ratio > 1.0

<!-- Tier: 30% reduction -->
☑️ Tier information present
```

5. **Diagnostics Review**

**Open:** `build/physics-diagnostics.txt`

**Verify:**
- [ ] All processed ships listed
- [ ] Mass calculations shown correctly
- [ ] Tiers identified for each ship
- [ ] Warnings present for extreme cases (mass ratio > 5.0)
- [ ] Summary statistics accurate
- [ ] Configuration used is documented

6. **In-Game Testing Plan - Baseline (2x Cargo)**

**Preparation:**
1. Install 2x cargo variant in game
2. Choose test ship: Shuyaku Vanguard (XL Transport) or Magnetar (XL Miner)
3. Ensure best available engine installed

**Tests:**
- [ ] Ship loads in game without errors
- [ ] Ship can accelerate forward normally
- [ ] Ship can turn (may be slightly sluggish, should be acceptable)
- [ ] Ship enters travel mode successfully (engagement animation plays)
- [ ] Ship **accelerates in travel mode** and reaches normal travel speeds
- [ ] Ship exits travel mode cleanly (no stuck states)
- [ ] No physics glitches (spinning, bouncing, etc.)
- [ ] Ship feels "heavier" but still pilotable

**Expected Result:** 2x cargo should work perfectly with minimal sluggishness.

7. **In-Game Testing - Moderate (4x Cargo)**

**Tests:**
- [ ] All baseline tests pass
- [ ] Noticeably more sluggish but still responsive enough
- [ ] Travel mode acceleration may be slower but still functional
- [ ] No excessive drift when stopping
- [ ] Turning is slower but acceptable

**Expected Result:** 4x cargo should be playable for dedicated cargo haulers, with noticeable "heavy ship" feel.

8. **In-Game Testing - Extreme (10x Cargo)**

**Test Ship:** Magnetar, Tokyo, or largest cargo ship available

**Tests:**
- [ ] Ship remains pilotable (even if very sluggish)
- [ ] Ship CAN enter travel mode (critical requirement)
- [ ] Travel mode DOES accelerate (may be slow, but should work)
- [ ] No game crashes or physics errors
- [ ] Ship doesn't become stuck or unresponsive

**Expected Result:** 10x cargo should be **functional but challenging**. Players prioritizing cargo over agility should accept sluggishness.

**If travel mode doesn't work:** Flag for configuration tuning (increase drag reduction tier for 10x).

9. **Configuration Testing**

**Test different configurations to verify system works:**

**Test A: More Aggressive Drag Reduction**
```json
"dragReductionTiers": [
  { "maxMultiplier": 999, "reductionPercent": 0.80 }  // Was 0.70
]
```
- [ ] Rebuild succeeds
- [ ] XML shows different drag values
- [ ] In-game: Ships more responsive (test 10x cargo)

**Test B: Heavier Feel (Lower Responsiveness)**
```json
"accelerationResponsiveness": 0.7  // Was 1.0
```
- [ ] Rebuild succeeds
- [ ] In-game: Ships feel heavier, less snappy

**Test C: Higher Inertia Impact**
```json
"inertiaImpactFactor": 0.8  // Was 0.5
```
- [ ] Rebuild succeeds
- [ ] In-game: Ships turn more slowly

10. **Regression Testing**

**Verify no existing functionality broken:**
- [ ] 2x cargo still works as before (or better)
- [ ] FOMOD installer builds correctly
- [ ] Reference generation works
- [ ] All Composer scripts functional

11. **Issue Documentation**

**If issues found, document:**
- Ship name and cargo multiplier
- Expected behavior vs actual behavior
- Configuration used
- Steps to reproduce
- Proposed fix

#### Success Criteria

**CRITICAL (Must Pass):**
- [ ] Shuyaku Vanguard with 2x cargo enters and flies in travel mode
- [ ] No backwards physics (jerk decreases, not increases)
- [ ] Build completes successfully with all cargo multipliers
- [ ] All unit tests pass
- [ ] XML contains comprehensive comments

**IMPORTANT (Should Pass):**
- [ ] Ships with 4x cargo remain highly pilotable
- [ ] Configuration changes work as expected
- [ ] Diagnostics file provides useful information
- [ ] Travel mode works for moderate cargo multipliers (2x-4x)

**NICE TO HAVE:**
- [ ] Ships with 10x cargo still flyable (even if sluggish)
- [ ] Different configurations provide noticeable differences
- [ ] No warnings in diagnostics for common ships

#### Deliverables

1. **Verification Report** documenting:
   - All tests performed
   - Results (pass/fail)
   - Issues found and resolution status
   - In-game testing notes
   - Configuration tuning recommendations

2. **Updated Configuration** if defaults need adjustment based on testing

3. **Known Issues Document** if any limitations discovered

#### Files Created
- `docs/verification-report.md` (testing results)
- `docs/known-issues.md` (if needed)

#### Notes for Implementation
- **In-game testing is CRITICAL** - code can compile but behave wrong
- Test multiple ship types (combat, transport, miner, resupplier)
- Document "feel" of ships (subjective but important)
- If travel mode fails with default config, adjust drag reduction tiers
- Performance with different engines should be documented
- Screenshots/videos of testing helpful for documentation

---

## 📊 Implementation Strategy

### Recommended Sequence

**Phase 1: Foundation (WP-1, WP-2)** - 3.5 hours
- Can be implemented in parallel or sequence
- No dependencies between them
- Creates foundation for all other work

**Phase 2: Physics Implementations (WP-3, WP-4, WP-5)** - 4.5 hours
- Can be implemented in parallel (3 developers) or sequence (1 developer)
- All depend on WP-1 and WP-2
- Each is independent of others

**Phase 3: Visibility (WP-6)** - 2 hours
- Depends on WP-1, WP-2
- Can be implemented in parallel with WP-3/4/5 if needed
- Improves debugging of physics implementations

**Phase 4: Quality Assurance (WP-7, WP-8)** - 5.5 hours
- WP-7 depends on WP-1 to WP-5
- WP-8 depends on WP-1 to WP-6
- Can be partially parallelized (tests vs docs)

**Phase 5: Integration (WP-9)** - 2.5 hours
- Depends on all previous work packages
- Must be last
- Includes in-game testing (time-consuming)

### Resource Allocation

**Single Developer:**
- Day 1: WP-1, WP-2 (3.5h)
- Day 2: WP-3, WP-4 (3h)
- Day 3: WP-5, WP-6 (3.5h)
- Day 4: WP-7 (2.5h)
- Day 5: WP-8 (3h)
- Day 6: WP-9 (2.5h) + buffer

**Multiple Developers:**
- Day 1: Developer A: WP-1, Developer B: WP-2
- Day 2: Developer A: WP-3, Developer B: WP-4, Developer C: WP-5
- Day 3: Developer A: WP-6, Developer B: WP-7, Developer C: WP-8 (start)
- Day 4: Complete WP-8, all developers: WP-9

### Risk Mitigation

**Medium Risk Items:**
- WP-5: Acceleration factor behavior (research solid, but in-game validation needed)
  - **Mitigation:** Include configurability, extensive testing
- WP-9: In-game testing (subjective, time-consuming)
  - **Mitigation:** Clear success criteria, multiple ship types, multiple multipliers

**Contingency:**
- If acceleration factors feel wrong in-game, default responsiveness can be adjusted without code changes (configuration only)
- If extreme cargo multipliers problematic, can adjust tier percentages in configuration
- Buffer time included in estimates

---

## 🎯 Definition of Done

A work package is considered complete when:

### Code
- [ ] All code compiles without errors
- [ ] PHPStan analysis passes (level 5+)
- [ ] All methods have PHPDoc comments
- [ ] Code follows project conventions (per AGENTS.md constraints.md)
- [ ] No debug code or commented-out sections

### Tests
- [ ] Unit tests written and passing
- [ ] Edge cases covered
- [ ] Test coverage ≥ 80% for new code
- [ ] No failing tests in entire suite

### Documentation
- [ ] User-facing docs updated (if applicable)
- [ ] Developer docs updated (data-flows.md, tech-stack.md)
- [ ] Manifest documents updated (per AGENTS.md)
- [ ] Code comments explain "why", not just "what"

### Integration
- [ ] Build process completes successfully
- [ ] Generated output verified (XML inspection)
- [ ] No regressions in existing functionality
- [ ] Performance acceptable (build time similar to before)

### Verification
- [ ] Manual testing completed (for WP-9)
- [ ] Issues documented
- [ ] Configuration validated
- [ ] Success criteria met

---

## 📞 Support and Questions

### During Implementation

If you encounter issues:

1. **Check constraints.md** - Ensure not violating project rules
2. **Review tech-stack.md** - Verify following established patterns
3. **Consult data-flows.md** - Understand data flow through system
4. **Review original plan** - Detailed rationale and context provided

### After Implementation

- Generated documentation (physics-tuning-guide.md) helps users
- Diagnostics (physics-diagnostics.txt) helps debugging
- XML comments explain calculations to users
- Verification report documents testing results

---

## 🏁 Completion Checklist

When all work packages complete:

- [ ] All 9 work packages marked complete
- [ ] Build generates correct XML for all cargo multipliers
- [ ] All unit tests pass
- [ ] PHPStan analysis clean
- [ ] User documentation complete (tuning guide)
- [ ] Developer documentation updated (manifest)
- [ ] In-game testing performed and documented
- [ ] Shuyaku Vanguard enters travel mode successfully with 2x cargo ← **CRITICAL SUCCESS CRITERION**
- [ ] Configuration system tested with multiple variants
- [ ] Known issues documented (if any)
- [ ] Verification report complete

---

**STATUS: READY_FOR_ENGINEERING**

---

## Appendices

### A. Key Files Reference

**Foundation:**
- `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php` (WP-1)
- `src/Mods/CargoSizesMod/Build/ReductionTier.php` (WP-2)
- `config/build-config.json` (WP-2)

**Physics Calculations:**
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php` (WP-3)
- `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk*.php` (WP-4)
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php` (WP-5)
- `src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php` (WP-5)

**Diagnostics:**
- `src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php` (WP-6)
- `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php` (WP-6)

**Documentation:**
- `docs/physics-tuning-guide.md` (WP-8)
- `docs/agents/project-manifest/data-flows.md` (WP-8)
- `README.md` (WP-8)

### B. Physics Formulas Quick Reference

```
Mass Ratio:
  massRatio = (baseMass + adjustedCargo) / (baseMass + originalCargo)
  Always > 1.0 when cargo increases

Tier-Based Drag Reduction:
  tier = findDragTierForMultiplier(cargoMultiplier)
  newDrag = originalDrag × (1.0 - tier.reductionPercent)

Tier-Based Jerk Reduction:
  tier = findJerkTierForMultiplier(cargoMultiplier)
  newJerk = originalJerk × (1.0 - tier.reductionPercent)

Dampened Inertia Increase:
  massIncrease = massRatio - 1.0
  dampedIncrease = massIncrease × impactFactor
  newInertia = originalInertia × (1.0 + dampedIncrease)

Proportional Acceleration Scaling:
  newAccel = originalAccel × massRatio × responsiveness
  Maintains AccelFactor/Mass ratio (physics-correct)
```

### C. Configuration Template

```json
{
  "cargo-multipliers": [2, 4, 6, 8, 10],
  "flight-mechanics": {
    "dragReductionTiers": [
      { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
      { "maxMultiplier": 4.0, "reductionPercent": 0.30 },
      { "maxMultiplier": 8.0, "reductionPercent": 0.50 },
      { "maxMultiplier": 999, "reductionPercent": 0.70 }
    ],
    "jerkReductionTiers": [
      { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
      { "maxMultiplier": 4.0, "reductionPercent": 0.15 },
      { "maxMultiplier": 8.0, "reductionPercent": 0.25 },
      { "maxMultiplier": 999, "reductionPercent": 0.35 }
    ],
    "inertiaImpactFactor": 0.5,
    "steeringIncreaseFactor": 1.0,
    "useEffectiveRatioCap": true,
    "accelerationResponsiveness": 1.0
  }
}
```

---

**End of Work Packages Document**
