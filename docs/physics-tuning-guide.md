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

### Acceleration Responsiveness

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

**Effects:**
- **Higher value = ships feel snappier** (reach speed faster)
- **Lower value = ships feel heavier** (slower to reach speed)
- `1.0` = vanilla responsiveness maintained **← DEFAULT**

**Note:** This is separate from top speed (controlled by drag reduction). You can have fast top speed but slow acceleration, or vice versa.

---

## Common Tuning Scenarios

### Scenario 1: Travel Mode Not Working

**Symptoms:** Ship enters travel mode but doesn't accelerate, or accelerates very slowly.

**Solution - Three-Pronged Approach:**
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

---

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
```

---

### Scenario 3: Ships Too Sluggish

**Symptoms:** Ship feels like a freight train - slow to accelerate, slow to stop.

**Solution Priority:**
```json
// PRIMARY FIX: Increase acceleration responsiveness
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
3. **Inertia** affects turning only

---

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

// Exponential: Rapidly increasing adjustments
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.05 },
  { "maxMultiplier": 4.0, "reductionPercent": 0.20 },
  { "maxMultiplier": 6.0, "reductionPercent": 0.45 },
  { "maxMultiplier": 999, "reductionPercent": 0.85 }
]
```

---

## FAQ

**Q: Why can't the mod just increase engine thrust?**  
A: Engines are separate equipment that players swap. Modifying engine thrust would affect **every ship in the game** using that engine, not just cargo-modded ships.

**Q: What about acceleration factors - don't they increase thrust?**  
A: No! Research confirms acceleration factors control **responsiveness** (time-to-speed), not top speed. Top speed is determined by the Thrust/Drag ratio. Acceleration factors must scale with mass to prevent ships feeling like "freight trains."

**Q: Why tier-based instead of formula-based?**  
A: Ships vary wildly in cargo-to-mass ratios. Formulas based on mass ratio would make cargo ships undriveable (99% drag reduction) while barely affecting combat ships. Tiers ensure consistent behavior.

**Q: What if I want pure realism?**  
A: Use low drag/jerk reductions, low responsiveness (0.7), and high inertia factor (0.8-1.0). But be warned: large cargo multipliers will make ships nearly unflyable. X4 wasn't designed for 10x cargo.

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
| `accelerationResponsiveness` | 0.1 | 5.0 | 1.0 | 1.0 = vanilla feel |

---

**End of Physics Tuning Guide**
