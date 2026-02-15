# Mass Ratio Analysis - Real X4 Ships
## Concrete Examples of Cargo Multiplier Impact

> **Generated:** February 9, 2026  
> **Status:** Analysis Only - No Solutions Proposed  
> **Data Source:** Extracted X4 game files

---

## Executive Summary

This analysis examines **5 real X4 ships** across different size classes and roles to understand the **actual impact** of cargo multipliers on mass ratios and drag adjustments. The findings reveal **critical issues** with both the current implementation and the proposed squared mode.

### Critical Finding

**ALL ships with 4x+ cargo multipliers enter EXTREME territory** under the proposed squared mode, with drag dropping to **<7% of original values**. Ships with 10x cargo have **~1% drag remaining**, making them likely **uncontrollable**.

---

## Ship Sample

| Ship | Type | Base Mass | Original Cargo | Cargo/Mass Ratio | Notes |
|------|------|-----------|----------------|------------------|-------|
| Nova Vanguard | S Fighter | 6.0 | 240 | 40.0x | Combat ship, minimal cargo |
| Mercury Vanguard | M Transporter | 43.0 | 8,200 | 190.9x | Medium freighter |
| Behemoth Vanguard | L Destroyer | 196.0 | 2,300 | 11.7x | Combat ship, small cargo |
| Magnetar (Gas) | L Miner | 205.3 | 42,000 | 204.6x | **Extreme cargo/mass ratio** |
| Shuyaku Vanguard | L Freighter | 650.4 | 37,000 | 56.9x | Largest cargo ship |

---

## Detailed Analysis

### 1. Nova Vanguard (S Fighter) - Light Ship, Minimal Cargo

**Base Stats:**
- Base Mass: 6.0
- Original Cargo: 240
- Original Full Mass: 246.0
- Cargo/Mass Ratio: **40.0x** (cargo is 4000% of base mass!)

| Cargo Mult | Adjusted Cargo | Adjusted Full Mass | Mass Ratio | Current Code Drag % | Proposed Squared Drag % | Status |
|------------|----------------|-------------------|------------|---------------------|------------------------|--------|
| **2x** | 480 | 486.0 | 1.976x | **50.6%** | **25.6%** | ⚠️ Aggressive |
| **4x** | 960 | 966.0 | 3.927x | **25.5%** | **6.5%** | ⚠️ WARNING |
| **8x** | 1,920 | 1,926.0 | 7.829x | **12.8%** | **1.6%** | 🔴 EXTREME |
| **10x** | 2,400 | 2,406.0 | 9.780x | **10.2%** | **1.0%** | 🔴 EXTREME |

**Key Insights:**
- Cargo is **40x base mass** - tiny changes in cargo create huge mass swings
- 2x cargo almost **doubles** the ship's mass (1.98x heavier)
- 10x cargo makes ship **9.8x heavier**
- Current code: 10.2% drag remaining (too weak)
- Proposed squared: **1.0% drag remaining** (likely uncontrollable)

---

### 2. Mercury Vanguard (M Transporter) - Medium Freighter

**Base Stats:**
- Base Mass: 43.0
- Original Cargo: 8,200
- Original Full Mass: 8,243.0
- Cargo/Mass Ratio: **190.9x** (cargo is 19,085% of base mass!)

| Cargo Mult | Adjusted Cargo | Adjusted Full Mass | Mass Ratio | Current Code Drag % | Proposed Squared Drag % | Status |
|------------|----------------|-------------------|------------|---------------------|------------------------|--------|
| **2x** | 16,400 | 16,443.0 | 1.995x | **50.1%** | **25.1%** | ⚠️ Aggressive |
| **4x** | 32,800 | 32,843.0 | 3.984x | **25.1%** | **6.3%** | ⚠️ WARNING |
| **8x** | 65,600 | 65,643.0 | 7.964x | **12.6%** | **1.6%** | 🔴 EXTREME |
| **10x** | 82,000 | 82,043.0 | 9.953x | **10.0%** | **1.0%** | 🔴 EXTREME |

**Key Insights:**
- Cargo dominates mass (190.9x) - base mass almost irrelevant
- Mass ratio scales almost **linearly** with cargo multiplier (2x cargo ≈ 2x mass)
- Behaves nearly identically to Magnetar despite different ship class
- Current code: Still too weak at 10x
- Proposed squared: **1.0% drag** - ship becomes a physics anomaly

---

### 3. Behemoth Vanguard (L Destroyer) - Combat Ship

**Base Stats:**
- Base Mass: 196.0
- Original Cargo: 2,300
- Original Full Mass: 2,496.0
- Cargo/Mass Ratio: **11.7x** (lowest in sample)

| Cargo Mult | Adjusted Cargo | Adjusted Full Mass | Mass Ratio | Current Code Drag % | Proposed Squared Drag % | Status |
|------------|----------------|-------------------|------------|---------------------|------------------------|--------|
| **2x** | 4,600 | 4,796.0 | 1.921x | **52.0%** | **27.1%** | ⚠️ Aggressive |
| **4x** | 9,200 | 9,396.0 | 3.764x | **26.6%** | **7.1%** | ⚠️ WARNING |
| **8x** | 18,400 | 18,596.0 | 7.450x | **13.4%** | **1.8%** | 🔴 EXTREME |
| **10x** | 23,000 | 23,196.0 | 9.293x | **10.8%** | **1.2%** | 🔴 EXTREME |

**Key Insights:**
- **Lowest cargo/mass ratio** in sample (11.7x)
- Base mass provides more **stability** - cargo increases have less impact
- 10x cargo only makes ship 9.3x heavier (vs 9.8x for Nova)
- Still enters extreme territory at 8x+ cargo
- Combat role means players rarely fill cargo - but mod affects ship anyway

---

### 4. Magnetar (Gas) Vanguard (L Miner) - Extreme Cargo Ship

**Base Stats:**
- Base Mass: 205.3
- Original Cargo: 42,000
- Original Full Mass: 42,205.3
- Cargo/Mass Ratio: **204.6x** (HIGHEST in sample)

| Cargo Mult | Adjusted Cargo | Adjusted Full Mass | Mass Ratio | Current Code Drag % | Proposed Squared Drag % | Status |
|------------|----------------|-------------------|------------|---------------------|------------------------|--------|
| **2x** | 84,000 | 84,205.3 | 1.995x | **50.1%** | **25.1%** | ⚠️ Aggressive |
| **4x** | 168,000 | 168,205.3 | 3.985x | **25.1%** | **6.3%** | ⚠️ WARNING |
| **8x** | 336,000 | 336,205.3 | 7.966x | **12.6%** | **1.6%** | 🔴 EXTREME |
| **10x** | 420,000 | 420,205.3 | 9.956x | **10.0%** | **1.0%** | 🔴 EXTREME |

**Key Insights:**
- **WORST CASE SCENARIO** - cargo is 204.6x base mass
- Base mass (205.3) is **insignificant** next to cargo (42,000)
- Behaves almost identically to Mercury despite being different size/class
- **This is the problematic ship mentioned in the plan** (player can't enter travel mode)
- Current code at 10x: 10% drag (insufficient)
- Proposed squared: **1.0% drag** - likely makes ship **worse**

---

### 5. Shuyaku Vanguard (L Freighter) - Largest Cargo Ship

**Base Stats:**
- Base Mass: 650.4
- Original Cargo: 37,000
- Original Full Mass: 37,650.4
- Cargo/Mass Ratio: **56.9x** (moderate)

| Cargo Mult | Adjusted Cargo | Adjusted Full Mass | Mass Ratio | Current Code Drag % | Proposed Squared Drag % | Status |
|------------|----------------|-------------------|------------|---------------------|------------------------|--------|
| **2x** | 74,000 | 74,650.4 | 1.983x | **50.4%** | **25.4%** | ⚠️ Aggressive |
| **4x** | 148,000 | 148,650.4 | 3.948x | **25.3%** | **6.4%** | ⚠️ WARNING |
| **8x** | 296,000 | 296,650.4 | 7.879x | **12.7%** | **1.6%** | 🔴 EXTREME |
| **10x** | 370,000 | 370,650.4 | 9.845x | **10.2%** | **1.0%** | 🔴 EXTREME |

**Key Insights:**
- **Heaviest base mass** (650.4) provides more stability
- Cargo/mass ratio (56.9x) is moderate compared to Magnetar
- Still enters extreme territory at 8x+ multipliers
- High base mass means less extreme mass ratios than lighter ships
- Proposed squared mode: Still drops to **1.0% drag** at 10x

---

## Comparative Analysis

### Mass Ratio Variance by Ship Type

The **same cargo multiplier** produces wildly different mass ratios:

| Ship | Cargo/Mass | 2x Mass Ratio | 4x Mass Ratio | 10x Mass Ratio |
|------|-----------|---------------|---------------|----------------|
| Behemoth | 11.7x | 1.921x | 3.764x | 9.293x |
| Shuyaku | 56.9x | 1.983x | 3.948x | 9.845x |
| Nova | 40.0x | 1.976x | 3.927x | 9.780x |
| Mercury | 190.9x | 1.995x | 3.984x | 9.953x |
| Magnetar | 204.6x | 1.995x | 3.985x | 9.956x |

**Pattern:** Ships with **high cargo/mass ratios** (Mercury, Magnetar) see **near-linear** mass scaling (10x cargo ≈ 10x mass). Ships with **low ratios** (Behemoth) see **sub-linear** scaling.

---

## Current Code vs Proposed Squared Mode

### Current Code Behavior (dragReductionFactor = 1.0)

**Formula:** 
```
dragReductionMultiplier = massMultiplier * dragReductionFactor
where massMultiplier = originalFullMass / adjustedFullMass

newDrag = originalDrag - (originalDrag * dragReductionMultiplier)
        = originalDrag * (1 - dragReductionMultiplier)
```

**Example (Shuyaku, 10x cargo):**
- massMultiplier = 37650.4 / 370650.4 = 0.102
- dragReductionMultiplier = 0.102 * 1.0 = 0.102
- newDrag = originalDrag * (1 - 0.102) = originalDrag * 0.898
- **Result: 89.8% of original drag** (too weak!)

---

### Proposed Squared Mode

**Formula (from plan):**
```
massRatio = adjustedFullMass / originalFullMass
newDrag = originalDrag / massRatio²
```

**Example (Shuyaku, 10x cargo):**
- massRatio = 370650.4 / 37650.4 = 9.845
- massRatio² = 96.92
- newDrag = originalDrag / 96.92
- **Result: 1.03% of original drag** (EXTREME!)

---

## Critical Issues Identified

### 1. No Safety Caps

**Finding:** Code contains **NO min/max limits** on drag values.

**Files Checked:**
- `FlightMechanicsOverrideFile.php` - No caps
- `AdjustedDrag.php` - No caps
- `functions.php` (calcDecrease) - No caps

**Impact:** Drag can drop to **any value**, including **near-zero** or **negative** (if formula changes).

---

### 2. Extreme Cases at Higher Multipliers

**At 10x cargo multiplier:**

| Ship | Proposed Drag % | Assessment |
|------|----------------|------------|
| Nova | 1.05% | 🔴 CRITICAL - 95% drag removed |
| Mercury | 1.01% | 🔴 CRITICAL - 99% drag removed |
| Behemoth | 1.16% | 🔴 CRITICAL - 98.8% drag removed |
| Magnetar | 1.01% | 🔴 CRITICAL - 99% drag removed |
| Shuyaku | 1.03% | 🔴 CRITICAL - 99% drag removed |

**All ships become physics anomalies** - may overshoot, drift uncontrollably, or exhibit non-physical behavior.

---

### 3. Current Code is Also Insufficient

**At 10x cargo multiplier (current code):**

| Ship | Current Drag % | Assessment |
|------|----------------|------------|
| Nova | 10.2% | ⚠️ Still too weak |
| Mercury | 10.0% | ⚠️ Still too weak |
| Behemoth | 10.8% | ⚠️ Still too weak |
| Magnetar | 10.0% | ⚠️ Still too weak (travel mode issue!) |
| Shuyaku | 10.2% | ⚠️ Still too weak |

**Current code reduces drag to ~10%** of original, which is **still insufficient** to compensate for fixed engine thrust. Magnetar specifically has documented travel mode issues.

---

### 4. No Relationship to Actual Physics

**Current "multiplier" is just:**
```
originalFullMass / adjustedFullMass
```

This is the **inverse of mass ratio**, not a physics-based compensation factor. It's an **arbitrary ratio** unrelated to:
- Engine thrust curves
- Terminal velocity
- Acceleration physics
- Ship role or design intent

---

## Implications for Proposed Plan

### Travel Mode Issue (Primary Problem)

**Current Issue:** Magnetar with 10x cargo can't enter travel mode.

**Current Drag:** ~10% of original (insufficient)

**Proposed Squared Drag:** ~1% of original

**Assessment:** Proposed change makes drag **10x weaker**, likely making the problem **WORSE**, not better. Ship may:
- Accelerate uncontrollably
- Overshoot travel mode entry requirements
- Drift past intended position
- Exhibit unstable physics behavior

---

### Secondary Concerns

1. **All ships become extreme cases** at 4x+ multipliers
2. **No safety limits** to prevent physics-breaking values
3. **One formula fits all** approach ignores ship role differences
4. **Jerk adjustments** (not analyzed here) may compound issues
5. **Engine thrust is fixed** - no amount of drag reduction can fully compensate

---

## Recommendations (Analysis-Based)

### Immediate Concerns

1. **Current code has no relationship to reported issue** - travel mode problem needs direct investigation
2. **Proposed squared mode is 10x more aggressive** than current code - likely overshoots
3. **Safety caps are mandatory** before any formula changes
4. **Ship role matters** - combat ships, miners, and freighters need different treatments

### Questions to Answer (Beyond Scope)

- What **exactly** prevents travel mode entry? (Physics threshold? Speed check? Bug?)
- Are there **X4 game engine limits** on drag values we must respect?
- Should **ship class** influence compensation factors?
- Is **cargo/mass ratio** a better baseline than absolute multipliers?
- Do **other mods** affect this system?

---

## Appendix: Raw Calculation Data

### Formula Reference

**Current Code:**
```
massMultiplier = baseMass + originalCargo
               ─────────────────────────────
               baseMass + adjustedCargo

dragReductionMultiplier = massMultiplier * 1.0

newDrag = originalDrag * (1 - dragReductionMultiplier)
```

**Proposed Squared:**
```
massRatio = baseMass + adjustedCargo
           ─────────────────────────────
           baseMass + originalCargo

newDrag = originalDrag / massRatio²
```

**Key Difference:** Current code uses **inverse ratio** as a **subtraction factor**. Proposed uses **direct ratio squared** as a **division factor**.

---

## Conclusion

**ANALYSIS CONFIRMS:**
- Current code is insufficient (travel mode issue exists)
- Proposed squared mode is **too aggressive** (creates worse extremes)
- **No safety limits** exist in either approach
- Ships with different cargo/mass ratios behave **radically differently**
- **Root cause** of travel mode issue is **unknown** - mass/drag may not be the solution

**RECOMMENDATION:** Do not implement squared mode without:
1. Understanding root cause of travel mode issue
2. Adding safety caps (minimum drag thresholds)
3. Ship-class-specific tuning
4. Extensive testing on Magnetar and Mercury (highest cargo/mass ratios)

---

*Analysis generated from extracted X4 game data and current codebase. No solutions implemented.*
