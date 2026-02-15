# Research Report

## Problem Statement

Design a hybrid approach for the Physics Tuning GUI that combines (1) **absolute estimated metrics** (top speed in m/s, acceleration in m/s², turn rate) with (3) **min/max range across all ships in a class** — so that when a user adjusts sliders, they see not just what happens to one ship, but the range of impact across the entire class (e.g., all Transport ships at 4x), with absolute numbers that convey real gameplay meaning.

## Problem Decomposition

1. **Data gap**: The GUI currently uses hardcoded placeholder physics values (drag=100, inertia=10, jerk=50) instead of real per-ship data. What data is actually available, and what's missing?
2. **Absolute metric formulas**: Which real-world metrics can be derived from the physics parameters, and with what accuracy?
3. **Class-wide aggregation**: How to efficiently compute results across all ships in a class for a given config, and present the min/max/median range?
4. **Architecture**: Where should the computation happen (frontend vs. backend)? What new endpoints or services are needed?
5. **UX presentation**: How to display the range + absolute values in a way that's immediately useful?

## Context & Constraints

- **Hard constraints**: No databases, synchronous PHP, <500ms feedback target, local dev tool only
- **Available data per ship from x4-core v1.3.0 (`ShipDef`)**: Complete physics data for all 256 ships:
  - `mass` — ship mass in tons
  - **7 drag axes**: `dragForward`, `dragReverse`, `dragHorizontal`, `dragVertical`, `dragPitch`, `dragYaw`, `dragRoll`
  - **3 inertia axes**: `inertiaPitch`, `inertiaYaw`, `inertiaRoll`
  - **10 jerk values**: `jerkStrafe`, `jerkAngular`, `jerkForwardAccel`, `jerkForwardDecel`, `jerkForwardRatio`, `jerkBoostAccel`, `jerkBoostRatio`, `jerkTravelAccel`, `jerkTravelDecel`, `jerkTravelRatio`
  - **4 acceleration factors**: `accFactorForward`, `accFactorReverse`, `accFactorHorizontal`, `accFactorVertical`
  - **Cargo**: `cargoCapacity` (m³), `cargoType` (`container` | `liquid` | `solid` | `none`)
  - **Storage**: `peopleCapacity`, `missileStorage`
  - **Hull**: `hull` (hitpoints)
- **Available data per engine from x4-core (`EngineDef`)**: `thrustForward`, `thrustReverse`, `boostThrust`, `boostDuration`, `boostRecharge`, `boostAcceleration`, `travelThrust`, `travelCharge`, `decelerationCurve` — real values, not estimated
- **Data quality**: No tiered fallback needed. All physics data is available programmatically via x4-core without requiring extracted game XML or `dev-config.php` configuration. The only data **not** available in x4-core is the steering curve (`SteeringCurve` with position/value pairs), which is still XML-only but not needed for the metrics in this design.
- **Batch API**: Already exists (`POST /api/calculate/batch`) but currently uses placeholder physics values
- **Formula confirmed in codebase**: $v_{max} = Thrust / Drag_{forward}$ (comment in `AdjustedDrag.php`)
- **~80 cargo-relevant ships** across Transport, Mining, Auxiliary, and Carrier classes

## Prior Art & Known Patterns

### Pattern 1: "Best/Worst Case" Range Display (Racing Games)

- **Description:** Show the performance envelope — best-case ship and worst-case ship under the current settings. Players see "Top speed: 285–412 m/s (Mercury → Colossus)" and immediately know the full range of impact.
- **Where used:** Forza Horizon tuning screen (shows min/max across car variants), Gran Turismo (before/after comparison bars)
- **Strengths:** Directly answers "will any ship become unplayable?"; compact display; highlights outliers
- **Weaknesses:** Hides the distribution — are most ships near the worst or best case?; requires computing all ships
- **Fit:** HIGH — The batch API already exists. A new endpoint that returns aggregated min/max/median per metric across a ship class would be efficient.

### Pattern 2: Box Plot / Range Visualization

- **Description:** Show a basic range indicator (min—median—max) as a horizontal bar with markers for each metric. Like a simplified box plot. The current config's resulting value for the selected ship is highlighted.
- **Where used:** Financial dashboards, medical reference ranges ("your blood pressure vs. normal range"), D&D character stat comparison
- **Strengths:** Shows distribution shape at a glance; highlights where the selected ship falls within the class; minimal screen space
- **Weaknesses:** Unfamiliar to some users; requires explaining the visualization
- **Fit:** MEDIUM-HIGH — Elegant but may need a tooltip explaining the visualization to users unfamiliar with box plots.

### Pattern 3: Absolute Values with Unit Context

- **Description:** Display estimated metrics in real units with contextual explanation. E.g., "Top Speed: ~412 m/s (fast corvette range)" or "Acceleration: 2.1 m/s² (comparable to a laden freighter)". The units carry meaning, and the context phrase helps interpret.
- **Where used:** Car reviews ("0–60 in 3.2 seconds — supercar territory"), gaming wikis (DPS + context)
- **Strengths:** Self-explanatory; no learning curve; numbers become meaningful through context
- **Weaknesses:** Context phrases need domain expertise to write; may be wrong for edge cases
- **Fit:** HIGH — X4 modders understand m/s. Context phrases can be derived from the ship size/class.

### Pattern 4: "Estimated Values from Available Data" with Accuracy Indicator

- **Description:** Show computed absolute metrics but mark them with a confidence level. Full physics data → "Accurate ✓". Only `dragForward` available → "Estimated ~". No engine selected → "Requires engine data".
- **Where used:** Weather forecasts (confidence intervals), financial projections (forecast vs. actual), Google Maps (ETA with range)
- **Strengths:** Honest about data quality; lets the user decide how much to trust; avoids false precision
- **Weaknesses:** Adds visual complexity; users may ignore confidence markers
- **Fit:** ~~HIGH~~ **LOW (Updated Feb 15, 2026)** — With x4-core v1.3.0 providing complete physics data for all ships, there is no longer a meaningful "estimated vs. accurate" distinction. All computed metrics use real per-ship values. The only remaining use case for accuracy indicators would be when no engine is selected (engine-dependent metrics like top speed cannot be computed).

## Alternative & Creative Approaches

### Approach: Direct Full-Data Integration (Supersedes Tiered Strategy)

> **Update (Feb 15, 2026):** X4 Core v1.3.0 now provides complete per-ship physics data programmatically. The previously proposed "Two-Phase Data Strategy with Graceful Degradation" (Tier A/B) is **no longer necessary**. All 7 drag axes, 3 inertia axes, 10 jerk values, 4 acceleration factors, and cargo capacity are available directly on `ShipDef` for all 256 ships without requiring extracted game XML.

With full physics data available out of the box, the implementation can use **accurate real values everywhere** with no fallback tiers or confidence indicators:

- Use `ShipDef.getDragForward()` through `getDragRoll()` → accurate top speed in all directions
- Use `ShipDef.getInertiaPitch()`, `getInertiaYaw()`, `getInertiaRoll()` → accurate turn rate estimates for all axes
- Use `ShipDef.getJerk*()` (10 values) → acceleration curve characteristics
- Use `ShipDef.getAccFactor*()` (4 values) → directional acceleration modifiers
- Use `ShipDef.getCargoCapacity()` + `getCargoType()` → real per-ship cargo data (no hardcoded estimates)
- Use `ShipDef.getMass()` + `EngineDef.getThrustForward()` + `ShipDef.countEngines()` → accurate acceleration, TWR

**Implementation:** The backend reads ship data directly from `ShipDefs::getInstance()`. No `dev-config.php` configuration, no `ShipXMLFile` parsing, no data quality tiers. All values are authoritative.

- **Rationale:** Eliminates all data-quality complexity from both backend and frontend. Every metric is accurate. No "Estimated" vs. "Accurate" UI distinction needed.
- **Risk:** Minimal. The only physics data not in x4-core is the steering curve, which is irrelevant for the cargo/speed/acceleration metrics in this design.

## Detailed Design: Class-Wide Range Endpoint

### New API Endpoint

```
POST /api/calculate/class-range
```

**Request body:**
```typescript
interface ClassRangeRequest {
  shipType: string;           // "transport" | "mining" | "auxiliary" | "carrier"
  cargoMultiplier: number;    // 2, 4, 6, 8, 10
  flightMechanics: FlightMechanics;  // current slider values
  engineId?: string;          // optional engine for TWR/speed estimates
}
```

**Response:**
```typescript
interface ClassRangeResponse {
  shipCount: number;          // how many ships in this class
  metrics: {
    topSpeed: RangeMetric;      // m/s (only if engine selected)
    acceleration: RangeMetric;  // m/s² (only if engine selected)
    twr: RangeMetric;           // ratio (only if engine selected)
    dragChange: RangeMetric;    // % change from original
    inertiaChange: RangeMetric; // % change from original
    jerkChange: RangeMetric;    // % change from original
    massRatio: RangeMetric;     // ratio
  };
  worstCase: ShipMetricSummary;  // the ship with the most extreme changes
  bestCase: ShipMetricSummary;   // the ship with the most moderate changes
}

interface RangeMetric {
  min: number;
  max: number;
  median: number;
  unit: string;        // "m/s", "m/s²", "%", "ratio"
  label: string;       // human-readable name
}

interface ShipMetricSummary {
  shipId: string;
  shipName: string;
  size: string;
  massRatio: number;
  topSpeed?: { original: number; adjusted: number };
  acceleration?: { original: number; adjusted: number };
}
```

### Computation Flow

```
User adjusts slider
        ↓
Frontend sends ClassRangeRequest (debounced 500ms — longer than single calc)
        ↓
Backend: ClassRangeService
        ↓
1. Get all ships of type from ShipDefs (full physics data available for all ships)
2. For each ship:
   a. Calculate massRatio using PhysicsCalculator
   b. Find applicable tier for this multiplier
   c. Compute drag/inertia/jerk adjustments
   d. If engine selected: compute top speed, acceleration, TWR
3. Aggregate: min, max, median for each metric
4. Identify worst-case and best-case ships
        ↓
Return ClassRangeResponse
        ↓
Frontend: ClassRangePanel component
        ↓
Display range bars + absolute values + worst/best ship names
```

### Performance Estimate

- ~80 cargo-relevant ships × ~10 arithmetic operations each = ~800 operations
- PHP can handle this in <10ms
- JSON serialization: <5ms
- Total: well under 100ms even with 500ms debounce
- **No performance risk**

## Formulas for Absolute Metrics

### Top Speed (requires engine selection)

$$v_{max} = \frac{Thrust_{forward} \times EngineCount}{Drag_{forward}}$$

- **With original drag**: gives original top speed  
- **With adjusted drag**: gives modded top speed  
- **Units**: m/s (X4 uses SI internally)

**Accuracy:** Uses real per-ship `ShipDef.getDragForward()` value. All 7 drag axes available via x4-core v1.3.0 for multi-directional speed calculations.

### Acceleration

$$a = \frac{Thrust_{forward} \times EngineCount \times 1000}{Mass_{full}}$$

- **Units**: m/s²  
- Uses `ShipDef.getMass()` (real) + `ShipDef.getCargoCapacity()` (real, per-ship) + `EngineDef.getThrustForward()` (real)

**Note:** Engine count per ship is available via `ShipDef.countEngines()` — this is critical for accurate estimates since large ships have multiple engine slots.

### Turn Rate (relative)

Without steering torque data, absolute degrees/s isn't possible. But relative comparison across all 3 axes is:

$$turnRate_{relative} = \frac{1}{Drag_{pitch}} \quad \text{(proportional)}$$

**For before/after comparison:**

$$turnRate_{ratio} = \frac{Drag_{pitch,original}}{Drag_{pitch,adjusted}}$$

**Accuracy:** All 3 rotational drag axes (`dragPitch`, `dragYaw`, `dragRoll`) and all 3 inertia axes (`inertiaPitch`, `inertiaYaw`, `inertiaRoll`) are available from x4-core v1.3.0. This enables turn rate comparison across all rotation axes, not just pitch.

### Mass Ratio (always available)

$$massRatio = \frac{baseMass + adjustedCargo}{baseMass + originalCargo}$$

This metric now uses **real per-ship cargo capacity** from `ShipDef.getCargoCapacity()` — no hardcoded estimates needed.

## Comparative Evaluation

> **Update (Feb 15, 2026):** The Tier A/B distinction is obsolete. X4 Core v1.3.0 provides complete physics data for all 256 ships. The table below compares the implementation approach against the previous state for reference.

| Criterion | Previous State (before v1.3.0) | Current State (x4-core v1.3.0) |
|---|---|---|
| **Drag data** | 1 of 7 axes (`dragForward` only) | All 7 axes available on `ShipDef` |
| **Inertia data** | 1 of 3 axes (`inertiaPitch` only) | All 3 axes available on `ShipDef` |
| **Jerk data** | Not available (hardcoded placeholders) | All 10 jerk values on `ShipDef` |
| **Acceleration factors** | Not available | All 4 axes on `ShipDef` |
| **Cargo capacity** | Hardcoded estimates per ship size | Real per-ship values via `getCargoCapacity()` |
| **Engine data** | Real forward/reverse/boost/travel from `EngineDef` | Same — unchanged |
| **Top speed accuracy** | Estimated (forward only) | Accurate (all directions possible) |
| **Turn rate accuracy** | Rough (pitch only, no torque) | Good (all 3 axes, still no steering torque) |
| **Implementation effort** | ~12h (tiered services + XML integration + fallbacks) | ~8h (single service + endpoint + frontend) |
| **Prerequisites** | Tier B required `dev-config.php` + extracted XML | None — works immediately |
| **Ships covered** | All 256 (Tier A) / subset with XML (Tier B) | All 256 — universal coverage |
| **Data quality indicators** | Required ("Estimated" / "Accurate") | Not needed — all values are accurate |

## Recommendation

> **Update (Feb 15, 2026):** The previous two-phase recommendation (Tier A partial data → Tier B full XML data) is superseded. X4 Core v1.3.0 provides complete physics data for all ships, enabling a single-phase implementation with accurate values throughout.

**Implement class-wide range display with full physics data from x4-core v1.3.0.**

1. **New `ClassRangeService`** backend service that iterates all ships of a type using `ShipDefs::getInstance()`, computes mass ratios and drag/inertia/jerk adjustments using the full physics data (all 7 drag axes, 3 inertia axes, 10 jerk values, 4 acceleration factors), and returns aggregated ranges.

2. **New `/api/calculate/class-range` endpoint** returning `ClassRangeResponse` with min/max/median per metric + worst/best case ships.

3. **New `ClassRangePanel` frontend component** showing:
   - Range bars for each metric (drag change across all axes, inertia change, jerk change, mass ratio)
   - When engine selected: absolute top speed range in m/s, acceleration in m/s², TWR
   - Worst-case ship highlighted with name and values
   - No data quality disclaimers needed — all values are accurate

4. **Replace hardcoded data in existing services:**
   - Use `ShipDef.getCargoCapacity()` instead of hardcoded cargo estimates in `ShipDataService`
   - Use `EngineDef.getThrustReverse()` / `getBoostThrust()` / `getTravelThrust()` instead of estimated multipliers in `ShipDataService`
   - Use `ShipDef.countEngines()` for engine count in top speed calculations
   - Use real jerk values (`getJerkForwardAccel()`, etc.) instead of placeholder values (drag=100, inertia=10, jerk=50)

### Why Single-Phase Is Now Possible

- **No data gaps remain**: All physics fields previously exclusive to extracted XML (`ShipXMLFile`) are now on `ShipDef` — 7 drag axes, 3 inertia axes, 10 jerk values, 4 acceleration factors, cargo capacity
- **Universal coverage**: Data is available for all 256 ships, not just those with extracted XML on disk
- **No configuration burden**: No `dev-config.php` setup, no `X4_EXTRACTED_CAT_FILES_FOLDER` constant, no `ShipXMLFile` integration in the GUI bootstrap
- **The worst-case ship identification is the killer feature**: A modder sees "At 4x cargo, the Magnetar has a mass ratio of 2049x and will lose 70% drag — top speed jumps from 150 m/s to 500 m/s" and knows immediately that's a problem — now with **accurate** numbers, not estimates

### Proof-of-Concept Outline

1. Create `ClassRangeService.php` in `gui/backend/src/Services/` — iterates `ShipDefs` by class, reads full physics data (all drag/inertia/jerk/accFactor values + `getCargoCapacity()`), computes per-ship mass ratios and % changes, aggregates into `ClassRangeResponse` DTO
2. Create `ClassRangeEndpoint.php` with `POST /api/calculate/class-range` route
3. Create `ClassRangeResponse` DTO with `RangeMetric` and `ShipMetricSummary` sub-types
4. Create `ClassRangePanel.tsx` frontend component with horizontal range bars (min—median—max) using simple divs or recharts
5. Wire into the existing `ConfigPanel` — when slider changes, debounce 500ms then fire class-range request alongside the single-ship calculation
6. Show range panel below the existing results panel

## Open Questions

- **Engine selection for class-wide calculation:** Different ship sizes use different engines. Should the class-wide calculation use "best available engine per size" or require the user to select an engine? The former is more useful (auto-selects representative engines) but requires mapping ship sizes to default engines.
- **Cargo data vs. ship type nuance:** Not all ships within a "transport" class have the same cargo capacity — a Colossus (large freighter) vs. a Magnetar (medium personnel transport with tiny cargo) have wildly different cargo-to-mass ratios. The class-wide range will capture this naturally, but the worst-case ship might always be a special-purpose vessel. Should there be size sub-filters (L transports, M transports, etc.)?
- **Steering curves:** The only physics data still exclusive to extracted game XML is the steering curve (`SteeringCurve` with position/value pairs). This is not needed for the metrics in this design, but if future iterations require steering-based calculations, XML extraction would still be necessary.

## References

- `AdjustedDrag.php` line 18 — confirms $v_{max} = Thrust / Drag$ formula
- `PhysicsService.php` lines 235–248 — existing TWR and acceleration calculations
- `AccelerationAdjustmentTest.php` line 230 — confirms time-to-speed ∝ Mass/AccelFactor
- `ShipDef.php` — x4-core v1.3.0 ship database: complete physics (mass, 7 drag, 3 inertia, 10 jerk, 4 accFactor, cargo capacity/type, engine counts)
- `EngineDef.php` — x4-core engine database (real thrust values for all modes, deceleration curves)
- `ShipXMLFile.php` — still available for steering curve data only; all other physics data now redundant with x4-core v1.3.0
- `ShipDataService.php` lines 301–312, 363–393 — current hardcoded estimates for cargo and engine thrust (should be replaced with x4-core data)
- `CargoSizeExtractor.php` — CLI build system's full physics extraction pipeline
- `build-config.json` — current tier configuration (drag 10–70%, jerk 5–35%, inertia factor 0.5)
- `ships.json` — x4-core v1.3.0 pre-extracted data for all 256 ships including full physics fields
- [Wikipedia: Thrust-to-weight ratio](https://en.wikipedia.org/wiki/Thrust-to-weight_ratio) — standard aerospace metric definition
