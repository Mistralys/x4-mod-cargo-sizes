# Data Flows & System Interactions

> **Version:** 1.0  
> **Last Updated:** February 12, 2026  
> **Purpose:** How data moves through the system

---

## Table of Contents

1. [Overview](#overview)
2. [Physics Calculation Flow](#physics-calculation-flow)
3. [Ship Data Retrieval Flow](#ship-data-retrieval-flow)
4. [Configuration Management Flow](#configuration-management-flow)
5. [User Interaction Patterns](#user-interaction-patterns)
6. [Error Handling Flow](#error-handling-flow)
7. [Performance Optimization Flows](#performance-optimization-flows)

---

## Overview

This document describes how data flows through the Physics Tuning GUI from user interaction to API response and back to UI updates.

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      User Interaction                        │
│  (Adjust slider, select ship, change tier config)           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    React Component                           │
│  (ConfigPanel, ShipSelector, ResultsPanel)                  │
│  • Captures user input                                      │
│  • Updates local state                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                     Custom Hook                              │
│  (usePhysicsCalculation, useShipData, useConfig)           │
│  • Debounces calls (300ms for physics)                      │
│  • Manages loading/error states                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    API Client Service                        │
│  (api.ts - physicsApi, shipsApi, configApi)                │
│  • Axios HTTP client                                        │
│  • JSON serialization                                       │
└─────────────────────────────────────────────────────────────┘
                            ↓ HTTP/JSON
┌─────────────────────────────────────────────────────────────┐
│                      Backend API                             │
│  (Slim Framework + Endpoints)                               │
│  • CORS middleware                                          │
│  • Route to appropriate endpoint                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                     Service Layer                            │
│  (PhysicsService, ShipDataService, ConfigService)          │
│  • Validates input DTOs                                      │
│  • Delegates to business logic                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                   Business Logic Layer                       │
│  (PhysicsCalculator, X4 Core library)                       │
│  • Performs calculations                                     │
│  • Accesses game data                                        │
│  • Reads/writes config files                                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      Response DTOs                           │
│  (PhysicsResponse, ShipDetails, ValidationResult)           │
│  • Type-safe response construction                          │
│  • Serialization to JSON                                    │
└─────────────────────────────────────────────────────────────┘
                            ↓ JSON Response
┌─────────────────────────────────────────────────────────────┐
│                     React Component                          │
│  (ResultsPanel, ShipSelector)                               │
│  • Updates UI with results                                   │
│  • Displays charts, comparisons, metrics                    │
└─────────────────────────────────────────────────────────────┘
```

---

## Physics Calculation Flow

### Flow Diagram

```
User Adjusts Slider (Drag Reduction)
        ↓
ConfigPanel Component
        ↓ onChange event
Update React State (dragReductionFactor)
        ↓
usePhysicsCalculation Hook
        ↓ calculate() called
Clear Existing Debounce Timer
        ↓
Set Loading State (true)
        ↓
Start 300ms Debounce Timer
        ↓ ... wait 300ms ...
        ↓
Debounce Timer Fires
        ↓
Construct PhysicsConfig Object
        {
          baseMass: 500.0,
          originalCargo: 30000.0,
          adjustedCargo: 120000.0,  // 4x multiplier
          cargoMultiplier: 4.0,
          dragReductionFactor: 0.7,  // User adjusted this
          inertiaImpactFactor: 0.5,
          // ... all other parameters
        }
        ↓
physicsApi.calculate(config)
        ↓ Axios POST request
Backend: POST /api/calculate/physics
        ↓
CorsMiddleware (add CORS headers)
        ↓
PhysicsEndpoint::calculate()
        ↓ Parse request body
PhysicsRequest DTO
        ↓ fromArray()
Validate Input (types, ranges)
        ↓
PhysicsService::calculatePhysics()
        ↓
Create PhysicsCalculator Instance
        new PhysicsCalculator(
          baseMass,
          originalCargo,
          adjustedCargo,
          cargoMultiplier,
          useEffectiveRatioCap
        )
        ↓
Calculate Mass Ratio
        massRatio = adjustedFullMass / originalFullMass
        effectiveRatio = cap if enabled
        ↓
Find Appropriate Tiers
        dragTier = findTierForMultiplier(dragReductionTiers, 4.0)
        jerkTier = findTierForMultiplier(jerkReductionTiers, 4.0)
        ↓
Apply Drag Adjustments
        adjustedDrag = new AdjustedDrag(originalDrag, dragTier.reductionPercent)
        ↓
Apply Inertia Adjustments
        inertiaMultiplier = 1.0 + ((massRatio - 1.0) * inertiaImpactFactor)
        adjustedInertia = new AdjustedInertia(originalInertia, inertiaMultiplier)
        ↓
Apply Jerk Adjustments
        jerkMultiplier = inverseMassRatio * (1.0 - jerkTier.reductionPercent)
        adjustedJerk = new AdjustedJerk(originalJerk, jerkMultiplier)
        ↓
Calculate Engine Performance (if engineId provided)
        enginePerformance = calculateEnginePerformance(engineId, masses)
        ↓
Construct PhysicsResponse DTO
        new PhysicsResponse(
          massRatio,
          effectiveRatio,
          dragOriginal,
          dragAdjusted,
          dragPercentChange,
          inertiaOriginal,
          inertiaAdjusted,
          // ... all calculated values
        )
        ↓
Serialize to JSON
        $response->withJson($physicsResponse->toArray())
        ↓ HTTP 200 OK
        {
          "massRatio": 1.3,
          "effectiveRatio": 1.3,
          "drag": {
            "original": { "forward": 100.0, ... },
            "adjusted": { "forward": 85.0, "forwardPercent": -15.0, ... },
            ...
          },
          "inertia": { ... },
          "jerk": { ... },
          "enginePerformance": { ... }
        }
        ↓
Frontend: Axios Receives Response
        ↓
usePhysicsCalculation Hook
        ↓ response.data
Set Result State (PhysicsResponse)
        ↓
Set Loading State (false)
        ↓
React Re-renders
        ↓
ResultsPanel Component
        ↓ Displays results
Show Mass Ratio Display
Show Drag Comparison Chart (original vs adjusted)
Show Inertia Comparison
Show Engine Performance Card
        ↓
User Sees Results (<500ms total)
```

### Timeline

| Step | Time | Cumulative |
|------|------|------------|
| User adjusts slider | 0ms | 0ms |
| React state update | ~5ms | 5ms |
| Debounce starts | instant | 5ms |
| **Debounce wait** | 300ms | 305ms |
| API call sent | instant | 305ms |
| Backend processing | ~50-100ms | 355-405ms |
| JSON serialization | ~5ms | 360-410ms |
| Network transfer (localhost) | ~1-5ms | 361-415ms |
| Frontend receives response | instant | 361-415ms |
| React re-render | ~5-10ms | 366-425ms |
| **User sees results** | **366-425ms** | **~400ms avg** |

**Target:** <500ms (✅ Achieved)

---

## Ship Data Retrieval Flow

### Get Ships by Type

```
User Selects Ship Type Dropdown (e.g., "Transport")
        ↓
ShipSelector Component
        ↓ onChange event
useShipData Hook
        ↓ fetchShipsByType("transport")
shipsApi.getShipsByType("transport")
        ↓ Axios GET
Backend: GET /api/ships/transport
        ↓
ShipsEndpoint::getShipsByType()
        ↓
ShipDataService::getShipsByType("transport")
        ↓
Map Type to Extractor Constant
        "transport" → CargoSizeExtractor::SHIP_TYPE_TRANSPORT
        ↓
Query Sample Ships (TODO: Use extracted game data)
        [
          {id: "ship_arg_l_trans_container_01_a", name: "Colossus", ...},
          {id: "ship_arg_m_trans_container_01_a", name: "Mercury", ...},
          ...
        ]
        ↓
Return Array of ShipInfo
        ↓ JSON Response
Frontend Receives Ship List
        ↓
useShipData Hook Updates State
        ships: ShipInfo[]
        ↓
React Re-renders
        ↓
ShipList Component
        ↓
Display Ships in Table
        | Name       | Size | Mass   | Cargo   |
        |------------|------|--------|---------|
        | Colossus   | L    | 500.0  | 30000   |
        | Mercury    | M    | 200.0  | 12000   |
        ↓
User Clicks Ship Row
        ↓ (triggers fetchShipDetails)
```

### Get Ship Details

```
User Clicks on Ship in List
        ↓
ShipSelector Component
        ↓ onSelectShip(shipId)
useShipData Hook
        ↓ fetchShipDetails(shipId)
shipsApi.getDetails(shipId)
        ↓ Axios GET
Backend: GET /api/ships/details/{shipId}
        ↓
ShipsEndpoint::getDetails()
        ↓
ShipDataService::getShipDetails(shipId)
        ↓
Query X4 Core ShipDefs
        ShipDefs::getInstance()->getByID(shipId)
        ↓
Extract Ship Properties
        - Name (from shipDef.getLabel())
        - Mass (from shipDef.getMass())
        - Size (from ID parsing)
        - Cargo (placeholder for now)
        ↓
Get Compatible Engines
        getEnginesForShip(shipId)
        ↓
Construct ShipDetails DTO
        new ShipDetails(
          id: shipId,
          name: "Colossus Vanguard",
          type: "transport",
          size: "l",
          mass: 500.0,
          cargo: 30000.0,
          engines: ["engine_arg_l_allround_01_mk1", ...]
        )
        ↓ JSON Response
Frontend Receives ShipDetails
        ↓
useShipData Hook Updates State
        selectedShip: ShipDetails
        ↓
React Re-renders
        ↓
ConfigPanel Component
        ↓ Now has ship mass and cargo
Populate baseMass Input (500.0)
Populate originalCargo Input (30000.0)
        ↓
Physics Calculation Can Now Run
```

---

## Configuration Management Flow

### Load Configuration

```
GUI Starts
        ↓
App Component Mounts
        ↓
useConfig Hook
        ↓ useEffect() on mount
configApi.get()
        ↓ Axios GET
Backend: GET /api/config
        ↓
ConfigEndpoint::get()
        ↓
ConfigService::getConfig()
        ↓
Read File Synchronously
        file_get_contents("config/build-config.json")
        ↓
Parse JSON
        json_decode($content, true, 512, JSON_THROW_ON_ERROR)
        ↓
Return Config Array
        {
          "cargo-multipliers": [2, 4, 8, 10],
          "flight-mechanics": {
            "dragReductionFactor": 1.0,
            "inertiaImpactFactor": 0.5,
            "dragReductionTiers": [...]
          }
        }
        ↓ JSON Response
Frontend Receives BuildConfig
        ↓
useConfig Hook Updates State
        config: BuildConfig
        ↓
React Re-renders
        ↓
ConfigPanel Component
        ↓ Populates inputs with loaded values
Drag Reduction Slider → 1.0
Inertia Impact Slider → 0.5
Tier Editor → Loads tier tables
        ↓
User Can Now Edit Configuration
```

### Save Configuration

```
User Clicks "Save Configuration" Button
        ↓
ConfigPanel Component
        ↓ onSave(config)
useConfig Hook
        ↓ saveConfig(config)
First: Validate Configuration
        ↓
configApi.validate(config)
        ↓ Axios POST
Backend: POST /api/config/validate
        ↓
ConfigEndpoint::validate()
        ↓
ConfigService::validateConfig(config)
        ↓
Validate cargo-multipliers
        - Must be array
        - Cannot be empty
        - All must be positive numbers
        ↓
Validate flight-mechanics
        - Check inertiaImpactFactor (0.0 - 2.0)
        - Check accelerationResponsiveness (0.1 - 5.0)
        - Validate tier structures
        ↓
Validate Tiers
        - Must be ascending order (maxMultiplier)
        - reductionPercent must be 0.0 - 1.0
        ↓
Return ValidationResult
        {valid: true, errors: []}
        OR
        {valid: false, errors: ["inertiaImpactFactor must be between 0.0 and 2.0"]}
        ↓ JSON Response
Frontend Receives ValidationResult
        ↓
If Valid: Proceed to Save
        ↓
configApi.update(config)
        ↓ Axios POST
Backend: POST /api/config
        ↓
ConfigEndpoint::update()
        ↓
ConfigService::updateConfig(config)
        ↓
Validate Again (double-check)
        ↓
Encode to JSON
        json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        ↓
Write File Synchronously
        file_put_contents("config/build-config.json", $json)
        ↓
Return Success
        {success: true, message: "Configuration saved"}
        ↓ JSON Response
Frontend Receives Success
        ↓
useConfig Hook Updates State
        ↓
Show Success Toast Notification
        "Configuration saved successfully!"
        ↓
User Can Now Run Composer Build
        composer build
        (will use updated config)
```

---

## User Interaction Patterns

### Pattern 1: Tune Drag Reduction for Specific Multiplier

```
Goal: Find optimal drag reduction for 4x cargo multiplier

Step 1: Select Ship Type
        User → Ship Type Dropdown → "Transport"
        ↓ Fetches transport ships
        
Step 2: Select Specific Ship
        User → Ship List → Click "Colossus Vanguard"
        ↓ Fetches ship details
        ↓ Populates mass (500.0) and cargo (30000.0)
        
Step 3: Set Cargo Multiplier
        User → Cargo Multiplier Dropdown → "4x"
        ↓ Sets adjustedCargo = 30000 * 4 = 120000
        
Step 4: Adjust Drag Reduction
        User → Drag Reduction Slider → Drag to 0.3 (30% reduction)
        ↓ Debounce starts (300ms)
        ↓ After 300ms → API call
        ↓ Results displayed
        
Step 5: Compare Results
        User → Views ResultsPanel
        - Mass Ratio: 1.3
        - Drag Forward: 100 → 70 (-30%)
        - TWR: 2.5 → 1.92 (-23%)
        
Step 6: Iterate
        User → Adjust slider again to 0.5 (50% reduction)
        ↓ Repeat calculation
        ↓ Compare again
        
Step 7: Save Configuration
        User → "Save Configuration" button
        ↓ Validates and saves to build-config.json
```

### Pattern 2: Configure Tier System

```
Goal: Set up custom reduction tiers for different multipliers

Step 1: Open Tier Editor
        User → ConfigPanel → "Edit Tiers" button
        ↓ Opens TierEditor component
        
Step 2: Edit Drag Reduction Tiers
        User → Drag Tier Table
        | Max Multiplier | Reduction % | Description          |
        |----------------|-------------|----------------------|
        | 2.0            | 10%         | Tier 1 (up to 2x)    |
        | 4.0            | 30%         | Tier 2 (2x - 4x)     |
        | 8.0            | 50%         | Tier 3 (4x - 8x)     |
        | 999            | 70%         | Tier 4 (8x+)         |
        ↓ User edits "Tier 2" reduction to 35%
        
Step 3: Live Preview
        ConfigPanel automatically recalculates physics
        ↓ (because dragReductionTiers changed)
        ↓ Shows new result with 35% reduction
        
Step 4: Validate Tiers
        Frontend validates:
        - Ascending order
        - Valid percentages
        ↓ Shows inline validation errors if any
        
Step 5: Save
        User → "Save Configuration"
        ↓ Backend validates tier structure
        ↓ Saves to build-config.json
```

---

## Error Handling Flow

### Invalid Configuration Error

```
User Enters Invalid Value
        User → Inertia Impact Factor Input → "5.0"  (max is 2.0)
        ↓
Frontend Validation (React Hook Form)
        ↓ Validates against schema
        ↓ Shows inline error: "Must be between 0.0 and 2.0"
        ↓
Save Button Disabled (form invalid)
        ↓
User Corrects Value → "1.5"
        ↓ Frontend validation passes
        ↓
User Clicks "Save"
        ↓
Backend Validation (ConfigService)
        ↓ Double-checks constraints
        ↓ (passes)
        ↓
File Write Succeeds
```

### API Error (Ship Not Found)

```
User Requests Invalid Ship ID
        shipsApi.getDetails("invalid_ship_id")
        ↓
Backend: GET /api/ships/details/invalid_ship_id
        ↓
ShipDataService::getShipDetails()
        ↓
X4 Core Query Fails
        ShipDefs::getInstance()->getByID() throws exception
        ↓
Catch Exception and Wrap
        throw new GUIException("Ship not found: invalid_ship_id")
        ↓
Endpoint Catches GUIException
        ↓ Returns HTTP 404
        {error: "Ship not found: invalid_ship_id"}
        ↓
Frontend Axios Receives 404
        ↓ Hook catches error
        setError("Ship not found: invalid_ship_id")
        ↓
React Re-renders
        ↓
Show Error Message in UI
        "❌ Ship not found: invalid_ship_id"
```

### File Access Error

```
ConfigService Tries to Write build-config.json
        ↓
File is Read-Only or Locked
        ↓
file_put_contents() Returns False
        ↓
Throw GUIException
        GUIException::fileAccessError("build-config.json", "write")
        ↓ HTTP 500
        {error: "Failed to write configuration file"}
        ↓
Frontend Shows Error Toast
        "❌ Failed to save configuration"
```

---

## Performance Optimization Flows

### Debouncing Pattern

```
User Drags Slider Rapidly
        ↓ onChange fires 50 times in 2 seconds
        
Without Debounce:
        ✗ 50 API calls
        ✗ 50 backend calculations
        ✗ 50 React re-renders
        ✗ Poor UX (laggy, overloaded)
        
With 300ms Debounce:
        ✓ Clear timer on each onChange
        ✓ Only fire API call 300ms after last change
        ✓ Result: 1 API call, fast response
        ✓ User sees final result quickly
```

**Implementation:**
```typescript
const calculate = useCallback((config: PhysicsConfig) => {
  clearTimeout(debounceTimerRef.current);  // Cancel previous timer
  
  debounceTimerRef.current = setTimeout(async () => {
    // Only runs if no further changes within 300ms
    const response = await physicsApi.calculate(config);
    setResult(response);
  }, 300);
}, []);
```

---

### Caching Pattern (Future Enhancement)

```
Current: No Caching (every API call hits backend)

Potential Optimization:
        User Calculates Physics for Ship A at 4x
        ↓ Cache result keyed by: shipId + cargoMultiplier + config hash
        ↓
        User Switches to Ship B
        ↓ Calculate and cache
        ↓
        User Switches Back to Ship A (same config)
        ↓ Cache hit! Return cached result
        ↓ No API call, instant response
```

**Not implemented yet** - but this is where it would fit.

---

## Component-to-Hook-to-API Flow Matrix

| User Action | Component | Hook | API Method | Backend Service | Result |
|-------------|-----------|------|------------|-----------------|--------|
| Adjust drag slider | `ConfigPanel` | `usePhysicsCalculation` | `physicsApi.calculate()` | `PhysicsService` | Physics results displayed |
| Select ship type | `ShipSelector` | `useShipData` | `shipsApi.getShipsByType()` | `ShipDataService` | Ship list displayed |
| Click ship row | `ShipSelector` | `useShipData` | `shipsApi.getDetails()` | `ShipDataService` | Ship details loaded |
| Select engine | `ShipSelector` | `useShipData` | `shipsApi.getEnginesForShip()` | `ShipDataService` | Engine list displayed |
| Edit tier config | `TierEditor` | `useConfig` | N/A (local state) | N/A | Tier table updated |
| Save config | `ConfigPanel` | `useConfig` | `configApi.update()` | `ConfigService` | Config file written |
| Validate config | `ConfigPanel` | `useConfig` | `configApi.validate()` | `ConfigService` | Validation result shown |

---

## State Management Flow

### React State Hierarchy

```
App Component
├── config: BuildConfig | null
├── shipData: ShipDataState
│   ├── shipTypes: ShipTypeInfo[]
│   ├── ships: ShipInfo[]
│   ├── selectedShip: ShipDetails | null
│   └── engines: EngineDef[]
└── physicsResult: PhysicsResultState
    ├── result: PhysicsResponse | null
    ├── loading: boolean
    └── error: string | null
    
State Updates Flow:
    User Action
        ↓
    Component Handler
        ↓
    Hook Updates State (useState)
        ↓
    React Re-renders Affected Components
        ↓
    UI Reflects New State
```

---

**End of Data Flows Documentation**

