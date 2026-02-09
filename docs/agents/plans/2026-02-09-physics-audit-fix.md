# Plan: Physics Calculation Audit & Fix

## Summary
The goal is to fix the faulty flight physics in the Cargo Sizes mod, specifically addressing the inability of player-piloted ships to enter travel mode when using large cargo multipliers (e.g., 10x). The current implementation reduces acceleration and drag (attempting to keep the ship "light"), which paradoxically makes the ship too sluggish to reach travel mode thresholds due to the game engine utilizing the actual, much heavier cargo mass. The fix will invert this logic: instead of reducing values, we will scale Thrust and Drag **up** proportionally to the increased mass, maintaining the ship's original acceleration and top speed characteristics while allowing it to carry the heavier load.

## Approach / Architecture
1.  **Physics Model Inversion**: Switch from a "Reduction Model" (reducing drag/accel to compensate) to a "Scaling Model" (increasing thrust/drag to match mass).
2.  **Tunable Compensation**: Introduce a `compensationEfficiency` factor (e.g. 0.9) to allow ships to feel "heavier" than vanilla but still functional, rather than aiming for perfect parity.
3.  **Mass Estimation Upgrade**: Leverage `x4-core` to calculate a more accurate "Operational Mass" (Hull + Standard Components + Cargo) rather than just (Hull + Cargo), ensuring the scaling factor is precise.
4.  **Safety & Stability Capping**: Implement configuration caps to prevent physics values from exceeding engine stability limits (e.g., prevent 10x drag from causing physics glitches).
5.  **Audit Tooling**: Create a build-time audit script that simulates "Time to Travel Speed" for ships to verify pilotability before releasing.

## Rationale
- **Current Flaw**: The current logic reduces acceleration and drag to calculate a multiplier < 1.0. With mass actually increasing 10x in-game, the effective acceleration ($F/m$) becomes negligible. During the Travel Mode "Ramp-up" phase, this results in the ship failing to gain any significant speed, making it appear broken.
- **Proposed Fix**: By scaling Thrust by the calculated Mass Factor (e.g., 4x), we increase the force available to move the increased mass. Formula: $F_{new} \approx F_{base} \times Ratio$.
- **Pilotability Goal**: We do not need perfect 1:1 agility retention. As long as $F_{new}$ is sufficient to overcome drag and reach the travel threshold, the ship is pilotable. The user accepts "heavier" handling (lower responsiveness), so we can tune the scaling to be slightly less than linear (e.g., 90% compensation) to simulate weight.
- **Linear Drag Confirmation**: Research confirms the game uses a Newtonian-lite model where drag is linear ($F_d = c \cdot v$), validating that linear scaling of drag coefficients will correctly offset mass increases.

## Detailed Steps

### Phase 1: Analysis & Tooling
1.  **Create Physics Audit Script**: 
    -   Create `src/Mods/CargoSizesMod/Build/Tools/PhysicsAudit.php`.
    -   Use `x4-core` to iterate all L/XL ships.
    -   Extract Hull Mass and standard Engine thrust values.
    -   Calculate the "Mass Ratio" for 2x, 5x, 10x multipliers.
    -   **Audit Metric**: Calculate **Effective Acceleration** ($F/m$) for Standard and Travel modes. Compare Modded vs Vanilla.
    -   **Pass Criteria**: Modded acceleration must be within acceptable variance (e.g. +/- 20%) of Vanilla acceleration. Existing logic likely shows ~10% or less.

### Phase 2: Logic Implementation
2.  **Refactor `MassAdjustment`**:
    -   Rename/Refactor to `PhysicsCompensator`.
    -   Change the multiplier formula to use an "Efficiency" factor: `Ratio = AdjustedFullMass / OriginalFullMass`; `Factor = 1 + (Ratio - 1) * Efficiency`.
    -   Implement "Operational Mass" heuristic (add ~20-30% padding to Hull Mass to account for equipment if precise component data is unavailable).
3.  **Update `FlightMechanicsOverrideFile`**:
    -   **Acceleration**: `New = Old * Factor`.
    -   **Drag**: `New = Old * Factor` (Linear scaling).
    -   **Inertia**: `New = Old * Factor` (Matches mass increase).
    -   **Jerk**: `New = Old * Factor` (Maintains responsiveness).
    -   **Steering**: Apply a dampened factor (e.g., `1 + (Factor-1)*0.5`) to allow heavy ships to feel slightly heavier/less agile, but still turnable.
4.  **Implement Configuration**:
    -   Update `BuildConfig` to include `maxPhysicsMultiplier` (default e.g., 5.0) and `compensationEfficiency` (default e.g. 0.9).
    -   Apply these caps/modifiers to the calculated Factor.

### Phase 3: Validation
5.  **Run Audit Script with New Logic**: Validate that "Time to Travel Speed" returns to near-vanilla values.
6.  **Generate Test Build**: Create a mod package with the new logic.

## Dependencies
-   **mistralys/x4-core**: For access to ship/engine database.
-   **mistralys/x4-mod-cargo-sizes**: Existing codebase.

## Required Components
-   `src/Mods/CargoSizesMod/Output/MassAdjustment.php` (Modify)
-   `src/Mods/CargoSizesMod/Output/PhysicsCompensator.php` (New)
-   `src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php` (Modify)
-   `src/Mods/CargoSizesMod/Build/Tools/PhysicsAudit.php` (New)
-   `config/build-config.json` (Update schema)

## Assumptions
- Travel Mode is a state transition that applies specific thrust/drag modifiers, not a speed check.
- The player's inability to gain speed is due to negligible acceleration ($F/m$) during the ramp-up phase.
- `x4-core` contains sufficient data to identify ship hull mass and engine types.

## Constraints
-   **Filesystem I/O**: Synchronous only.
-   **Performance**: Audit script runs at build time, so pure performance is less critical than accuracy.
-   **Configurability**: Must preserve user ability to tune factors via `build-config.json`.

## Out of Scope
-   Modifying individual engine files (global change).
-   Changing cargo capacity logic itself.

## Acceptance Criteria
-   **Travel Mode**: Player ships with 10x cargo can successfully enter travel mode and accelerate.
-   **Speed**: Max speed of loaded ships remains roughly comparable to vanilla (within +/- 20%).
-   **Agility**: Ships feel heavy but respond to controls (yaw/pitch).
-   **Safety**: No physics glitches (nan/infinite acceleration) in extreme configurations.

## Testing Strategy
1.  **Unit Tests**: Test `PhysicsCompensator` with various inputs to verify the math (2x mass = 2x thrust).
2.  **Audit Simulation**: The `PhysicsAudit` script acts as a regression test.
3.  **Manual Flight Test**: Generate mod, spawn `Shuyaku Vanguard` with 10x cargo, fill it, and fly.

## Risks & Mitigations
| Risk | Mitigation |
|------|------------|
| **Overscaling** | Ships become "rockets" if Empty. | **Mitigation**: The calculated Factor includes Cargo. If Empty, the mass is low. Wait. The Logic overrides the *Ship Base Stats*. We cannot dynamically adjust stats based on current cargo in-game. **CRITICAL MITIGATION**: We optimize for the *Full Load* scenario to ensure pilotability. When empty, the ship *will* be overpowered (High Thrust / Low Mass). This is an unavoidable trade-off of static XML modding. We can dampen the factor (e.g. `Factor = 1 + (Ratio-1)*0.7`) to strike a balance, or accept that "Empty = Fast, Full = Normal". |
| **Physics Instability** | 10x Drag values might cause oscillation. | **Mitigation**: Implement `maxPhysicsMultiplier` cap (e.g. 5x) in config. |
