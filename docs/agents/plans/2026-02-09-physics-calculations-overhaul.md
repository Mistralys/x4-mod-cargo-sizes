# Physics Calculations Overhaul

> **Date:** February 9, 2026  
> **Status:** Planned  
> **Priority:** Critical - Player-controlled ships cannot enter travel mode  
> **Approach:** Tier-based adjustments with safety caps

---

## Problem Statement

The current cargo size mod calculations are faulty, preventing player-controlled large cargo ships (e.g., Shuyaku Vanguard) from entering travel mode. When travel mode is enabled, the ship does not gain any speed. While NPC ships (which don't use full physics simulation) work fine, player-controlled ships are severely affected.

**Root Causes Identified:**
1. **Backwards jerk calculations** - Jerk values are INCREASED when mass increases (physics says they should DECREASE)
2. **Conservative drag reduction** - Not aggressive enough to compensate for fixed engine thrust (current: 10% at 10x cargo)
3. **Formula-based approach flawed** - Mass ratio varies wildly by ship type, causing extreme adjustments for cargo-heavy ships
4. **Travel mode extra penalty** - Arbitrary 2x penalty on travel jerk has no physics basis
5. **Engine thrust not adjusted** - Ships get heavier but engines produce same thrust

**Critical Discovery:**
- Combat ships (low cargo): 10x cargo = ~10% mass increase
- Cargo ships (Magnetar, Mercury): 10x cargo = ~900% mass increase!
- Formula-based adjustments (squared mode) would reduce Magnetar's drag to **1%** at 10x cargo → undriveable
- Current code reduces drag to 10% at 10x → still too low for travel mode
- **Solution**: Tier-based system where all ships with same cargo multiplier get same adjustment (safe, predictable, tunable)

**Mass Ratio Analysis Results** (10x cargo example):
| Ship | Base Mass | Cargo | Mass Ratio | Current Drag | Squared Mode | Tier-Based |
|------|-----------|-------|------------|--------------|--------------|------------|
| Nova Fighter | 6 kg | 240 | 9.78x | 10.2% | **1.0%** 🔴 | 30% ✅ |
| Mercury Transport | 43 kg | 8,200 | 9.95x | 10.0% | **1.0%** 🔴 | 30% ✅ |  
| Magnetar Miner | 205 kg | 42,000 | 9.96x | 10.0% | **1.0%** 🔴 | 30% ✅ |
| Shuyaku Freighter | 650 kg | 37,000 | 9.85x | 10.2% | **1.0%** 🔴 | 30% ✅ |

(Full analysis: [docs/agents/plans/mass-ratio-analysis-summary.md](docs/agents/plans/mass-ratio-analysis-summary.md))

---

## Technical Constraints Discovered

### X4 Modding Architecture Limitations

**What Can Be Modified Per-Ship:**
- ✅ Mass (ship property)
- ✅ Drag (7 components: forward, reverse, horizontal, vertical, pitch, yaw, roll)
- ✅ Inertia (3 components: pitch, yaw, roll)
- ✅ Jerk (strafe, angular, forward, boost, travel)
- ✅ Acceleration factors (4 components)
- ✅ Steering curve (multiple position/value pairs)
- ✅ Cargo capacity (via storage macro)

**What Cannot Be Modified Per-Ship:**
- ❌ Engine thrust (forward, reverse)
- ❌ Engine boost thrust
- ❌ Engine travel thrust
- ❌ Engine charge times

**Why:** Engines are separate macros shared across all ships. Overriding an engine's thrust would affect **every ship in the game** using that engine, not just cargo-modded ships. Additionally, players can choose different engines (Split, Terran, Argon, etc.) with varying performance characteristics.

### Implication

Since engine thrust cannot be adjusted per-ship and players choose engines dynamically, the fix **must aggressively optimize ship-level characteristics** (drag, jerk, inertia) to compensate for increased mass with fixed engine thrust.

---

## Solution: Tier-Based Ship-Level Physics Adjustments

### Core Philosophy - **RESEARCH-VALIDATED** ✅

1. **Fix backwards physics** - Jerk should DECREASE with mass, not increase
2. **Tier-based drag reductions** - Use cargo multiplier (2x, 4x, 10x) to ensure uniform behavior
3. **Scale acceleration factors proportionally** - Maintains `AccelFactor/Mass` ratio for responsiveness
4. **Safety-capped adjustments** - Maximum 70% drag reduction to prevent instability
5. **Full configurability** - Let users tune tier thresholds and reduction percentages
6. **Ship-level only** - No global engine overrides (avoids side effects)
7. **Maintain pilotability** - All ships with same cargo multiplier behave consistently

### Physics Model Confirmed by Research

**Top Speed** (determined by Thrust and Drag):
```
v_max = Thrust / DragCoefficient
```
- Thrust is FIXED (engine property, cannot modify per-ship)
- To maintain top speed with heavier ship: REDUCE drag
- This is why drag reduction tiers are correct ✅

**Responsiveness** (determined by Acceleration Factor and Mass):
```
Δv ∝ (AccelerationFactor / Mass)
```
- When mass increases 4x, responsiveness becomes 1/4
- To maintain responsiveness: SCALE acceleration factor by 4x
- This maintains the ratio: `(4×AccelFactor) / (4×Mass) = original ratio` ✅

**Why Original Plan Was Partially Right:**
- Drag reduction IS correct (compensates for fixed thrust)
- But acceleration factors need proportional scaling (not formula-based reduction)

**Why Physics Audit Was Partially Right:**
- Identified that `F/m` ratio is critical
- Correct that we need to scale something proportionally
- But wrong about scaling drag UP (would decrease top speed)

### KEY CHANGE: Tier-Based System vs Formula-Based

**Problem with formula-based approach:**
- Mass ratio varies wildly: Combat ship with 10x cargo = 10% heavier, Magnetar with 10x cargo = 2000% heavier
- Pure physics formulas would make Magnetar's drag → 1% (undriveable)
- Current code: drag → 10% (still too low for travel mode)
- Squared mode: drag → 1% (even worse!)

**Solution: Tier-based adjustments:**
```
2x cargo:  10% drag reduction  → All ships get same adjustment
4x cargo:  30% drag reduction  → Predictable, testable
8x cargo:  50% drag reduction  → Independent tuning per tier  
10x cargo: 70% drag reduction  → SAFETY CAP prevents extremes
```

Benefits:
- ✅ **Uniform behavior** - All ships with 10x cargo behave the same
- ✅ **Safety caps** - Can't accidentally create 99% drag reduction
- ✅ **Independently tunable** - Test and adjust each tier based on gameplay
- ✅ **Simple configuration** - Users can understand and modify
- ✅ **Prevents edge cases** - Magnetar and Fighter get same treatment

---

## Implementation Steps

**IMPORTANT - Configuration Documentation:** This plan includes comprehensive user-facing configuration documentation as a critical deliverable. Step 11.3 creates a complete Physics Tuning Guide ([docs/physics-tuning-guide.md](docs/physics-tuning-guide.md)) that explains:
- What each configuration parameter does and why it exists
- How tier-based systems work (drag reduction tiers, jerk reduction tiers)
- How to adjust values for different scenarios (travel mode issues, ships too sluggish, ships too responsive)
- Testing workflow with before/after comparisons
- Common tuning scenarios with complete JSON examples
- Value ranges and safety limits
- FAQ section answering user questions

This documentation is REQUIRED for users to understand and tune the tier-based system to their gameplay preferences. Without it, users won't know how to adjust the mod when ships don't behave as expected.

---

### Quick Reference: New Tier-Based Configuration

**File:** [config/build-config.json](config/build-config.json)

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

**How It Works:**
- Ship with 4x cargo → finds tier with `maxMultiplier: 4.0` → applies `reductionPercent: 0.30`
- Drag: `newDrag = originalDrag * (1 - 0.30) = originalDrag * 0.70` (70% remains)
- Jerk: `newJerk = originalJerk * (1 - 0.15) = originalJerk * 0.85` (85% remains)
- **ALL ships with 4x cargo get the same adjustment** regardless of their base cargo/mass ratio

---

### 1. Create Physics Calculator Foundation

**File:** [src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php](src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php)

**Purpose:** Replace confusing "multiplier" terminology with clear physics calculations.

**Calculations:**
```php
// Mass values
baseMass = ship mass without cargo
originalFullMass = baseMass + originalCargo
adjustedFullMass = baseMass + adjustedCargo

// Physics ratios
massRatio = adjustedFullMass / originalFullMass          // > 1.0 when cargo increases
cargoMultiplier = adjustedCargo / originalCargo          // User's chosen multiplier (2x, 4x, 10x)
effectiveRatio = min(massRatio, cargoMultiplier)         // Cap extreme cargo-heavy ships

// For tier lookups
cargoMultiplier is used to find appropriate tier
```

**Methods:**
- `getMassRatio(): float` - How much heavier the ship becomes (e.g., 1.22 for 22% increase)
- `getCargoMultiplier(): float` - User's chosen multiplier (2.0, 4.0, 10.0)
- `getEffectiveRatio(): float` - Capped ratio to prevent extremes
- `getBaseMass(): float` - Ship mass without cargo
- `getOriginalFullMass(): float` - Ship + original cargo
- `getAdjustedFullMass(): float` - Ship + adjusted cargo
- `getMassIncrease(): float` - Absolute mass difference

**Validation:**
- All ratios must be > 0
- massRatio should typically be 1.0-10.0 (larger values flagged as extreme)

---

### 2. Fix Backwards Jerk Calculations

**Problem:** Current code INCREASES jerk when mass increases (opposite of physics).

**Physics:** Heavier objects have lower jerk (slower rate of acceleration change).

#### 2.1 Update AdjustedJerk.php

**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php](src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php)

**Current (Wrong):**
```php
$value = $original * (1 + $multiplier);  // INCREASES jerk
```

**New (Correct):**
```php
$jerkFactor = $config->getJerkCompensationFactor();  // Default 1.0
$value = $original / ($massRatio * $jerkFactor);
// Example: 2x mass with factor 1.0 → jerk becomes 50% of original
```

**Apply to:**
- Strafe jerk
- Angular jerk

#### 2.2 Update AdjustedJerkForward.php

**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php](src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php)

Same formula:
```php
$newAccel = $originalAccel / ($massRatio * $jerkFactor);
$newDecel = $originalDecel / ($massRatio * $jerkFactor);
```

#### 2.3 Update AdjustedJerkBoost.php

**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php](src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php)

Same formula:
```php
$newBoostAccel = $originalBoostAccel / ($massRatio * $jerkFactor);
```

#### 2.4 Update AdjustedJerkTravel.php (CRITICAL)

**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php](src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php)

**Current (Wrong):**
```php
$value = $original * (1 + $multiplier * 2);  // INCREASES + arbitrary 2x penalty
```

**New (Correct):**
```php
$travelJerkFactor = $config->getTravelJerkCompensationFactor();  // Default 1.0
$newAccel = $originalAccel / ($massRatio * $travelJerkFactor);
$newDecel = $originalDecel / ($massRatio * $travelJerkFactor);
// Example: 2x mass with factor 1.0 → travel jerk becomes 50% of original
// No arbitrary 2x penalty - let physics handle it
```

**Why This Fixes Travel Mode:**
- Current code makes travel jerk WORSE (higher values)
- New code makes travel jerk BETTER (lower values, smoother acceleration)
- Combined with aggressive drag reduction, ship can reach travel speeds

---

### 3. Implement Aggressive Drag Reduction

**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php](src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php)

**Problem:** Current linear drag reduction is too conservative - doesn't compensate enough for fixed engine thrust.

**Current (Conservative):**
```php
dragMultiplier = massMultiplier * factor;  // < 1.0
newDrag = originalDrag * (1 - dragMultiplier);
// Example: 2x mass → drag reduced to ~75% (25% reduction)
```

**New (Aggressive, Configurable):**
```php
$mode = $config->getDragReductionMode();  // 'linear' or 'squared'
$factor = $config->getDragReductionFactor();  // Default 1.0

if ($mode === 'squared') {
    // Aggressive: squared ratio for non-linear compensation
    $dragMultiplier = 1.0 / $massRatioSquared;
    // Example: 2x mass (ratio 2.0) → squared = 4.0 → 1/4 = 0.25 (drag becomes 25%)
} else {
    // Linear: conservative compensation
    $dragMultiplier = 1.0 / $massRatio;
    // Example: 2x mass → 1/2 = 0.5 (drag becomes 50%)
}

$newDrag = $originalDrag * $dragMultiplier * $factor;
```

**Why Squared Mode:**
- Travel mode requires significant thrust to overcome drag at high speeds
- Fixed engine thrust means we must drastically reduce drag to maintain performance
- Squared mode: 2x mass → 75% drag reduction (vs 50% in linear mode)
- More aggressive for extreme cargo (10x mass → 99% drag reduction)

**Apply to all 7 drag components:**
- Forward
- Reverse
- Horizontal
- Vertical
- Pitch
- Yaw
- Roll

---

### 4. Revise Inertia Adjustments

**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php](src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php)

**Current (Backwards):**
```php
$value = $original * (1 + $massMultiplier);  // Slight increase
```

**New (Physics-Correct):**
```php
$inertiaFactor = $config->getInertiaIncreaseFactor();  // Default 1.0
$newInertia = $originalInertia * $massRatio * $inertiaFactor;
// Example: 2x mass with factor 1.0 → inertia becomes 2x (doubles)
```

**Physics:** Inertia (rotational resistance) scales with mass. Heavier ships turn more slowly.

**Configurability:**
- `inertiaFactor = 1.0` - Full physics (2x mass → 2x inertia)
- `inertiaFactor = 0.5` - Half impact (2x mass → 1.5x inertia, more responsive)
- `inertiaFactor = 1.5` - Amplified (2x mass → 3x inertia, more sluggish)

**Apply to all 3 inertia components:**
- Pitch
- Yaw
- Roll

---

### 5. Fix Acceleration Factor Logic - **RESEARCH CONFIRMED** ✅

**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php](src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php)

**Current:**
```php
$value = $original * (1 + $massMultiplier);  // Incorrect formula
```

**RESEARCH FINDINGS - Acceleration Factors Explained:**

Acceleration factors are **rate-of-change scalars** (responsiveness), NOT thrust multipliers:

- **Thrust** determines **top speed** (fighting drag): `v_max = Thrust / DragCoefficient`
- **Acceleration Factor** determines **time-to-speed** (how quickly you reach top speed)

**The Physics Formula:**
```
Δv ∝ (AccelerationFactor / Mass) × BaseForce
```

**Critical Insight:**
- When mass increases 4x, the `AccelFactor/Mass` ratio becomes 1/4
- Ship takes **4x longer** to reach top speed (even if top speed is maintained)
- For travel mode: Controls the "slope" of the acceleration ramp-up
- Low factor with high mass = feels like freight train trying to move

**New Implementation (Correct):**
```php
// Acceleration factors must scale WITH mass to maintain responsiveness
// Formula: Δv ∝ (AccelFactor / Mass)
// To maintain constant Δv: newAccelFactor = oldAccelFactor * massRatio

$responsivenessFactor = $config->getAccelerationResponsiveness();  // Default 1.0

// Scale acceleration factor proportionally to mass to maintain time-to-speed
$newValue = $originalValue * $massRatio * $responsivenessFactor;

// Example: 4x mass with factor 1.0
//   newAccel = oldAccel * 4.0
//   Result: (4×AccelFactor) / (4×Mass) = AccelFactor/Mass (maintained!)
```

**Why This Works:**
- Maintains the `AccelFactor/Mass` ratio → preserves responsiveness
- Ship still reaches its new top speed in same time as vanilla
- Travel mode acceleration ramp works correctly
- Configurable factor allows tuning "snappiness"

**Configuration Options:**
- `responsivenessFactor = 1.0` → Full compensation (vanilla-like response)
- `responsivenessFactor = 0.7` → 70% compensation (feels heavier, more realistic)
- `responsivenessFactor = 1.2` → Extra responsive (lighter feel than physics dictates)

**Apply to all 4 components:**
- Forward
- Reverse
- Horizontal
- Vertical

**XML Comments:** Explain responsiveness scaling for user understanding.

---

### 6. Update Configuration Schema

**File:** [config/build-config.json](config/build-config.json)

**Current:**
```json
{
  "cargo-multipliers": [2, 4, 6, 8, 10],
  "flight-mechanics": {
    "dragReductionFactor": 1,
    "steeringIncreaseFactor": 1,
    "inertiaIncreaseFactor": 1
  }
}
```

**New (Tier-Based System):**
```json
{
  "cargo-multipliers": [2, 4, 6, 8, 10],
  "flight-mechanics": {
    "dragReductionTiers": [
      { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
    ccelerationResponsiveness` | float | `1.0` | Controls how much acceleration factors scale with mass (1.0 = full compensation for responsiveness, <1.0 = heavier feel)
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
    "adjustAccelerationFactors": true,
    "accelerationFactorAdjustment": 1.0
  }
}
```

**New Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `dragReductionTiers` | array | See above | Tier-based drag reductions by cargo multiplier. Each tier has `maxMultiplier` (upper bound) and `reductionPercent` (0.0-1.0 = percentage to reduce) |
| `jerkReductionTiers` | array | See above | Tier-based jerk reductions by cargo multiplier. Separate from drag for independent tuning |
| `inertiaImpactFactor` | float | `0.5` | Dampening factor for inertia increase (0.5 = half the physics impact, 1.0 = full impact) |
| `steeringIncreaseFactor` | float | `1.0` | Multiplier for steering curve adjustment |
| `useEffectiveRatioCap` | bool | `true` | Cap mass ratio at cargo multiplier to prevent extreme cargo-heavy ships from getting excessive adjustments |
| `adjustAccelerationFactors` | bool | `true` | Enable/disable acceleration factor adjustments |
| `accelerationFactorAdjustment` | float | `1.0` | Multiplier for acceleration factor changes |

**File:** [src/Mods/CargoSizesMod/Build/BuildConfiguration.php](src/Mods/CargoSizesMod/Build/BuildConfiguration.php)

**Add Methods:**
```php
public function getDragReductionTiers(): array  // Returns ReductionTier[]
public function getJerkReductionTiers(): array  // Returns ReductionTier[]
public function getInertiaImpactFactor(): float
public function getSteeringIncreaseFactor(): float
public function getUseEffectiveRatioCap(): bool
public function getAdjustAccelerationFactors(): bool
public function getAccelerationResponsiveness $multiplier): ReductionTier
public function findJerkTierForMultiplier(float $multiplier): ReductionTier
```

**Add Value Object:**
```php
class ReductionTier
{
    public function __construct(
        private float $maxMultiplier,
        private float $reductionPercent
    ) {}
    
    public function getMaxMultiplier(): float
    public function getReductionPercent(): float
    public function appliesToMultiplier(float $multiplier): bool
}
```

**Add Validation:**
- Tier arrays must have at least one entry
- `maxMultiplier` must be positive, ascending order
- `reductionPercent` must be 0.0-1.0 (percentage as decimal)
- `inertiaImpactFactor` must be 0.0-2.0
- All other factors must be 0.1-5.0
- Boolean flags validated as bool

---

### 7. Enhance XML Comments for Diagnostics

**File:** [FlightMechanicsOverrideFile.php](src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php)

**Purpose:** Generate comprehensive XML comments explaining all calculations for debugging and understanding.

**Example Output:**
```xml
<?xml version="1.0" encoding="utf-8"?>
<diff>
  <!-- ===================================================================== -->
  <!-- CARGO SIZE MOD: Physics Adjustments -->
  <!-- Ship: Shuyaku Vanguard (XL Transport) -->
  <!-- Cargo multiplier: 4.0x -->
  <!-- ===================================================================== -->
  
  <!-- MASS CALCULATIONS -->
  <!-- Base mass: 12500 kg -->
  <!-- Original cargo: 1000 | New cargo: 4000 -->
  <!-- Original full mass: 13500 kg | New full mass: 16500 kg -->
  <!-- Mass ratio: 1.22x (ship is 22% heavier) -->
  
  <replace sel="//macros/macro[@name='ship_arg_xl_carrier_01_a_macro']/properties/physics">
    <physics mass="16500">
      
<!-- DRAG: Tier-based reduction (4x cargo tier) -->
  <!-- Mass ratio: 1.22 (22% heavier) | Cargo multiplier: 4.0x -->
  <!-- Tier: 4x → 30% reduction (safety-capped) -->
  <!-- Original drag: forward=17.9, result: 12.5 (70% remains) -->
  <!-- Purpose: Compensate for fixed engine thrust without extreme physics -->
      <drag forward="12.5" reverse="18.7" horizontal="23.4" vertical="35.1"
            pitch="10.5" yaw="10.5" roll="7.8"/>
      
<!-- INERTIA: Increased with dampening -->
  <!-- Formula: originalInertia * (1 + (effectiveRatio - 1) * dampFactor) -->
  <!-- Effective ratio: 1.22 | Damp factor: 0.5 | Impact: 11% increase -->
  <!-- Physics: Heavier ships turn more slowly (dampened for playability) -->
      <inertia pitch="5.5" yaw="5.5" roll="2.2"/>
      
      <!-- ACCELERATION FACTORS: Adjusted for heavier ship -->
      <!-- Formula: originalAccelFactor * massRatio * adjustmentFactor -->
      <!-- Purpose: Compensate acceleration for increased mass -->
      <accfactors forward="2.44" reverse="1.83" horizontal="3.05" vertical="4.58"/>
    </physics>
  </replace>
  
  <replace sel="//macros/macro[@name='ship_arg_xl_carrier_01_a_macro']/properties/jerk">
<!-- JERK: Tier-based reduction (4x cargo tier) -->
  <!-- Formula: originalJerk * (1 - reductionPercent) -->
  <!-- Cargo multiplier: 4.0x → Tier: 15% reduction -->
  <!-- Original jerk: 5.8, result: 4.9 (85% remains) -->
    <!-- Physics: Heavier ships have slower acceleration changes -->
    
    <jerk strafe="4.9" angular="7.3">
      <!-- FORWARD JERK: General movement -->
      <forward accel="3.3" decel="4.9"/>
      
      <!-- BOOST JERK: Boost mode responsiveness -->
      <forward_boost accel="6.5"/>
      
      <!-- TRAVEL JERK: Critical for travel mode functionality -->
      <!-- Original: accel=0.41, decel=0.82 -->
      <!-- Adjusted: accel=0.34, decel=0.67 (82% of original) -->
      <!-- Note: Travel speed still limited by engine thrust (player-chosen engine) -->
      <!-- Fix: Removed arbitrary 2x penalty from original calculations -->
      <forward_travel accel="0.34" decel="0.67"/>
    </jerk>
  </replace>
  
  <!-- END CARGO SIZE MOD -->
</diff>
```

**Benefits:**
- Users can understand what changed
- Developers can verify formulas
- In-game testing provides context
- Configuration tuning is informed

---

### 8. Refactor MassAdjustment Class

**File:** [src/Mods/CargoSizesMod/Output/MassAdjustment.php](src/Mods/CargoSizesMod/Output/MassAdjustment.php)

**Current Issues:**
- Method `getMultiplier()` returns confusing value (originalMass / adjustedMass < 1.0)
- Backwards terminology leads to backwards calculations

**Refactoring:**

**Remove:**
```php
public function getMultiplier(): float
{
    return $this->getOriginalFullLoadMass() / $this->getAdjustedFullLoadMass();
}
```

**Add:**
```php
public function getMassRatio(): float
{
    return $this->getAdjustedFullLoadMass() / $this->getOriginalFullLoadMass();
}

public function getInverseMassRatio(): float
{
    return 1.0 / $this->getMassRatio();
}

public function getMassRatioSquared(): float
{
    $ratio = $this->getMassRatio();
    return $ratio * $ratio;
}

public function getMassIncrease(): float
{
    return $this->getAdjustedFullLoadMass() - $this->getOriginalFullLoadMass();
}

public function getMassIncreasePercent(): float
{
    return ($this->getMassRatio() - 1.0) * 100.0;
}
```

**Update All References:**
- FlightMechanicsOverrideFile.php
- All Physics/* classes
- All Jerk/* classes
- Any other files using `getMultiplier()`

---

### 9. Add Diagnostic Logging

**File:** [src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php](src/Mods/CargoSizesMod/Output/DiagnosticsLogger.php)

**Purpose:** Generate human-readable physics diagnostics for testing and tuning.

**Methods:**
```php
public function logShip(ShipDef $ship, MassAdjustment $mass, BuildConfiguration $config): void
public function writeToFile(string $filePath): void
public function getWarnings(): array
```

**Output File:** [build/physics-diagnostics.txt](build/physics-diagnostics.txt)

**Example Output:**
```
================================================================================
CARGO SIZE MOD - Physics Diagnostics Report
Build Date: 2026-02-09 14:32:15
Configuration: Squared drag mode, all factors at 1.0
================================================================================

Ship: Shuyaku Vanguard (ship_arg_xl_carrier_01_a)
Class: XL Transport
-------------------------------------------------
Mass:
  Base mass: 12,500 kg
  Original cargo: 1,000 | New cargo: 4,000 (4.0x multiplier)
  Original full: 13,500 kg | New full: 16,500 kg
  Mass ratio: 1.22x (22% increase)

Physics Adjustments:
  Drag reduction: 33% (squared mode: 1/1.22² = 0.67)
  Jerk reduction: 18% (1/1.22 = 0.82)
  Inertia increase: 22% (1.22x)
  
Configuration Applied:
  dragReductionMode: squared
  dragReductionFactor: 1.0
  jerkCompensationFactor: 1.0
  travelJerkCompensationFactor: 1.0
  inertiaIncreaseFactor: 1.0

Status: ✓ OK

================================================================================

Ship: Magnetar (ship_ter_xl_miner_01_a)
Class: XL Miner
-------------------------------------------------
Mass:
  Base mass: 28,000 kg
  Original cargo: 15,000 | New cargo: 60,000 (4.0x multiplier)
  Original full: 43,000 kg | New full: 88,000 kg
  Mass ratio: 2.05x (105% increase)

Physics Adjustments:
  Drag reduction: 76% (squared mode: 1/2.05² = 0.24)
  Jerk reduction: 51% (1/2.05 = 0.49)
  Inertia increase: 105% (2.05x)
  
Configuration Applied:
  dragReductionMode: squared
  dragReductionFactor: 1.0
  jerkCompensationFactor: 1.0
  travelJerkCompensationFactor: 1.0
  inertiaIncreaseFactor: 1.0

Status: ⚠️ WARNING - High mass ratio (>2.0x), test carefully in-game

================================================================================

Ship: Tokyo (ship_tel_xl_resupplier_01_a)
Class: XL Resupplier
-------------------------------------------------
Mass:
  Base mass: 45,000 kg
  Original cargo: 50,000 | New cargo: 200,000 (4.0x multiplier)
  Original full: 95,000 kg | New full: 245,000 kg
  Mass ratio: 2.58x (158% increase)

Physics Adjustments:
  Drag reduction: 85% (squared mode: 1/2.58² = 0.15)
  Jerk reduction: 61% (1/2.58 = 0.39)
  Inertia increase: 158% (2.58x)
  
Configuration Applied:
  dragReductionMode: squared
  dragReductionFactor: 1.0
  jerkCompensationFactor: 1.0
  travelJerkCompensationFactor: 1.0
  inertiaIncreaseFactor: 1.0

Status: ⚠️ WARNING - High mass ratio (>2.5x), consider testing with upgraded engines

================================================================================

Summary:
  Total ships: 87
  Ships with mass ratio < 1.5x: 42 (low impact)
  Ships with mass ratio 1.5-2.0x: 31 (moderate impact)
  Ships with mass ratio 2.0-3.0x: 11 (high impact - test carefully)
  Ships with mass ratio > 3.0x: 3 (extreme impact - may require tuning)

Warnings: 14 ships flagged for careful testing

================================================================================
```

**Integration:**
- Call from build process after generating each ship's XML
- Collect data during build
- Write file at end of build
- Display summary in console

---

### 10. Update Tests

**Create:** [tests/CargoSizesModTests/PhysicsCalculatorTest.php](tests/CargoSizesModTests/PhysicsCalculatorTest.php)

```php
class PhysicsCalculatorTest extends TestCase
{
    public function testMassRatioIsGreaterThanOne(): void
    {
        // Test that mass ratio is > 1.0 when cargo increases
    }
    
    public function testInverseMassRatioCalculation(): void
    {
        // Test 1/ratio for jerk calculations
    }
    
    public function testSquaredMassRatioCalculation(): void
    {
        // Test ratio² for drag calculations
    }
    
    public function testPhysicsDirectionCorrectness(): void
    {
        // Verify jerk decreases, inertia increases, drag decreases
    }
    
    public function testExtremeMassMultipliers(): void
    {
        // Test 10x cargo scenarios
    }
}
```

**Create:** [tests/CargoSizesModTests/DragAdjustmentTest.php](tests/CargoSizesModTests/DragAdjustmentTest.php)

```php
class DragAdjustmentTest extends TestCase
{
    public function testLinearDragReduction(): void
    {
        // 2x mass → 0.5x drag
    }
    
    public function testSquaredDragReduction(): void
    {
        // 2x mass → 0.25x drag
    }
    
    public function testDragWithConfigurableFactor(): void
    {
        // Test factor 0.5, 1.0, 1.5
    }
    
    public function testDragNeverNegative(): void
    {
        // Ensure drag > 0 always
    }
}
```

**Create:** [tests/CargoSizesModTests/JerkAdjustmentTest.php](tests/CargoSizesModTests/JerkAdjustmentTest.php)

```php
class JerkAdjustmentTest extends TestCase
{
    public function testJerkDecreases(): void
    {
        // Verify jerk goes DOWN when mass increases
    }
    
    public function testTravelJerkNoExtraPenalty(): void
    {
        // Verify 2x penalty removed, uses same formula
    }
    
    public function testJerkWithConfigurableFactor(): void
    {
        // Test different compensation factors
    }
    
    public function testJerkNeverZero(): void
    {
        // Ensure jerk > 0 always
    }
}
```

**Create:** [tests/CargoSizesModTests/InertiaAdjustmentTest.php](tests/CargoSizesModTests/InertiaAdjustmentTest.php)

```php
class InertiaAdjustmentTest extends TestCase
{
    public function testInertiaIncreases(): void
    {
        // Verify inertia goes UP when mass increases
    }
    
    public function testInertiaProportionalToMass(): void
    {
        // 2x mass → 2x inertia (with factor 1.0)
    }
    
    public function testInertiaWithConfigurableFactor(): void
    {
        // Test different inertia factors
    }
}
```

---

### 11. Update Documentation

#### 11.1 Update Data Flows

**File:** [docs/agents/project-manifest/data-flows.md](docs/agents/project-manifest/data-flows.md)

**Add Section:** "Physics Calculation Flow - Ship-Level Only"

```markdown
### Physics Calculation Flow (Ship-Level Only)

Due to X4 architecture constraints, engines cannot be modified per-ship. The following flow shows ship-level adjustments only:

1. **Extract Ship Data**
   - Mass, drag (7 components), inertia (3 components), jerk, acceleration factors

2. **Extract Cargo Data**
   - Original capacity, adjusted capacity (multiplied)

3. **Calculate Mass Physics**
   - baseMass = ship mass
   - originalFullMass = baseMass + originalCargo
   - adjustedFullMass = baseMass + adjustedCargo
   - massRatio = adjustedFullMass / originalFullMass (> 1.0)

4. **Calculate Adjustments**
   - **Drag:** `newDrag = originalDrag / massRatio²` (squared mode)
   - **Jerk:** `newJerk = originalJerk / massRatio` (inverse)
   - **Inertia:** `newInertia = originalInertia * massRatio` (proportional)
   - **Accel Factors:** `newAccelFactor = originalAccelFactor * massRatio`

5. **Apply Configuration Factors**
   - All formulas multiplied by user-configurable factors
   - Allows tuning for specific flight characteristics

6. **Generate XML Overrides**
   - Replace physics section with adjusted values
   - Replace jerk section with adjusted values
   - Include comprehensive comments explaining calculations
```

#### 11.2 Update Main README

**File:** [README.md](README.md)

**Add Section:** "How It Works - Physics Calculations"

```markdown
## How It Works - Physics Calculations

### Ship-Level Adjustments Only

The mod adjusts **ship properties** to compensate for increased cargo mass. Engine properties (thrust, boost, travel) cannot be modified per-ship due to X4's architecture - engines are separate components that players can swap.

### What Gets Adjusted

1. **Mass** - Directly increased by cargo difference
2. **Drag** - Aggressively reduced (squared mode: 2x mass → 75% drag reduction)
3. **Jerk** - Decreased proportionally (heavier ships accelerate more slowly)
4. **Inertia** - Increased proportionally (heavier ships turn more slowly)
5. **Steering** - Adjusted to maintain responsiveness

### Physics Formulas

- **Mass Ratio:** `adjustedMass / originalMass` (e.g., 1.22 for 22% heavier)
- **Drag:** `originalDrag / massRatio²` (aggressive compensation)
- **Jerk:** `originalJerk / massRatio` (physics-correct reduction)
- **Inertia:** `originalInertia * massRatio` (proportional increase)

### Why Aggressive Drag Reduction?

Since engine thrust cannot be adjusted per-ship (and players choose different engines), the mod must drastically reduce drag to maintain performance. Squared mode provides non-linear compensation that works even with extreme cargo multipliers (10x).

### Travel Mode

Travel mode speed depends on **engine travel thrust** (not adjustable per-ship). The mod optimizes ship characteristics (drag, jerk) to allow ships to reach travel speeds with their equipped engines.

**Note:** Ships with minimal engine thrust may still struggle with very high cargo multipliers. Consider upgrading engines in-game.
```

#### 11.3 Create Physics Tuning Guide

**File:** [docs/physics-tuning-guide.md](docs/physics-tuning-guide.md)

```markdown
# Physics Tuning Guide

## Overview

This guide explains how the cargo size mod adjusts ship physics and how to tune the configuration for optimal flight characteristics.

## Understanding the System

### The Core Problem

**What happens when cargo increases:**
- Ship mass increases (base mass + cargo)
- Engine thrust stays the same (engines are player-chosen equipment)
- Result: Thrust-to-weight ratio decreases, ship becomes sluggish

**The solution:**
- Reduce drag to compensate for lower thrust-to-weight
- Reduce jerk (acceleration smoothness) to match new mass
- Increase inertia (rotational resistance) for heavier feel
- Keep ships pilotable even at extreme cargo multipliers

### Why Tier-Based?

Ships vary wildly in cargo-to-mass ratios:
- **Combat ships**: Small cargo (100-2000) vs heavy hull (100-600 mass) → Low impact
- **Cargo ships**: Massive cargo (15,000-50,000) vs light hull (200-650 mass) → **Extreme impact**

**Example - 10x cargo multiplier:**
- Fighter: Goes from 106 mass → 1006 mass (9.5x heavier)
- Magnetar: Goes from 205 mass → 42,205 mass (205x heavier!) 

Using physics formulas directly would make Magnetar have 99.99% drag reduction (undriveable). **Tier-based system treats all ships with 10x cargo the same** (predictable, safe).

---

## Configuration Parameters

### Drag Reduction Tiers

**What it does:** Reduces drag force to compensate for heavier ships with fixed engine thrust.

**Configuration:**
```json
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.30 },
  { "maxMultiplier": 8.0, "reductionPercent": 0.50 },
  { "maxMultiplier": 999, "reductionPercent": 0.70 }
]
```

**How to read:**
- Ships with 2x cargo or less: 10% drag reduction (90% drag remains)
- Ships with 4x cargo or less: 30% drag reduction (70% drag remains) 
- Ships with 8x cargo or less: 50% drag reduction (50% drag remains)
- Ships with more than 8x: 70% drag reduction (30% drag remains) **← SAFETY CAP**

**Effects:**
- **Higher reduction = faster acceleration, higher top speed**
- **Lower reduction = more sluggish, more realistic mass feel**
- Affects all movement: forward, reverse, strafe, rotation

**Tuning tips:**
```json
// Ships too sluggish? Increase reduction percentages
{ "maxMultiplier": 4.0, "reductionPercent": 0.40 }  // Was 0.30

// Ships too responsive (unrealistic)? Decrease reduction
{ "maxMultiplier": 4.0, "reductionPercent": 0.20 }  // Was 0.30

// Travel mode not working? Increase high-tier reduction
{ "maxMultiplier": 999, "reductionPercent": 0.80 }  // Was 0.70 (careful!)

// Add intermediate tier for 6x cargo
{ "maxMultiplier": 6.0, "reductionPercent": 0.40 }
```

**Warning:** Reductions above 0.85 (85%) may cause physics instability.

---

### Jerk Reduction Tiers

**What it does:** Reduces jerk (rate of acceleration change) to make heavier ships feel more massive.

**Configuration:**
```json
"jerkReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.15 },
  { "maxMultiplier": 8.0, "reductionPercent": 0.25 },
  { "maxMultiplier": 999, "reductionPercent": 0.35 }
]
```

**How to read:**
- Ships with 4x cargo: 15% jerk reduction (85% jerk remains)
- Lower jerk = slower acceleration **response**, not slower speed

**Effects:**
- **Higher reduction = smoother, more gradual acceleration/deceleration**
- **Lower reduction = snappier, more responsive controls**
- Affects travel mode entry/exit smoothness

**Tuning tips:**
```json
// Ships accelerate too slowly? Decrease jerk reduction
{ "maxMultiplier": 4.0, "reductionPercent": 0.10 }  // Was 0.15

// Want more mass "feel"? Increase jerk reduction  
{ "maxMultiplier": 4.0, "reductionPercent": 0.25 }  // Was 0.15

// Travel mode entry too abrupt? Use less travel jerk reduction
{ "maxMultiplier": 999, "reductionPercent": 0.20 }  // Was 0.35
```

**Note:** Jerk and drag work together - high drag reduction with low jerk reduction = very responsive ship.

---

### Inertia Impact Factor

**What it does:** Controls how much rotational resistance increases with mass.

**Configuration:**
```json
"inertiaImpactFactor": 0.5
```

**How it works:**
```
If ship becomes 2x heavier (mass ratio 2.0):
  Mass increase = 2.0 - 1.0 = 1.0 (100% increase)
  Inertia increase = 1.0 * 0.5 = 0.5 (50% increase)
  New inertia = original * 1.5 (150% of original)
```

**Effects:**
- **Higher factor = ship turns more slowly** (more realistic)
- **Lower factor = ship turns more easily** (more playable)
- `1.0` = full physics impact (2x mass → 2x inertia)
- `0.5` = half impact (2x mass → 1.5x inertia) **← DEFAULT**
- `0.0` = no impact (inertia unchanged, unrealistic)

**Tuning tips:**
```json
// Ship turns too slowly?
"inertiaImpactFactor": 0.3  // Was 0.5

// Want realistic heavy ship feel?
"inertiaImpactFactor": 0.8  // Was 0.5

// Keep turning identical to vanilla (not recommended)?
"inertiaImpactFactor": 0.0
```

---

### Steering Increase Factor

**What it does:** Adjusts steering curve to compensate for increased mass.

**Configuration:**
```json
"steeringIncreaseFactor": 1.0
```

**Effects:**
- `1.0` = full compensation (heavier ships get increased steering)
- `0.5` = half compensation (ships steer more sluggishly)
- `1.5` = extra compensation (ships steer better than they should)

**Tuning tips:**
Leave at `1.0` unless you have specific issues with ship maneuverability.

---

### Use Effective Ratio Cap

**What it does:** Prevents extreme cargo-heavy ships from getting excessive adjustments.

**Configuration:**
```json
"useEffectiveRatioCap": true
```

**How it works:**
```
Magnetar: base mass 205, cargo 42,000
  At 10x cargo: mass becomes 205 + 420,000 = 420,205 (2049x heavier!)
  
  With cap OFF: Would try to use this crazy ratio
  With cap ON: Uses min(2049, 10) = 10 (cargo multiplier)
```

**Effects:**
- `true` = all ships with same cargo multiplier behave similarly **← RECOMMENDED**
- `false` = cargo-heavy ships get extreme adjustments (may be undriveable)

**Tuning tips:**
Leave at `true` unless testing extreme physics scenarios.

---

### Acceleration Responsiveness - **RESEARCH CONFIRMED** ✅

**What it does:** Controls how "snappy" ships feel when accelerating/decelerating.

**The Physics:**
```
Δv ∝ (AccelerationFactor / Mass)
```

When mass increases, the `AccelFactor/Mass` ratio decreases, making ships feel sluggish (like freight trains). This parameter scales acceleration factors to compensate.

**Configuration:**
```json
"accelerationResponsiveness": 1.0
```

**How it works:**
- `1.0` = Full compensation (maintains vanilla responsiveness)
  - 4x mass → 4x acceleration factor → same `AccelFactor/Mass` ratio
- `0.7` = Partial compensation (70% of vanilla responsiveness, heavier feel)
  - 4x mass → 2.8x acceleration factor → ship feels 30% less responsive
- `1.2` = Over-compensation (extra snappy, lighter than physics dictates)

**Effects: - Three-Pronged Approach:**
```json
// 1. Increase drag reduction (improves top speed)
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.35 },  // Increased from 0.30
  { "maxMultiplier": 8.0, "reductionPercent": 0.60 },  // Increased from 0.50  
  { "maxMultiplier": 999, "reductionPercent": 0.80 }  // Increased from 0.70
],

// 2. Increase acceleration responsiveness (faster ramp-up)
"accelerationResponsiveness": 1.3,  // Was 1.0 (30% more responsive)

// 3. Reduce travel jerk reduction (smoother entry)
"jerkReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.12 },  // Decreased from 0.15
  { "maxMultiplier": 8.0, "reductionPercent": 0.20 },  // Decreased from 0.25
  { "maxMultiplier": 999, "reductionPercent": 0.25 }  // Decreased from 0.35
]
```

**Why This Works:**
- Higher drag reduction → ship CAN reach higher speeds (top speed increased)
- Higher responsiveness → ship GETS TO those speeds faster (acceleration improved)
- Lower jerk reduction → travel mode entry is smoother (less "freight train" feel)
**Note:** This is separate from top speed (controlled by drag reduction). You can have fast top speed but slow acceleration, or vice versa.

---

## Common Tuning Scenarios

### Scenario 1: Travel Mode Not Working

**Symptoms:** Ship enters travel mode but doesn't accelerate, or accelerates very slowly.

**Solution:**
```json
// Increase drag reduction for high-tier cargo
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.35 },  // Increased from 0.30
  { "maxMultiplier": 8.0, "reductionPercent": 0.60 },  // Increased from 0.50  
  { "maxMultiplier": 999, "reductionPercent": 0.80 }  // Increased from 0.70
]

// Also reduce travel jerk reduction (smoother entry)
"jerkReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.12 },  // Decreased from 0.15
  { "maxMultiplier": 8.0, "reductionPercent": 0.20 },  // Decreased from 0.25
  { "maxMultiplier": 999, "reductionPercent": 0.25 }  // Decreased from 0.35
]
```

### Scenario 2: Ships Too Responsive (Unrealistic)

**Symptoms:** 10x cargo ship flies like it's empty.

**Solution:**
```json
// Reduce drag compensation
{ "maxMultiplier": 999, "reductionPercent": 0.50 }  // Was 0.70

// Increase inertia impact
"inertiaImpactFactor": 0.8  // Was 0.5

// Increase jerk reduction (heavier feel)
{ "maxMultiplier": 999, "reductionPercent": 0.50 }  // Was 0.35
```PRIMARY FIX: Increase acceleration responsiveness
"accelerationResponsiveness": 1.3,  // Was 1.0 (30% more responsive)

// SECONDARY: Increase drag reduction (improves top speed)
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.20 },  // Was 0.10
  { "maxMultiplier": 4.0, "reductionPercent": 0.45 },  // Was 0.30
  { "maxMultiplier": 8.0, "reductionPercent": 0.65 },  // Was 0.50
  { "maxMultiplier": 999, "reductionPercent": 0.80 }  // Was 0.70
],

// TERTIARY: Reduce inertia impact (turns faster)
"inertiaImpactFactor": 0.3  // Was 0.5
```

**Why This Order:**
1. **Responsiveness** affects how quickly you reach ANY speed (most noticeable)
2. **Drag reduction** affects top speed ceiling
3. **Inertia** affects turning only "maxMultiplier": 2.0, "reductionPercent": 0.20 },  // Was 0.10
  { "maxMultiplier": 4.0, "reductionPercent": 0.45 },  // Was 0.30
  { "maxMultiplier": 8.0, "reductionPercent": 0.65 },  // Was 0.50
  { "maxMultiplier": 999, "reductionPercent": 0.80 }  // Was 0.70
]

// Reduce inertia impact
"inertiaImpactFactor": 0.3  // Was 0.5
```

### Scenario 4: Different Behavior by Ship Size

**Symptoms:** Small ships feel good, large ships struggle (or vice versa).

**Note:** Tier system treats all ships equally per cargo multiplier. To differentiate by ship class, you may need custom builds per ship type (advanced, not covered here).

**Workaround:** Tune for the ship class you use most, or create multiple build configurations.

---

## Testing Workflow

### Step 1: Baseline Test
1. Start with default configuration
2. Build mod: `composer build`
3. Install in game, test 2x and 4x cargo variants
4. Note which ships feel wrong

### Step 2: Identify Issues
- Travel mode broken? → Increase drag reduction
- Too responsive? → Decrease drag reduction, increase inertia/jerk
- Too sluggish? → Increase drag reduction, decrease inertia/jerk
- Turning bad? → Adjust inertia factor or steering factor

### Step 3: Incremental Tuning
1. Change ONE parameter at a time
2. Rebuild: `composer build`
3. Test in-game with same ship
4. Compare to previous build
5. Repeat until satisfied

### Step 4: Extreme Testing
1. Test 10x cargo on largest cargo ships (Magnetar, Tokyo)
2. Should be sluggish but still **flyable**
3. Travel mode must work (even if slow to enter)
4. If broken, increase high-tier drag reduction

### Step 5: Document Your Settings
Once you find settings you like, save them! The defaults are starting points, not gospel.

---

## Advanced: Custom Tier Structures

You can create any tier structure you want:

```json
// Conservative: Many small steps
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },
  { "maxMultiplier": 3.0, "reductionPercent": 0.20 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.30 },
  { "maxMultiplier": 5.0, "reductionPercent": 0.40 },
  { "maxMultiplier": 6.0, "reductionPercent": 0.50 },
  { "maxMultiplier": 8.0, "reductionPercent": 0.60 },
  { "maxMultiplier": 999, "reductionPercent": 0.70 }
]

// Aggressive: Only care about extremes
"dragReductionTiers": [
  { "maxMultiplier": 4.0, "reductionPercent": 0.20 },
  { "maxMultiplier": 999, "reductionPercent": 0.75 }
]

// Expoat about acceleration factors - don't they increase thrust?**  
A: No! Research confirms acceleration factors control **responsiveness** (time-to-speed), not top speed. Top speed is determined by the Thrust/Drag ratio. Acceleration factors must scale with mass to prevent ships feeling like "freight trains."

**Q: Why tier-based instead of formula-based?**  
A: Ships vary wildly in cargo-to-mass ratios. Formulas based on mass ratio would make cargo ships undriveable (99% drag reduction) while barely affecting combat ships. Tiers ensure consistent behavior.

**Q: What if I want pure realism?**  
A: Use low drag/jerk reductions, low responsiveness (0.7), and high inertia factor (0.8-1.0)
  { "maxMultiplier": 999, "reductionPercent": 0.85 }
]
```

---

## FAQ

**Q: Why can't the mod just increase engine thrust?**  
A: Engines are separate equipment that players swap. Modifying engine thrust would affect **every ship in the game** using that engine, not just cargo-modded ships.

**Q: Why tier-based instead of formula-based?**  
A: Ships vary wildly in cargo-to-mass ratios. Formulas based on mass ratio would make cargo ships undriveable (99% drag reduction) while barely affecting combat ships. Tiers ensure consistent behavior.

**Q: What if I want pure realism?**  
A: Use low drag/jerk reductions and high inertia factor. But be warned: large cargo multipliers will make ships nearly unflyable. X4 wasn't designed for 10x cargo.

**Q: Can I have different settings for different cargo multipliers?**  
A: Not directly, but you can use the tier system to approximate this. Each tier corresponds roughly to a cargo multiplier threshold.

**Q: My ship explodes/crashes/behaves weird. Help?**  
A: Likely drag or jerk reduction too extreme (>85%). Reduce to 0.70-0.75 max. Check build diagnostics log for warnings.

**Q: How do I know if my settings are working?**  
A: Test in-game! The mod generates XML comments showing exact values. You can also check `build/physics-diagnostics.txt` for calculated adjustments.

---

## Configuration Reference

### Full Default Configuration

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

### Value Ranges

| Parameter | Min | Max | Default | Notes |
|-----------|-----|-----|---------|-------|
| `reductionPercent` | 0.0 | 0.9 | Varies | Above 0.85 risky |
| `inertiaImpactFactor` | 0.0 | 2.0 | 0.5 | 1.0 = full physics |
| `steeringIncreaseFactor` | 0.1 | 5.0 | 1.0 | Rarely needs tuning |
| `accelerationFactorAdjustment` | 0.1 | 5.0 | 1.0 | Experimental |

---

**End of Physics Tuning Guide**
```

---

## Verification Plan

### 1. Build Test
```bash
composer build
```
- ✅ Build completes without errors
- ✅ XML files generated in build/ directory
- ✅ physics-diagnostics.txt created with ship data

### 2. Unit Tests
```bash
composer test
```
- ✅ All physics calculation tests pass
- ✅ Drag, jerk, inertia adjustments verified
- ✅ Edge cases handled (extreme multipliers)

### 3. Static Analysis
```bash
composer analyze
```
- ✅ No PHPStan errors
- ✅ Type safety verified

### 4. XML Inspection
- ✅ Open generated XML files
- ✅ Verify drag values decreased significantly (squared mode)
- ✅ Verify jerk values decreased (not increased)
- ✅ Verify inertia values increased proportionally
- ✅ Verify comprehensive comments present

### 5. In-Game Testing - Baseline (2x Cargo)

**Ship:** Shuyaku Vanguard (XL Transport)
**Multiplier:** 2x cargo
**Engine:** Best available (player choice)

**Tests:**
- ✅ Ship loads in game without errors
- ✅ Ship can accelerate normally
- ✅ Ship can turn (slightly sluggish but acceptable)
- ✅ Ship enters travel mode successfully
- ✅ Ship accelerates in travel mode to normal speeds
- ✅ Ship exits travel mode cleanly
- ✅ No physics glitches or stuck states

### 6. In-Game Testing - Moderate (4x Cargo)

**Ship:** Shuyaku Vanguard
**Multiplier:** 4x cargo

**Tests:**
- ✅ All baseline tests pass
- ✅ Noticeably more sluggish but still responsive
- ✅ Travel mode works correctly
- ✅ No excessive drift or instability

### 7. In-Game Testing - Extreme (10x Cargo)

**Ship:** Largest cargo ship available
**Multiplier:** 10x cargo

**Tests:**
- ✅ Ship remains pilotable (even if very sluggish)
- ✅ Travel mode still functions
- ✅ No game crashes or physics bugs
- ✅ Acceptable for players prioritizing cargo over agility

### 8. Configuration Testing

**Test 1:** Linear drag mode
```json
"dragReductionMode": "linear"
```
- ✅ Rebuild succeeds
- ✅ XML shows different drag values (less aggressive)
- ✅ In-game: Ships more sluggish but still functional

**Test 2:** Reduced drag factor
```json
"dragReductionFactor": 0.5
```
- ✅ Rebuild succeeds
- ✅ Ships more sluggish (as expected)

**Test 3:** Reduced inertia factor
```json
"inertiaIncreaseFactor": 0.7
```
- ✅ Rebuild succeeds
- ✅ Ships turn more easily

### 9. Diagnostics Review

Review [build/physics-diagnostics.txt](build/physics-diagnostics.txt):
- ✅ No unexpected warnings
- ✅ Mass ratios reasonable
- ✅ Extreme cases flagged appropriately
- ✅ Summary statistics look correct

---

## Success Criteria

### Critical (Must Pass)
- ✅ Shuyaku Vanguard with 2x cargo enters and flies in travel mode
- ✅ No backwards physics (jerk decreases, not increases)
- ✅ Build completes successfully
- ✅ All unit tests pass

### Important (Should Pass)
- ✅ Ships with 4x cargo remain highly pilotable
- ✅ Configuration changes work as expected
- ✅ Diagnostics file provides useful information
- ✅ XML comments explain calculations clearly

### Nice to Have
- ✅ Ships with 10x cargo still flyable (even if sluggish)
- ✅ Different drag modes provide noticeable differences
- ✅ Users can tune configurations to their preference

---

## Key Decisions Summary

| Decision | Rationale |
|----------|-----------|
| **Ship-level only** | Engines are shared macros; per-ship thrust not possible |
| **Tier-based adjustments** | Mass ratio varies wildly by ship; tiers ensure uniform behavior (prevents 99% drag reduction on cargo ships) |
| **Safety caps** | Max 70% drag reduction prevents physics anomalies and undriveable ships |
| **Jerk physics corrected** | Was backwards; heavier ships now have appropriately reduced jerk (slower accel changes) |  
| **Remove travel 2x penalty** | Arbitrary and harmful to travel mode |
| **Comprehensive config docs** | Users need detailed tuning guide to adjust for their gameplay preferences |
| **Fully configurable tiers** | Users can independently tune each cargo multiplier tier based on testing |
| **Extensive diagnostics** | Helps debugging and configuration tuning |
| **Backwards compatible** | Same file structure, better (safer) formulas |

---

## Implementation Timeline

**Estimated Effort:** 4-6 hours

1. **Phase 1 (1.5h):** Core physics classes and formula fixes
2. **Phase 2 (1h):** Configuration updates and validation
3. **Phase 3 (1h):** XML comment generation and diagnostics
4. **Phase 4 (1.5h):** Testing and documentation updates
5. **Phase 5 (0.5-1h):** In-game verification and tuning

---

## Risk Assessment

### Low Risk
- ✅ Formula changes well understood
- ✅ Configuration preserves existing structure
- ✅ Backwards compatible with existing builds

### Medium Risk
- ⚠️ Acceleration factors behavior unclear (mitigated by configurability)
- ⚠️ Extreme cargo multipliers may need per-ship tuning

### Mitigation
- Comprehensive testing before release
- Clear documentation for users
- Configuration allows reverting to conservative settings
- Diagnostics help identify problematic ships

---

**End of Plan**
