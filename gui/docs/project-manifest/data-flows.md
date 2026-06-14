# Data Flows & System Interactions

> **Version:** 1.3  
> **Last Updated:** June 12, 2026  
> **Purpose:** How data moves through the system

---

## Table of Contents

1. [Overview](#overview)
2. [Physics Calculation Flow](#physics-calculation-flow)
3. [Class-Wide Range Calculation Flow](#class-wide-range-calculation-flow)
4. [Ship Data Retrieval Flow](#ship-data-retrieval-flow)
5. [Configuration Management Flow](#configuration-management-flow)
6. [User Interaction Patterns](#user-interaction-patterns)
7. [Error Handling Flow](#error-handling-flow)
8. [Performance Optimization Flows](#performance-optimization-flows)

---

## Overview

This document describes how data flows through the Physics Tuning GUI from user interaction to API response and back to UI updates.

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      User Interaction                        │
│  (Adjust slider, select ship, change cargo multiplier)      │
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
User Adjusts Slider (Acceleration Responsiveness)
        ↓
ConfigPanel Component
        ↓ onChange event
Update React State (accelerationResponsiveness)
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
          accelerationResponsiveness: 1.0,  // User adjusted this
          engineId: "engine_arg_m_travel_01_mk1"  // optional
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
          cargoMultiplier
        )
        ↓
Calculate Mass Ratio
        massRatio = adjustedFullMass / originalFullMass
        ↓
Calculate Acceleration Scaling
        accelerationScalingFactor = massRatio × accelerationResponsiveness
        ↓
Calculate Engine Performance (if engineId provided)
        enginePerformance = calculateEnginePerformance(engineId, masses)
        ↓
Construct PhysicsResponse DTO
        new PhysicsResponse(
          massRatio,
          originalFullMass,
          adjustedFullMass,
          massIncrease,
          originalCargo,
          adjustedCargo,
          accelerationScalingFactor,
          accelerationResponsiveness,
          enginePerformance
        )
        ↓
Serialize to JSON
        $response->withJson($physicsResponse->toArray())
        ↓ HTTP 200 OK
        {
          "massRatio": 1.3,
          "originalFullMass": 530000.0,
          "adjustedFullMass": 620000.0,
          "massIncrease": 90000.0,
          "originalCargo": 30000.0,
          "adjustedCargo": 120000.0,
          "accelerationScalingFactor": 1.3,
          "accelerationResponsiveness": 1.0,
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
Show Acceleration Scaling Factor
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

## Class-Wide Range Calculation Flow

### Purpose

Class-wide range calculations aggregate physics metrics across all ships of a type (e.g., all Transport ships) to show min/max/median ranges and identify worst-case/best-case ships. This helps modders understand the full impact of physics changes across an entire ship class.

### Flow Diagram

```
User Adjusts Slider or Selects Ship Type
        ↓
App.tsx Component (useEffect)
        ↓ triggers both:
        ├─ triggerCalculation() [single-ship, 300ms debounce]
        └─ triggerClassRangeCalculation() [class-range, 500ms debounce]
        ↓
useClassRange Hook (500ms debounce)
        ↓ calculate(classRangeRequest)
classRangeApi.calculate(ClassRangeRequest)
        ↓ Axios POST
Backend: POST /api/calculate/class-range
        ↓
ClassRangeEndpoint::calculate()
        ↓ Parse JSON body
Validate ClassRangeRequest DTO
        {
          "shipType": "transport",
          "cargoMultiplier": 4.0,
          "accelerationResponsiveness": 1.0,
          "engineId": "engine_arg_m_travel_01_mk1"  // optional
        }
        ↓
ClassRangeService::calculateClassRange()
        ↓
Get All Ships of Type
        ShipDataService::getShipsByType($request->shipType)
        ↓ returns array of ShipDef objects
Iterate All Ships (~80 for Transport)
        For each ShipDef:
          ├─ Get cargo capacity
          ├─ Calculate mass ratio
          ├─ Apply PhysicsCalculator
          ├─ Store metrics (massRatio, topSpeed, acceleration)
          └─ Skip ships with zero cargo (avoid division-by-zero)
        ↓
Aggregate Metrics
        For each metric (massRatio, topSpeed, acceleration):
          ├─ Sort values
          ├─ Calculate min = values[0]
          ├─ Calculate max = values[count-1]
          └─ Calculate median = values[count/2]
        ↓
Identify Worst/Best Cases
        Worst Case = Ship with highest mass ratio
        Best Case = Ship with lowest mass ratio
        ↓
Construct ClassRangeResponse DTO
        new ClassRangeResponse(
          shipCount: 78,
          metrics: {
            "massRatio": RangeMetric(min: 1.2, max: 1.8, median: 1.5),
            "topSpeed": RangeMetric(min: 380.5, max: 520.3, median: 412.0),
            "acceleration": RangeMetric(min: 28.5, max: 45.2, median: 35.0)
          },
          worstCase: ShipMetricSummary(
            shipId: "ship_arg_l_trans_container_01_a",
            shipName: "Colossus",
            massRatio: 1.8,
            topSpeed: { original: 450.2, adjusted: 380.5 }
          ),
          bestCase: ShipMetricSummary(...)
        )
        ↓
Serialize to JSON
        $response->withJson($classRangeResponse->toArray())
        ↓ HTTP 200 OK
        {
          "shipCount": 78,
          "metrics": {
            "massRatio": {
              "min": 1.2,
              "max": 1.8,
              "median": 1.5,
              "unit": "ratio",
              "label": "Mass Ratio"
            },
            "topSpeed": { "min": 380.5, "max": 520.3, "median": 412.0, "unit": "m/s", "label": "Top Speed" },
            "acceleration": { "min": 28.5, "max": 45.2, "median": 35.0, "unit": "m/s\u00b2", "label": "Acceleration" }
          },
          "worstCase": { "shipId": "...", "shipName": "Colossus", ... },
          "bestCase": { ... }
        }
        ↓
Frontend: Axios Receives Response
        ↓
useClassRange Hook
        ↓ response.data
Set Result State (ClassRangeResponse)
        ↓
Set Loading State (false)
        ↓
React Re-renders
        ↓
ResultsPanel → ClassRangePanel Component
        ↓ Displays class-wide ranges
For each metric:
  ├─ AbsoluteMetricCard (top speed, acceleration with context phrases)
  ├─ RangeBar (min ─── median ─── max visualization)
  └─ WorstCaseCard (worst-case and best-case ship identification)
        ↓
User Sees Class-Wide Results (<600ms total)
```

### Timeline

| Step | Time | Cumulative |
|------|------|------------|
| User adjusts slider | 0ms | 0ms |
| React state update | ~5ms | 5ms |
| Debounce starts | instant | 5ms |
| **Debounce wait** | 500ms | 505ms |
| API call sent | instant | 505ms |
| Backend processing (80 ships × calculations) | ~50-80ms | 555-585ms |
| JSON serialization | ~5ms | 560-590ms |
| Network transfer (localhost) | ~1-5ms | 561-595ms |
| Frontend receives response | instant | 561-595ms |
| React re-render | ~5-10ms | 566-605ms |
| **User sees results** | **566-605ms** | **~580ms avg** |

**Target:** <600ms (✅ Achieved)

### Key Performance Optimizations

- **500ms debounce** (vs 300ms for single-ship) accounts for heavier backend computation
- **Early ship filtering:** Ships with zero cargo capacity skipped immediately
- **Efficient median calculation:** Sort once, index middle value (O(n log n))
- **Shared PhysicsCalculator instance:** Reused across all ship iterations
- **Engine-dependent metrics:** Top speed and acceleration only calculated when engineId provided

### Differences from Single-Ship Calculation

| Aspect | Single-Ship | Class-Wide Range |
|--------|-------------|------------------|
| **Debounce** | 300ms | 500ms (heavier computation) |
| **Scope** | One ship | ~80 ships (entire class) |
| **Backend Service** | `PhysicsService` | `ClassRangeService` |
| **Endpoint** | `POST /calculate/physics` | `POST /calculate/class-range` |
| **Response** | Detailed physics breakdown | Aggregated min/max/median ranges |
| **Typical Response Time** | 50-100ms | 50-80ms (optimized iteration) |
| **Primary Use Case** | Real-time feedback for current ship | Class-wide impact assessment |

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
determineShipType() alias intercept (mirrors CargoSizeExtractor::resolveShipType()):
        macro contains "scavenger" → "transport"  (Barbarossa)
        macro contains "terraformer" → "mining"   (Xenon H)
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
            "accelerationResponsiveness": 1.0
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
Acceleration Responsiveness Slider → 1.0
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
        - Check accelerationResponsiveness (0.1 - 5.0)
        ↓
Return ValidationResult
        {valid: true, errors: []}
        OR
        {valid: false, errors: ["accelerationResponsiveness must be between 0.1 and 5.0"]}
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

### Pattern 1: Tune Acceleration Responsiveness for Specific Multiplier

```
Goal: Find optimal acceleration responsiveness for 4x cargo multiplier

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
        
Step 4: Adjust Acceleration Responsiveness
        User → Responsiveness Slider → 1.0 (default: physics-correct)
        ↓ Debounce starts (300ms)
        ↓ After 300ms → API call
        ↓ Results displayed
        
Step 5: Compare Results
        User → Views ResultsPanel
        - Mass Ratio: 1.3
        - Acceleration Scaling Factor: 1.3 (equals mass ratio at 1.0 responsiveness)
        - TWR: 2.5 → 1.92 (-23%)
        
Step 6: Iterate
        User → Adjust slider again to 0.8 (80% responsiveness, heavier feel)
        ↓ Repeat calculation
        ↓ Compare again
        
Step 7: Save Configuration
        User → "Save Configuration" button
        ↓ Validates and saves to build-config.json
```

### Pattern 2: Compare Multiple Cargo Multipliers

```
Goal: Compare physics impact across 2x, 4x, 8x cargo multipliers

Step 1: Select Ship
        User → Ship Type Dropdown → "Transport"
        User → Ship List → Click "Colossus Vanguard"
        ↓ Fetches ship details: mass=500, cargo=30000
        
Step 2: Set Acceleration Responsiveness
        User → Responsiveness Slider → 1.0
        ↓ Ensures physics-correct baseline
        
Step 3: Test 2x Multiplier
        User → Cargo Multiplier → "2x"
        ↓ adjustedCargo = 60000
        ↓ API call → acceleration scaling = 1.1
        
Step 4: Test 4x Multiplier
        User → Cargo Multiplier → "4x"
        ↓ adjustedCargo = 120000
        ↓ API call → acceleration scaling = 1.3
        
Step 5: Test 8x Multiplier
        User → Cargo Multiplier → "8x"
        ↓ adjustedCargo = 240000
        ↓ API call → acceleration scaling = 1.5
        
Step 6: Decide on Responsiveness
        If 8x feels too sluggish → increase responsiveness to 1.3
        ↓ Recalculate all multipliers
        
Step 7: Save Configuration
        User → "Save Configuration"
        ↓ Backend validates and saves to build-config.json
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

