# Physics Tuning Guide

## Overview

This guide explains how the cargo size mod adjusts ship physics and how to tune the configuration for an optimal flight experience.

> **💡 Physics Tuning GUI Available**  
> An interactive web-based GUI is available for real-time physics tuning. Instead of manually editing JSON files, you can use the GUI to adjust parameters, select ships and engines, and see results instantly. See [gui/README.md](../gui/README.md) for setup instructions.  
> Run with: `composer gui:start` (Linux/Mac) or `composer gui:start-win` (Windows) from the project root.

## Understanding the System

### The Core Problem

**What happens when cargo increases:**
- Ship mass increases (base mass + cargo mass)
- Engine thrust stays the same (engines are player-chosen equipment)
- Result: The thrust-to-weight ratio decreases, so the ship accelerates more slowly

**The solution:**
- Scale the ship's acceleration factor proportionally to the mass ratio
- Ships maintain the same perceived responsiveness as their vanilla equivalents

### The Formula

```
massRatio = (baseMass + adjustedCargo) / (baseMass + originalCargo)
accelerationScalingFactor = massRatio × accelerationResponsiveness
newAccelerationFactor = originalAccelerationFactor × accelerationScalingFactor
```

**Example — 4x cargo on a ship with 50,000 base mass and 5,000 original cargo:**
```
massRatio = (50,000 + 20,000) / (50,000 + 5,000)
          = 70,000 / 55,000
          = 1.273

accelerationScalingFactor = 1.273 × 1.0  (at default responsiveness)
                          = 1.273

newAccel = originalAccel × 1.273
```

This ensures the `accelFactor / mass` ratio is preserved — giving the same perceived acceleration as vanilla.

---

## Configuration Parameter

### `accelerationResponsiveness`

**What it does:** Controls how closely acceleration scaling tracks the mass ratio.

**Configuration location:** `config/build-config.json`

```json
{
  "cargo-multipliers": [2, 4, 8, 10],
  "flight-mechanics": {
    "accelerationResponsiveness": 1.0
  }
}
```

#### How it works

| Value | Effect |
|-------|--------|
| `1.0` | Physics-correct. Scaling exactly matches the mass ratio. Preserves vanilla `accelFactor/mass` ratio. **← Default** |
| `< 1.0` | Under-compensation. Ships feel heavier and less responsive than vanilla-equivalent. |
| `> 1.0` | Over-compensation. Ships feel snappier and more responsive than physics dictates. |

**Valid range:** 0.1 – 5.0

#### Examples

```json
// Default behaviour (recommended starting point)
"accelerationResponsiveness": 1.0

// Ships feel ~20% less responsive (heavier freight-train feel)
"accelerationResponsiveness": 0.8

// Ships feel snappier (good for small cargo-haulers)
"accelerationResponsiveness": 1.2
```

---

## Common Tuning Scenarios

### Scenario 1: Ships Feel Too Sluggish

**Symptoms:** Ship feels like a freight train. Acceleration is noticeably slower than vanilla.

**Solution:**
```json
"accelerationResponsiveness": 1.2   // 20% more responsive than physics-correct
```

**Why this works:**  
Higher responsiveness → stronger acceleration scaling → better `accelFactor/mass` ratio.

**Recommended range:** 1.1 – 1.5

---

### Scenario 2: Ships Feel Too Responsive (Unrealistic)

**Symptoms:** A 10x cargo ship handles like it's empty.

**Solution:**
```json
"accelerationResponsiveness": 0.8   // Physics-heavier, more sluggish feel
```

**Note:** Values below 0.5 will make ships at very high multipliers extremely sluggish or nearly unresponsive.

---

### Scenario 3: Balanced Vanilla-Equivalent Feel

**Symptoms:** You want ships to feel as close as possible to their unmodded equivalents despite the cargo increase.

**Solution:**
```json
"accelerationResponsiveness": 1.0   // The default — this is the recommended baseline
```

At `1.0`, the formula precisely compensates for the mass increase. The perceived thrust-to-weight ratio stays constant across all cargo multipliers.

---

### Scenario 4: Fine-Tuning by Multiplier

Because `accelerationResponsiveness` is a single global parameter, it applies uniformly across all cargo multipliers. If you need different feel for different multipliers, consider separate build configurations:

```bash
# Copy the main config to create a high-multiplier variant
cp config/build-config.json config/custom-builds/high-multiplier.json
# Then edit accelerationResponsiveness to 0.9 in that file
```

See the `config/custom-builds/` directory for existing custom build examples.

---

## Testing Workflow

### Step 1: Start with the Default

```bash
composer build
```

Install the generated mod and test in-game with your most commonly used ship and cargo multiplier combination.

### Step 2: Identify Issues

- Ship accelerates too slowly? → Increase towards 1.2 – 1.5
- Ship feels unrealistically snappy? → Decrease towards 0.7 – 0.9
- Ship feels correct? → You are done!

### Step 3: Incremental Adjustments

Change in steps of 0.1 or 0.05:

```json
// First try
"accelerationResponsiveness": 1.1

// Still sluggish?
"accelerationResponsiveness": 1.2
```

Rebuild and re-test after every change:

```bash
composer build
```

### Step 4: Use the Physics Tuning GUI

The GUI lets you test parameter changes in real time without rebuilding:

```bash
# Windows
composer gui:start-win

# Linux/Mac
composer gui:start
```

Open `http://localhost:5173`, select a ship, choose a cargo multiplier, and adjust **Acceleration Responsiveness** with the slider to see results instantly.

---

## FAQ

**Q: Why is there only one parameter now?**  
A: The previous version used a tier-based system with drag tiers, jerk tiers, and an inertia impact factor — many interacting parameters that were difficult to predict and tune. Research confirmed that scaling only the acceleration factor is sufficient to preserve ship feel and produces consistent, predictable results across all ship types and cargo multipliers.

**Q: What about top speed?**  
A: Top speed is determined by the engine thrust-to-drag ratio. Since the mod does not modify drag or engine thrust, top speed changes proportionally with mass (heavier ship → slightly lower top speed at the same engine). This is physically correct and intentional.

**Q: Can I have different settings per ship type?**  
A: Not through the main config, but you can create custom build configurations in `config/custom-builds/`.

**Q: What does the acceleration scaling factor mean in the GUI?**  
A: The displayed `accelerationScalingFactor` equals `massRatio × accelerationResponsiveness`. It is the multiplier applied to the original acceleration factor. At `1.0` responsiveness, this equals the mass ratio exactly.

**Q: My ship still feels wrong after adjusting. What next?**  
A: Use the Physics Tuning GUI to test live with the exact ship and engine you use in-game. It shows the calculated acceleration scaling factor and engine performance metrics in real time, which helps diagnose unusual mass ratios.

---

## Configuration Reference

### Full Configuration Schema

```json
{
  "cargo-multipliers": [2, 4, 8, 10],
  "flight-mechanics": {
    "accelerationResponsiveness": 1.0
  }
}
```

### Value Ranges

| Parameter | Min | Max | Default | Notes |
|-----------|-----|-----|---------|-------|
| `accelerationResponsiveness` | 0.1 | 5.0 | 1.0 | 1.0 preserves vanilla `accelFactor/mass` ratio |

---

**End of Physics Tuning Guide**

