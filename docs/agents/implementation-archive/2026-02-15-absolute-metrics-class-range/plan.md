# Plan: Absolute Metrics with Class-Wide Range Display

## Summary

Implement the "Absolute Values with Unit Context" display variant combined with "Best/Worst Case" class-wide range in the Physics Tuning GUI. This replaces the current hardcoded placeholder physics values (drag=100, inertia=10, jerk=50) with real per-ship data from x4-core v1.3.0, adds a new class-wide range endpoint that aggregates metrics across all ships of a type for a given configuration, and introduces frontend components that display absolute estimated metrics (top speed in m/s, acceleration in m/s²) alongside min/max/median range bars and worst/best case ship identification.

## Approach / Architecture

The implementation follows a three-layer approach matching the existing architecture:

### 1. Backend Data Layer Fixes (Foundation)
Replace all hardcoded placeholder values in `PhysicsService` and `ShipDataService` with real per-ship data from x4-core v1.3.0's `ShipDef` and `EngineDef` APIs. This is the prerequisite for all subsequent work — without real data, no metrics are meaningful.

### 2. New Class-Range Backend Service + Endpoint
Create a new `ClassRangeService` that iterates all ships of a given type, computes physics adjustments for each using the current slider configuration, and returns aggregated min/max/median ranges. A new `POST /api/calculate/class-range` endpoint exposes this. New DTOs define the response contract.

### 3. Frontend Display Components
Create a `ClassRangePanel` component that visualizes the aggregated range data using horizontal range bars and absolute metric values. Integrate it into the existing right-side results panel below the current `ResultsPanel`. Add a new `useClassRange` hook for state management and debounced API calls (500ms debounce, longer than the 300ms single-ship debounce).

### Architecture Diagram

```
┌───────────────────────────────────────────────────────┐
│  Frontend                                              │
│                                                        │
│  App.tsx                                               │
│  ├── ShipSelector + ConfigPanel (left panel)           │
│  └── Right Panel                                       │
│      ├── ResultsPanel (existing, now with real data)   │
│      └── ClassRangePanel (NEW)                         │
│          ├── RangeBar (reusable range visualization)   │
│          ├── AbsoluteMetricCard (speed, accel)         │
│          └── WorstCaseShipCard                         │
│                                                        │
│  useClassRange hook (NEW) ──→ classRangeApi (NEW)      │
└───────────────────────────────────────────────────────┘
                     ↓ JSON / HTTP
┌───────────────────────────────────────────────────────┐
│  Backend                                               │
│                                                        │
│  Router.php ──→ ClassRangeEndpoint (NEW)               │
│                     ↓                                  │
│  ClassRangeService (NEW)                               │
│  ├── Iterates ShipDefs by type                         │
│  ├── Reads real physics data (drag, inertia, jerk)     │
│  ├── Applies PhysicsCalculator per ship                │
│  ├── Computes absolute metrics (top speed, accel)      │
│  └── Aggregates into min/max/median ranges             │
│                                                        │
│  DTOs: ClassRangeRequest, ClassRangeResponse,          │
│        RangeMetric, ShipMetricSummary                  │
└───────────────────────────────────────────────────────┘
                     ↓
┌───────────────────────────────────────────────────────┐
│  x4-core v1.3.0                                       │
│  ShipDef  → 7 drag, 3 inertia, 10 jerk, 4 accFactor,│
│             mass, cargoCapacity, countEngines()        │
│  EngineDef → thrustForward/Reverse, boostThrust,      │
│              travelThrust, boostDuration, etc.         │
└───────────────────────────────────────────────────────┘
```

## Rationale

### Why "Absolute Values with Unit Context"?
- X4 modders understand m/s and m/s² — these numbers are immediately meaningful
- No learning curve (unlike box plots which need explanation)
- Self-documenting: "Top Speed: ~412 m/s" needs no interpretation
- Context phrases (e.g., "fast corvette range") can be derived from ship size/class

### Why class-wide range (Best/Worst Case)?
- The critical modding question is "will any ship become unplayable?" — a range display answers this directly
- Identifies the worst-case ship by name, so modders know exactly which edge case to worry about
- Compact display: min—median—max as a horizontal bar takes minimal screen space
- The batch computation (~80 ships × ~10 operations) completes in <10ms on the backend — no performance risk

### Why replace hardcoded values first?
- The existing `PhysicsService` uses `Drag(100, 100, 100, 100, 100, 100, 100)` and `Inertia(10, 10, 10)` as placeholders — all percentage changes displayed are meaningless percentages of arbitrary numbers
- `ShipDataService.getShipCargoCapacity()` returns hardcoded estimates (e.g., L=30000) instead of real per-ship values via `ShipDef.getCargoCapacity()`
- `loadAllEngines()` estimates reverse/boost/travel thrust as multipliers of forward thrust instead of using the real `EngineDef` getters
- Without fixing these, the new class-range feature would also produce meaningless results
- x4-core v1.3.0 provides all the data needed — there's no reason to keep placeholders

### Why a separate ClassRangeService (not extending PhysicsService)?
- Different responsibility: PhysicsService handles single-ship detailed calculation; ClassRangeService handles aggregation across a fleet
- Different performance profile: ClassRangeService needs to be highly optimized for iterating 80+ ships, while PhysicsService focuses on detailed per-ship output
- ClassRangeService can directly use `ShipDef` data without going through the `PhysicsRequest` DTO (which is designed for user-provided values)

## Detailed Steps

### Phase 1: Replace Hardcoded Data with Real x4-core Values

1. **Modify `PhysicsService.calculatePhysics()`** to accept real per-ship drag, inertia, and jerk values instead of creating hardcoded `Drag(100,...)`, `Inertia(10,...)`, and `Jerk(50,...)` objects. The `PhysicsRequest` DTO already has a `baseMass` field; it needs additional fields for the ship's real physics values, OR the service must look them up from a ship ID. The cleanest approach: add an optional `shipId` field to `PhysicsRequest`. When provided, the service loads real physics data from `ShipDef`; when absent (e.g., manual input mode), the current behavior remains as a fallback.

2. **Modify `PhysicsService.calculateEnginePerformance()`** to accept engine count from `ShipDef.countEngines()`. Currently it uses a single engine's thrust; with engine count, the formula becomes `totalThrust = thrustForward * engineCount`. Add `engineCount` as a parameter (default: 1 for backward compatibility). Top speed calculation should be added here: `topSpeed = (thrustForward * engineCount * 1000) / dragForward`.

3. **Modify `ShipDataService.getShipCargoCapacity()`** to use `ShipDef.getCargoCapacity()` instead of the hardcoded `match($size)` fallback. The `getCargoCapacity()` method returns `int` (m³) and returns 0 for ships with no storage connection — handle this by falling back to the hardcoded estimates only when the value is 0.

4. **Modify `ShipDataService.loadAllEngines()`** to use real `EngineDef` getters (`getThrustReverse()`, `getBoostThrust()`, `getTravelThrust()`) instead of estimated multipliers (`thrustForward * 0.5`, `* 2.0`, `* 4.0`).

5. **Update `ShipDataService.getShipDetails()`** to include engine count from `ShipDef.countEngines()` in the `ShipDetails` DTO. Add `engineCount` field to the DTO.

6. **Extend `PhysicsRequest` DTO** with optional `?string $shipId` field and update `fromArray()` to parse it. When present, `PhysicsService` loads the ship's real physics data.

7. **Extend `PhysicsResponse` DTO** to include absolute metrics: `topSpeed` (m/s, original and adjusted, null if no engine selected), `acceleration` (m/s², original and adjusted, null if no engine selected). Add these to `toArray()`.

8. **Extend `EnginePerformance` DTO** to include `topSpeed` (original and adjusted), `topSpeedReverse`, `topSpeedBoost`, `topSpeedTravel`, and engine count used.

9. **Update `ShipDetails` DTO** to include `engineCount: int`, the real drag/inertia/jerk values as nested arrays, and cargo type.

### Phase 2: New Class-Range Backend

10. **Create `ClassRangeRequest` DTO** in `gui/backend/src/DTOs/ClassRangeRequest.php`:
    - `shipType: string` (transport, mining, auxiliary, carrier)
    - `cargoMultiplier: float`
    - `dragReductionTiers: array`
    - `jerkReductionTiers: array`
    - `inertiaImpactFactor: float`
    - `useEffectiveRatioCap: bool`
    - `dragReductionFactor: float`
    - `accelerationResponsiveness: float`
    - `engineId: ?string` (optional — for engine-dependent metrics)
    - `fromArray()` static factory

11. **Create `RangeMetric` DTO** in `gui/backend/src/DTOs/RangeMetric.php`:
    - `min: float`
    - `max: float`
    - `median: float`
    - `unit: string` (m/s, m/s², %, ratio)
    - `label: string` (human-readable name)
    - `toArray()` serializer

12. **Create `ShipMetricSummary` DTO** in `gui/backend/src/DTOs/ShipMetricSummary.php`:
    - `shipId: string`
    - `shipName: string`
    - `size: string`
    - `massRatio: float`
    - `topSpeed: ?array{original: float, adjusted: float}`
    - `acceleration: ?array{original: float, adjusted: float}`
    - `dragChangePercent: float` (forward axis % change — most impactful)
    - `toArray()` serializer

13. **Create `ClassRangeResponse` DTO** in `gui/backend/src/DTOs/ClassRangeResponse.php`:
    - `shipCount: int`
    - `metrics: array` containing `RangeMetric` objects for: `topSpeed`, `acceleration`, `dragChange`, `inertiaChange`, `jerkChange`, `massRatio`
    - `worstCase: ShipMetricSummary`
    - `bestCase: ShipMetricSummary`
    - `toArray()` serializer

14. **Create `ClassRangeService` service** in `gui/backend/src/Services/ClassRangeService.php`:
    - Constructor dependency: `ShipDataService` (to reuse ship loading logic)
    - `calculateClassRange(ClassRangeRequest $request): ClassRangeResponse` main method
    - Internal: iterate all ships of requested type from `ShipDefs`, for each ship:
      a. Create `PhysicsCalculator` with real mass + cargo data
      b. Find applicable drag/jerk tiers for the given multiplier
      c. Compute drag/inertia/jerk adjustments using real per-ship values
      d. If engine selected: compute top speed = `(thrustForward * engineCount * 1000) / dragForward`
      e. If engine selected: compute acceleration = `(thrustForward * engineCount * 1000) / adjustedFullMass`
      f. Collect all per-ship results into arrays
    - After iteration: compute min/max/median for each metric
    - Identify worst-case ship (highest mass ratio) and best-case ship (lowest mass ratio)
    - Return `ClassRangeResponse`
    - `computeMedian(array $values): float` private helper

15. **Create `ClassRangeEndpoint`** in `gui/backend/src/API/Endpoints/ClassRangeEndpoint.php`:
    - `calculate()` method handling `POST /api/calculate/class-range`
    - Parse request body → `ClassRangeRequest::fromArray()`
    - Call `ClassRangeService::calculateClassRange()`
    - Return JSON response

16. **Register the new route** in `Router.php`:
    - `$app->post('/api/calculate/class-range', [$classRangeEndpoint, 'calculate']);`

### Phase 3: Frontend Type Definitions

17. **Add TypeScript types** in `gui/frontend/src/types/physics.d.ts`:
    - `ClassRangeRequest` interface
    - `RangeMetric` interface
    - `ShipMetricSummary` interface
    - `ClassRangeResponse` interface

18. **Update `ShipDetails` interface** in `gui/frontend/src/types/ships.d.ts`:
    - Add `engineCount: number`
    - Add `cargoType: string`

19. **Update `EnginePerformance` interface** in `gui/frontend/src/types/physics.d.ts`:
    - Add `originalTopSpeed: number | null`
    - Add `adjustedTopSpeed: number | null`
    - Add `engineCount: number`

20. **Update `PhysicsResponse` interface** to include the new absolute metric fields returned by the updated backend.

### Phase 4: Frontend API Client & Hook

21. **Add class-range API method** to `gui/frontend/src/services/api.ts`:
    - `classRangeApi.calculate(request: ClassRangeRequest): Promise<ClassRangeResponse>`
    - `POST /calculate/class-range` endpoint call

22. **Create `useClassRange` hook** in `gui/frontend/src/hooks/useClassRange.ts`:
    - State: `result: ClassRangeResponse | null`, `loading: boolean`, `error: string | null`
    - `calculate(request: ClassRangeRequest): void` — debounced at 500ms (longer than single-ship 300ms since this is a heavier computation)
    - `reset(): void` — clear state
    - Return type: `UseClassRangeResult`

### Phase 5: Frontend Components

23. **Create `RangeBar` component** in `gui/frontend/src/components/UI/RangeBar.tsx`:
    - Reusable horizontal bar showing min—median—max with labeled markers
    - Props: `min: number`, `max: number`, `median: number`, `unit: string`, `label: string`, `highlightValue?: number` (optional — to highlight the selected ship's value within the range)
    - Render as a horizontal div with positioned markers (pure CSS/Tailwind, no chart library needed)
    - Display absolute values at min/max endpoints and median marker

24. **Create `AbsoluteMetricCard` component** in `gui/frontend/src/components/ResultsPanel/AbsoluteMetricCard.tsx`:
    - Displays a single absolute metric with original → adjusted comparison
    - Props: `label: string`, `originalValue: number`, `adjustedValue: number`, `unit: string`, `contextPhrase?: string`
    - Context phrase examples: for top speed values, show size-appropriate context like "fast freighter range" or "sluggish hauler range" derived from ranges
    - Shows delta: "+15.2%" or "-8.3%"

25. **Create `WorstCaseCard` component** in `gui/frontend/src/components/ResultsPanel/WorstCaseCard.tsx`:
    - Displays worst-case and best-case ship identification
    - Props: `worstCase: ShipMetricSummary`, `bestCase: ShipMetricSummary`, `engineSelected: boolean`
    - Shows ship name, size, mass ratio, and if engine selected: top speed original → adjusted
    - Visual emphasis on worst case (warning color) vs best case (success color)

26. **Create `ClassRangePanel` component** in `gui/frontend/src/components/ResultsPanel/ClassRangePanel.tsx`:
    - Container component that combines `RangeBar` instances for each metric + `WorstCaseCard`
    - Props: `data: ClassRangeResponse | null`, `loading: boolean`, `error: string | null`, `engineSelected: boolean`
    - When loading: show skeleton/spinner
    - When no data: show "Select a ship type and adjust settings to see class-wide impact"
    - Renders:
      - Section header: "Class Impact: {shipCount} {shipType} ships at {multiplier}x"
      - `RangeBar` for mass ratio (always shown)
      - `RangeBar` for drag change % (always shown)
      - `RangeBar` for inertia change % (always shown)
      - `RangeBar` for jerk change % (always shown)
      - If engine selected: `RangeBar` for top speed (m/s), acceleration (m/s²)
      - `WorstCaseCard` for worst/best case ships

27. **Update `ResultsPanel.tsx`** to:
    - Accept new optional `classRangeData` and classRange loading/error props
    - Render `ClassRangePanel` below the existing result sections

### Phase 6: Wiring & Integration

28. **Update `App.tsx`** to:
    - Import and use the `useClassRange` hook
    - Add state for the selected ship type (needed for class-range API; may already be available in `ShipSelector` callbacks)
    - In the calculation trigger effect, also fire the class-range calculation alongside the single-ship calculation
    - Build a `ClassRangeRequest` from the current config + selected ship type + multiplier
    - Pass class-range results down to `ResultsPanel`

29. **Update the `handleShipDetailsChange` callback** in `App.tsx` to also capture the ship type for the class-range request.

30. **Add a `shipId` field to the `PhysicsConfig` request** constructed in `triggerCalculation` (so the backend can load real per-ship physics data).

### Phase 7: Context Phrases for Absolute Values

31. **Add context phrase logic** to the frontend (utility function in a new `gui/frontend/src/utils/metricContext.ts`):
    - `getSpeedContext(speedMs: number, shipSize: string): string` — returns phrases like:
      - < 100 m/s: "very slow — heavy hauler"
      - 100–200 m/s: "typical freighter speed"
      - 200–350 m/s: "corvette range"
      - 350–500 m/s: "fast fighter territory"
      - > 500 m/s: "extremely fast — possibly unbalanced"
    - `getAccelerationContext(accelMs2: number, shipSize: string): string` — returns phrases like:
      - < 5 m/s²: "sluggish response"
      - 5–20 m/s²: "standard acceleration"
      - 20–50 m/s²: "nimble"
      - > 50 m/s²: "fighter-like responsiveness"
    - `getMassRatioContext(ratio: number): string` — returns phrases like:
      - < 1.5: "minimal mass increase"
      - 1.5–3.0: "noticeable but manageable"
      - 3.0–10.0: "significant — will affect handling"
      - > 10.0: "extreme — may feel unresponsive"

### Phase 8: Update Existing Single-Ship Results

32. **Update `PhysicsOverview` and `ComparisonView` components** to display the new absolute metric fields from the updated `PhysicsResponse` (top speed, acceleration) when an engine is selected. Use `AbsoluteMetricCard` components alongside the existing percentage-based comparisons.

33. **Update `EnginePerformanceDisplay` component** to show top speed (original vs adjusted) in m/s alongside the existing TWR display. Show for all flight modes (normal, boost, travel) when data is available.

## Dependencies

- **x4-core v1.3.0** must be installed as a Composer dependency (already available — `ShipDef` has all required getter methods confirmed)
- **Existing parent mod classes**: `PhysicsCalculator`, `AdjustedDrag`, `AdjustedInertia`, `AdjustedJerk`, `ReductionTier` (already used by `PhysicsService`)
- **Existing UI components**: `Card`, `Spinner` from `gui/frontend/src/components/UI/`
- Steps 1–9 (Phase 1: data fixes) must be completed before Phase 2 (class-range service)
- Steps 10–16 (Phase 2: backend) must be completed before Phase 4–5 (frontend)
- Steps 17–20 (Phase 3: types) must be completed before Phase 4–5
- Phase 6 (wiring) depends on Phases 4–5
- Phase 7–8 can run in parallel with Phase 6

## Required Components

### New Backend Files
- `gui/backend/src/DTOs/ClassRangeRequest.php` — Request DTO
- `gui/backend/src/DTOs/ClassRangeResponse.php` — Response DTO
- `gui/backend/src/DTOs/RangeMetric.php` — Range metric sub-DTO
- `gui/backend/src/DTOs/ShipMetricSummary.php` — Ship summary sub-DTO
- `gui/backend/src/Services/ClassRangeService.php` — Class range calculation service
- `gui/backend/src/API/Endpoints/ClassRangeEndpoint.php` — API endpoint

### Modified Backend Files
- `gui/backend/src/Services/PhysicsService.php` — Replace hardcoded values with real data
- `gui/backend/src/Services/ShipDataService.php` — Use real cargo/engine data
- `gui/backend/src/DTOs/PhysicsRequest.php` — Add `shipId` field
- `gui/backend/src/DTOs/PhysicsResponse.php` — Add absolute metric fields
- `gui/backend/src/DTOs/EnginePerformance.php` — Add top speed fields
- `gui/backend/src/DTOs/ShipDetails.php` — Add `engineCount`, physics data, `cargoType`
- `gui/backend/src/API/Router.php` — Register new route

### New Frontend Files
- `gui/frontend/src/hooks/useClassRange.ts` — Class range hook
- `gui/frontend/src/components/UI/RangeBar.tsx` — Reusable range visualization
- `gui/frontend/src/components/ResultsPanel/AbsoluteMetricCard.tsx` — Absolute metric display
- `gui/frontend/src/components/ResultsPanel/WorstCaseCard.tsx` — Worst/best case ship display
- `gui/frontend/src/components/ResultsPanel/ClassRangePanel.tsx` — Container panel
- `gui/frontend/src/utils/metricContext.ts` — Context phrase utility functions

### Modified Frontend Files
- `gui/frontend/src/types/physics.d.ts` — New types + updated interfaces
- `gui/frontend/src/types/ships.d.ts` — Updated ShipDetails
- `gui/frontend/src/services/api.ts` — New API method
- `gui/frontend/src/hooks/usePhysicsCalculation.ts` — Pass shipId in config
- `gui/frontend/src/components/ResultsPanel/ResultsPanel.tsx` — Include ClassRangePanel
- `gui/frontend/src/components/ResultsPanel/PhysicsOverview.tsx` — Show absolute metrics
- `gui/frontend/src/components/ResultsPanel/ComparisonView.tsx` — Show absolute metrics
- `gui/frontend/src/components/ResultsPanel/EnginePerformanceDisplay.tsx` — Show top speed
- `gui/frontend/src/App.tsx` — Wire class-range hook, pass data down

### Manifest Documents to Update
- `gui/docs/project-manifest/public-api.md` — New service, DTOs, endpoint, hook, components
- `gui/docs/project-manifest/file-tree.md` — New files
- `gui/docs/project-manifest/data-flows.md` — Class-range calculation flow
- `gui/docs/project-manifest/tech-stack.md` — New architectural pattern (class-wide aggregation)
- `gui/docs/project-manifest/constraints.md` — If any new constraints emerge
- `gui/docs/API.md` — New endpoint documentation

## Assumptions

- x4-core v1.3.0 is already installed as a Composer dependency in the parent project (confirmed by the research paper and verified ShipDef API)
- `ShipDef.getCargoCapacity()` returns 0 for ships without cargo storage (e.g., fighters) — the fallback to size-based estimates should only apply when the value is 0
- The PHP autoloader is configured to load `gui/backend/src/` classes via Composer's PSR-4 mapping (existing convention)
- The top speed formula `v_max = (Thrust * EngineCount) / Drag` is a reasonable approximation for X4's physics model (confirmed in `AdjustedDrag.php` line 18 comment)
- Engine selection for class-wide calculation uses the user-selected engine from the ShipSelector, not auto-selected per-size engines (simpler UX; the user already selects an engine for single-ship calc)
- Context phrases for absolute values are approximate and can be tuned based on user feedback — they don't need to be perfect for v1

## Constraints

- **Synchronous file I/O only** — all backend operations must use synchronous PHP functions (project constraint)
- **No database** — all data comes from x4-core's in-memory ship/engine definitions (JSON-backed)
- **<500ms user feedback target** — the class-range calculation adds a second API call; using a 500ms debounce + <100ms backend computation keeps it within acceptable latency
- **TypeScript strict mode** — all new frontend code must pass `strict: true` compilation
- **PHP strict types** — every new PHP file must start with `declare(strict_types=1)`
- **Readonly DTOs** — all new DTO properties must be `readonly`
- **PSR-12 coding standard** — PHP code follows PSR-12
- **Existing patterns** — follow Service Layer, DTO, Hook, and Component Composition patterns established in the codebase
- **No external dependencies** — no new npm or Composer packages should be added for this feature (recharts is already available for any chart needs, but simple Tailwind CSS divs are preferred for range bars)

## Out of Scope

- **Steering curve integration** — `SteeringCurve` data remains XML-only in x4-core; this plan doesn't need it for the metrics being implemented
- **Auto-selecting engines per ship size** — the open question from the research paper about "best available engine per size" is deferred; the user-selected engine is used for all class-wide calculations
- **Size sub-filters for class range** — the research paper asks whether to filter by ship size within a class (e.g., "L transports only"); this is deferred to a future iteration. The initial range shows all ships in the class, and the worst-case identification helps the user understand outliers
- **Production deployment concerns** — no auth, SSL, or multi-user features
- **Unit tests** — test coverage for the new service and components is desirable but not part of this initial plan scope (backend tests are "not yet implemented" per manifest)
- **Turn rate in deg/s** — absolute turn rate requires steering torque data not available in x4-core; only relative turn rate change (%) is shown
- **Cargo type filtering** — the class-range shows all cargo-carrying ships regardless of cargo type (container, liquid, solid); filtering by cargo type is a future enhancement

## Acceptance Criteria

1. **Real data everywhere**: `PhysicsService` uses real per-ship drag, inertia, and jerk values from `ShipDef` when a ship is selected — no more hardcoded `Drag(100,...)` etc.
2. **Real engine data**: `ShipDataService.loadAllEngines()` returns real reverse/boost/travel thrust values from `EngineDef`, not estimated multipliers.
3. **Real cargo capacity**: `ShipDataService.getShipCargoCapacity()` returns `ShipDef.getCargoCapacity()` for ships where it's available (>0).
4. **Top speed displayed**: When an engine is selected, the single-ship `ResultsPanel` shows estimated top speed (original vs adjusted) in m/s.
5. **Acceleration displayed**: When an engine is selected, estimated acceleration in m/s² is shown (original vs adjusted).
6. **Class-range endpoint works**: `POST /api/calculate/class-range` accepts a ship type + config and returns min/max/median ranges for all relevant metrics.
7. **Class-range panel visible**: The `ClassRangePanel` appears in the right panel below the single-ship results and updates reactively when sliders change.
8. **Range bars displayed**: Each metric shows a horizontal min—median—max range bar with labeled absolute values.
9. **Worst/best case ships identified**: The worst-case and best-case ships are shown by name with their key metrics.
10. **Context phrases**: Absolute metric values include brief contextual phrases (e.g., "typical freighter speed").
11. **Performance target met**: Class-range API responds in <100ms; total user feedback (debounce + API + render) is <600ms.
12. **Engine-dependent metrics gated**: Top speed, acceleration, and TWR ranges only appear when an engine is selected; a clear message explains this when no engine is selected.
13. **No new dependencies**: Implementation uses only existing npm/Composer packages.
14. **Manifest updated**: All relevant manifest documents are updated to reflect new files, APIs, flows, and types.

## Testing Strategy

### Backend Testing
- **Unit test `ClassRangeService`**: Verify that iterating a small set of known ships produces correct min/max/median aggregations. Mock `ShipDefs` if possible, or use real data with known expected ranges.
- **Unit test `PhysicsService` with real data**: Verify that when a `shipId` is provided, real drag/inertia/jerk values are used instead of placeholders. Compare output against manual calculation.
- **Integration test `ClassRangeEndpoint`**: HTTP test sending a `ClassRangeRequest` JSON body and validating the response structure matches `ClassRangeResponse`.
- **Edge case: ship with zero cargo**: Verify that ships where `getCargoCapacity()` returns 0 don't cause division-by-zero errors.
- **Edge case: no engine selected**: Verify that engine-dependent metrics (top speed, acceleration) are null/omitted when no engine ID is provided.

### Frontend Testing
- **Component rendering**: Verify `ClassRangePanel` renders correctly in all states (loading, error, no data, data with engine, data without engine).
- **`RangeBar` rendering**: Verify correct positioning of min/median/max markers for various value distributions.
- **Context phrases**: Verify `getSpeedContext()` returns appropriate phrases for boundary values.
- **Integration**: Verify that slider changes trigger both the single-ship and class-range API calls with correct debounce timing.

### Manual Testing
- Start both dev servers (`composer gui:start-win`)
- Select a Transport ship, select an engine, set multiplier to 4x
- Verify: single-ship results show real drag/inertia/jerk values (not 100/10/50)
- Verify: top speed and acceleration appear in m/s and m/s²
- Verify: class-range panel appears below results with range bars
- Verify: worst-case ship is identified and makes intuitive sense
- Adjust drag reduction slider → verify both panels update within ~600ms
- Remove engine selection → verify top speed/acceleration fields hide, replaced by a "Select an engine" message
- Test all 4 ship types (transport, mining, auxiliary, carrier)

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **`ShipDef.getCargoCapacity()` returns 0 for many ships** | Fall back to size-based estimates only when value is 0; log a warning for monitoring |
| **Class-range for large ship classes exceeds 100ms** | Pre-tested: ~80 ships × ~10 ops = ~800 ops is <10ms in PHP. If somehow slow, add pagination or cache results per config hash |
| **Engine count varies per ship, affecting top speed accuracy** | Use `ShipDef.countEngines()` for per-ship engine count; clearly label metrics as "estimated" since not all engine slots may be filled in-game |
| **Worst-case ship is always a niche ship (e.g., personnel transport with tiny cargo)** | This is actually the intended behavior — the worst case IS the interesting case. The range bars show the distribution so the user can see if most ships are fine |
| **Two parallel API calls per slider change (single + class-range) may cause racing** | Separate hooks with independent state; class-range uses longer debounce (500ms vs 300ms); no shared mutable state between the two |
| **Breaking change in `PhysicsResponse` format** | Add new fields as optional/nullable to maintain backward compatibility; existing fields remain unchanged |
| **Context phrases are inaccurate for edge cases** | Label them as "approximate context" in tooltips; allow easy tuning of thresholds in the utility function |
| **Frontend bundle size increase** | New components are small (mostly Tailwind + simple divs); recharts is already in the bundle. Estimate <10KB gzipped increase |
