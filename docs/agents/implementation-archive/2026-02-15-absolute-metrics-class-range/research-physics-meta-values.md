# Research Report

## Problem Statement

The Physics Tuning GUI exposes low-level physics parameters (drag reduction percentages, inertia impact factors, jerk reduction tiers) that are difficult for users to translate into intuitive understanding of ship behavior. When a user moves a slider, they see raw numbers like "drag reduced 30%" or "inertia increased 91%", but cannot easily answer questions like: **"Will this ship still feel nimble?"** or **"How sluggish will turning be?"**

The goal is to find a way to derive **high-level behavioral meta-values** from the existing physics parameters that communicate the *feel* of the ship in terms a human can immediately grasp.

## Problem Decomposition

1. **Identify the behavioral dimensions** — What distinct aspects of ship behavior do users care about? (e.g., top speed, turning, acceleration responsiveness)
2. **Map raw parameters to behavioral dimensions** — Which low-level values (drag, inertia, jerk, mass ratio, TWR) contribute to each behavioral dimension?
3. **Define a scoring/normalization strategy** — How to express composite behaviors on a consistent, intuitive scale (e.g., 0–100, letter grades, named tiers)
4. **Determine before/after comparison strategy** — Users need to see how the mod changes *feel* relative to the unmodified ship
5. **Validate against known patterns** — Do established games, flight models, or engineering disciplines already solve this problem?

## Context & Constraints

- **Physics model**: Drag (7 axes), Inertia (3 axes: pitch/yaw/roll), Jerk (forward/boost/travel × accel/decel), Mass Ratio, TWR
- **Core formulas**:
  - Drag: `adjusted = original × (1 - tierReductionPercent)` — lower drag → higher top speed
  - Inertia: `adjusted = original × (1 + (massRatio - 1) × impactFactor)` — higher inertia → slower turning
  - Jerk: `adjusted = original × (1 - tierReductionPercent)` — lower jerk → more gradual acceleration changes
  - TWR: `thrust / (mass × g)` — lower TWR → slower acceleration
- **Architecture**: Frontend (React+TS) ↔ Backend (PHP) ↔ PhysicsCalculator. Meta-values can be computed on either side.
- **Hard constraint**: No databases, synchronous PHP, all data already available in `PhysicsResponse`
- **Soft preference**: Computation should be fast enough to stay within the <500ms feedback target
- **User persona**: Mod developers tuning physics before building; they understand "this ship should feel heavy but not unplayable"

## Prior Art & Known Patterns

### Pattern 1: Composite Rating Scores (Space Sim Games)

- **Description:** Games like Elite Dangerous, Star Citizen, and X4 itself use single-number or letter-grade ratings for ship attributes like "Maneuverability: 6/10" or "Agility: B". These are typically pre-computed by designers, not derived from physics. The number represents a composite judgment of how multiple physics properties affect a single behavioral quality.
- **Where used:** Elite Dangerous (0–10 scale for maneuverability, speed, etc.), Star Citizen (ship matrix with handling/agility ratings), X4 Foundations (ship class descriptions)
- **Strengths:** Immediately understandable; enables quick comparison between ships; familiar to the target audience (X4 players/modders)
- **Weaknesses:** Typically hand-tuned by designers, not formula-derived; can lose nuance (a "5" doesn't tell you *why*); requires careful calibration to feel meaningful
- **Fit:** HIGH — The target audience expects this kind of abstraction. The challenge is deriving it from formulas rather than hand-tuning.

### Pattern 2: Percentage-of-Original Comparison

- **Description:** Instead of abstract scores, show each behavioral dimension as a percentage of the original unmodified ship. E.g., "Top Speed: 115% of original", "Turn Rate: 68% of original", "Acceleration Feel: 82% of original". This is essentially a "delta from baseline" approach.
- **Where used:** Racing games (before/after car tuning screens), flight simulators (performance comparison sheets), KSP (delta-v and TWR before/after staging)
- **Strengths:** Directly answers "how much did the mod change things?"; no arbitrary scale to calibrate; mathematically derivable from existing formulas; self-explanatory
- **Weaknesses:** Doesn't provide an absolute sense of "good" or "bad" (115% top speed — is that fast or slow?); requires the user to already understand the baseline; multiple percentages can still be overwhelming
- **Fit:** HIGH — All required data is already computed in `PhysicsResponse`. This is the easiest to implement and the most honest representation.

### Pattern 3: Named Behavioral Tiers / Qualitative Labels

- **Description:** Map numeric ranges to human-readable labels. E.g., Turn Rate percentage maps to: "Brick" (0–40%), "Sluggish" (40–60%), "Normal" (60–80%), "Agile" (80–95%), "Nimble" (95–110%). This is essentially Pattern 2 with a qualitative overlay.
- **Where used:** RPG stat systems (D&D ability score labels: "Poor", "Average", "Good", "Exceptional"), Kerbal Space Program community (TWR guidelines: "<1.0 won't lift", "1.2–1.5 efficient", ">2.0 wasteful"), MechWarrior (mech class labels: Light/Medium/Heavy/Assault based on tonnage)
- **Strengths:** Instantly communicates intent ("Sluggish" is unambiguous); bridges the gap between numbers and feeling; can carry thematic flavor
- **Weaknesses:** Label boundaries are arbitrary and need careful tuning; different users may disagree on where "Agile" starts; can oversimplify
- **Fit:** MEDIUM-HIGH — Works excellently when combined with Pattern 2 (show the percentage *and* the label). Labels would need to be calibrated against actual gameplay testing.

### Pattern 4: Spider/Radar Chart Visualization

- **Description:** Display 4–6 behavioral dimensions as a radar chart (spider chart), showing the original ship outline vs. the modified ship outline. Each axis represents a derived behavioral metric. The visual overlap/difference immediately communicates the trade-offs.
- **Where used:** FIFA/sports games (player stats), Elite Dangerous (ship comparison tools), racing games (car tuning screens), Dark Souls (weapon comparison)
- **Strengths:** Visual — communicates trade-offs at a glance; shows the *shape* of behavior, not just individual numbers; natural for comparative analysis (before vs. after overlay)
- **Weaknesses:** Axis ordering affects perception; more than 6 axes becomes cluttered; requires careful normalization; the project already uses `recharts` which supports radar charts
- **Fit:** HIGH — The project already depends on `recharts` (which includes `RadarChart`), making this low-effort to implement. Combined with derived behavioral scores, this would be very powerful.

### Pattern 5: Thrust-to-Weight Ratio as Master Metric

- **Description:** TWR is the single most important derived metric in aerospace and space sim physics. It directly answers "can this ship accelerate adequately?" Formula: $TWR = \frac{Thrust}{Mass \times g}$. Already partially implemented via `EnginePerformance` DTO which calculates `originalTWR` and `adjustedTWR`.
- **Where used:** Kerbal Space Program (critical gameplay metric), real aerospace engineering, every space sim with physics
- **Strengths:** Universally understood in the target audience; physically meaningful; already available in the codebase
- **Weaknesses:** Only covers linear acceleration, not turning or responsiveness; requires engine selection (optional in current UI); doesn't capture drag or jerk behavior
- **Fit:** HIGH as one dimension among several — but insufficient alone.

### Pattern 6: Time-to-X Derived Metrics

- **Description:** Rather than showing abstract percentages, compute concrete time estimates: "Time to reach top speed: 12.4s → 18.7s", "Time to complete 180° turn: 3.2s → 5.1s". These are derived from the physics parameters using standard kinematic equations.
- **Where used:** Automotive reviews (0–60 mph times), racing game telemetry, KSP mission planning (burn time estimates), Star Citizen ship specs
- **Strengths:** Extremely intuitive — everyone understands "it takes 5 seconds longer to turn around"; directly actionable; answers real gameplay questions
- **Weaknesses:** Requires additional assumptions (target speed, turn angle, initial conditions); may need engine data to compute; estimates may not match actual game behavior exactly due to engine-specific implementation details in X4
- **Fit:** MEDIUM — Powerful concept but introduces complexity. Would require assumptions about X4's flight model integration (how drag/jerk translate to actual game seconds). Could be approximated using $v_{max} = Thrust / Drag$ and $t_{accel} \approx \frac{v_{max} \times Mass}{Thrust}$ but accuracy is uncertain.

## Alternative & Creative Approaches

### Approach A: "Ship Character Profile" — Behavioral Fingerprint

Combine Patterns 1, 2, 3, and 4 into a unified **Ship Character Profile**:

1. **Define 5 behavioral dimensions**, each derived from existing physics data:

   | Dimension | Description | Derived From | Formula (% of Original) |
   |-----------|-------------|-------------|------------------------|
   | **Top Speed** | How fast the ship can go | Drag (forward) | `originalDrag / adjustedDrag × 100` (since $v_{max} \propto 1/drag$) |
   | **Acceleration** | How quickly it reaches top speed | TWR + Jerk (forward accel) | `(adjustedTWR / originalTWR) × (adjustedJerkAccel / originalJerkAccel) × 100` |
   | **Agility** | How quickly it can turn/rotate | Inertia (pitch, yaw, roll avg) | `originalInertia / adjustedInertia × 100` (inverse because higher inertia = slower turning) |
   | **Responsiveness** | How snappy controls feel | Jerk (all modes average) | `adjustedJerk / originalJerk × 100` (% of original responsiveness) |
   | **Braking** | How quickly it can stop | Drag (forward) + Jerk (decel) | `(adjustedDrag / originalDrag × 0.5 + adjustedJerkDecel / originalJerkDecel × 0.5) × 100` |

2. **Normalize to 0–100% of original** (100% = unchanged, >100% = improved, <100% = degraded)

3. **Apply qualitative labels** per dimension:
   - 0–30%: **Crippled** — Ship is nearly unplayable in this dimension
   - 30–50%: **Heavily Impaired** — Noticeably worse, gameplay-affecting
   - 50–70%: **Reduced** — Clearly slower/heavier but workable
   - 70–85%: **Slightly Reduced** — Subtle difference, good compromise
   - 85–100%: **Near Original** — Barely noticeable change
   - 100–130%: **Enhanced** — Better than stock (from drag reduction)
   - 130%+: **Greatly Enhanced** — Significantly improved over stock

4. **Visualize as radar chart** (original = outer boundary, modified = filled polygon)

- **Rationale:** This gives the user a complete behavioral picture using data already available in `PhysicsResponse`. No new API calls needed. Every dimension is directly derivable from existing response fields. Labels add immediate human understanding. Radar chart provides at-a-glance comparison.
- **Risk:** Label boundaries may need iteration; the weighted formulas for Acceleration and Braking need gameplay validation; some dimensions may feel redundant (Top Speed and Braking both depend on drag).

### Approach B: Single "Handling Grade" with Breakdown

Compute a single **overall handling grade** (A+ through F) as a weighted composite, then show the breakdown:

```
Overall Grade: B+ (73% of original handling)
├── Top Speed:      ██████████████░░ 130%  Enhanced
├── Acceleration:   ████████████░░░░  78%  Slightly Reduced
├── Agility:        ██████████░░░░░░  65%  Reduced
├── Responsiveness: ██████████████░░  85%  Near Original
└── Braking:        ███████████░░░░░  72%  Slightly Reduced
```

- **Rationale:** Gives both a quick summary (for rapid slider adjustment) and detailed breakdown (for fine-tuning). The letter grade immediately answers "is this config playable?"
- **Risk:** The weighted composite requires opinionated weighing of dimensions (is Agility more important than Top Speed?). Could solve with user-configurable weights or sensible defaults.

### Approach C: "Before vs. After" Sentence Generator

Generate natural language summaries:

> *"This ship will reach **30% higher top speed** but will turn **35% slower** and take **15% longer to accelerate**. Controls will feel **nearly as responsive** as the original."*

- **Rationale:** Zero learning curve — plain English (or any language). Could use the existing translation system.
- **Risk:** Text generation adds complexity; thresholds for "nearly", "significantly", etc. need tuning; may feel gimmicky.

## Comparative Evaluation

| Criterion | Pattern 2: %-of-Original | Pattern 3: Named Labels | Pattern 4: Radar Chart | Approach A: Ship Character Profile | Approach B: Handling Grade | Approach C: Sentences |
|---|---|---|---|---|---|---|
| **Complexity** | Very Low | Low | Medium | Medium | Medium-High | High |
| **Intuitiveness** | Medium | High | High | Very High | Very High | Very High |
| **Accuracy** | High | Medium (labels are approximate) | High (visual) | High | Medium (composite loses nuance) | Medium |
| **Implementation Effort** | ~2h (frontend only) | ~3h (frontend only) | ~4h (frontend, uses recharts) | ~8h (frontend + minor backend) | ~6h (frontend + minor backend) | ~10h (frontend + backend + i18n) |
| **Maintainability** | Easy | Easy | Easy | Medium | Medium | Hard (strings) |
| **Information Density** | Low (just numbers) | Medium | High | Very High | High | Medium |
| **Risk** | None | Label calibration | Normalization | Formula calibration | Weight calibration | Threshold calibration + i18n |

## Recommendation

**Implement Approach A: "Ship Character Profile"** as a phased rollout, combining the best elements of Patterns 2, 3, and 4.

### Why This Approach

1. **All data already exists** — Every dimension can be computed from fields already in `PhysicsResponse`. No new API endpoints or backend calculations needed.
2. **Leverages existing dependencies** — `recharts` (already installed) includes `RadarChart` component.
3. **Layered information** — Users get (a) a visual shape, (b) percentage numbers, and (c) qualitative labels — three levels of detail for different needs.
4. **Modder-friendly** — The target audience (X4 modders) will immediately understand "Agility: 65% — Reduced" and can decide if that's acceptable.
5. **Low risk** — Even if qualitative labels need adjustment, the percentages are mathematically correct from day one.

### Recommended Behavioral Dimensions (5)

| # | Dimension | Display Name | Source Fields from `PhysicsResponse` | Formula |
|---|-----------|-------------|--------------------------------------|---------|
| 1 | **Top Speed** | Top Speed | `drag.original.forward`, `drag.adjusted.forward` | $\frac{drag_{original}}{drag_{adjusted}} \times 100$ |
| 2 | **Acceleration** | Acceleration | `enginePerformance.originalAcceleration`, `enginePerformance.adjustedAcceleration` (or `massRatio` as proxy) | $\frac{accel_{adjusted}}{accel_{original}} \times 100$ or $\frac{1}{massRatio} \times 100$ when no engine selected |
| 3 | **Agility** | Agility | `inertia.original.{pitch,yaw,roll}`, `inertia.adjusted.{pitch,yaw,roll}` | $\frac{avg(inertia_{original})}{avg(inertia_{adjusted})} \times 100$ |
| 4 | **Responsiveness** | Responsiveness | `jerk.original.forward.accel`, `jerk.adjusted.forward.accel` (+ boost, travel) | $\frac{avg(jerk_{adjusted})}{avg(jerk_{original})} \times 100$ |
| 5 | **Braking** | Braking | `jerk.original.forward.decel`, `jerk.adjusted.forward.decel`, `drag.adjusted.forward` | $\frac{jerkDecel_{adjusted}}{jerkDecel_{original}} \times 100$ |

### Qualitative Label Scale

| Range | Label | Color | Meaning |
|-------|-------|-------|---------|
| 0–40% | Crippled | Red | Nearly unplayable |
| 40–60% | Impaired | Orange | Significantly degraded |
| 60–80% | Reduced | Yellow | Noticeably heavier/slower |
| 80–95% | Slightly Reduced | Light Green | Subtle change, good compromise |
| 95–105% | Unchanged | Green | Negligible difference |
| 105–130% | Enhanced | Cyan/Blue | Improved over stock |
| 130%+ | Greatly Enhanced | Purple | Major improvement |

### Proof-of-Concept Outline

Phase 1 (Frontend only, ~4h):
1. Create a `deriveShipProfile(response: PhysicsResponse): ShipProfile` utility function that computes the 5 dimensions as percentages
2. Create a `ShipProfileCard` component that displays the 5 dimensions as horizontal bars with percentage labels and qualitative text
3. Wire it into `ResultsPanel` — it renders whenever `PhysicsResponse` is available

Phase 2 (Radar chart, ~2h):
1. Add a `ShipProfileRadar` component using `recharts`' `RadarChart`
2. Show original (100% on all axes) as reference polygon, adjusted values as overlay
3. Place alongside or as tab alternative to the bar display

Phase 3 (Refinement, ~2h):
1. Calibrate qualitative label boundaries against actual gameplay testing
2. Add tooltips explaining each dimension
3. Consider adding the natural language summary (Approach C) as a one-line "verdict" above the chart

## Open Questions

- **Engine dependency for Acceleration dimension:** When no engine is selected, the Acceleration dimension falls back to inverse mass ratio, which is less accurate. Should the UI prompt users to select an engine for full profile, or is the mass-ratio proxy sufficient?
- **Label boundary calibration:** The proposed ranges (0–40%, 40–60%, etc.) are educated guesses. They should be validated against actual X4 gameplay with different multipliers to ensure "Reduced" truly feels "reduced" in-game.
- **Weight of axes in radar chart:** Should all 5 axes be equally weighted visually, or should some be larger/smaller based on gameplay importance? (Recommendation: equal weighting initially, adjust based on feedback.)
- **Strafe/lateral dimensions:** The current 5 dimensions focus on forward flight and turning. Should lateral movement (horizontal/vertical drag) be a 6th dimension ("Strafe Capability") for combat-oriented ships?
- **Translation/i18n:** The qualitative labels ("Crippled", "Reduced", etc.) will need translation. The project already has a translation system (`config/translations.json`) — should these labels be added there?

## References

- **Thrust-to-Weight Ratio** — [Wikipedia: Thrust-to-weight ratio](https://en.wikipedia.org/wiki/Thrust-to-weight_ratio). Standard aerospace metric: $TWR = T / (m \times g)$.
- **Ship Motions (6 DOF)** — [Wikipedia: Ship motions](https://en.wikipedia.org/wiki/Ship_motions). Surge, sway, heave, roll, pitch, yaw — the 6 degrees of freedom model.
- **Vehicle Dynamics** — [Wikipedia: Vehicle dynamics](https://en.wikipedia.org/wiki/Vehicle_dynamics). Academic framework for analyzing vehicle behavior from component parameters.
- **Recharts RadarChart** — [Recharts documentation](https://recharts.org/en-US/api/RadarChart). Already available in the project's frontend dependencies.
- **Kerbal Space Program TWR guidelines** — Community-established thresholds: <1.0 (no liftoff), 1.2–1.5 (efficient), >2.0 (wasteful). Model for calibrating qualitative labels.
- **Elite Dangerous ship stats** — In-game 0–10 rating scales for maneuverability, speed, etc. Prior art for composite game ship ratings derived from physics.
- **X4 Cargo Sizes Mod — PhysicsCalculator** — [PhysicsCalculator.php](../../src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php). Source of all underlying formulas.
- **X4 Cargo Sizes Mod — Physics Tuning Guide** — [physics-tuning-guide.md](../../docs/physics-tuning-guide.md). Explains why tier-based approach is used and the relationship between parameters and gameplay feel.
