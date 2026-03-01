# X4 Cargo Sizes Mod GUI - API Documentation

> **REST API for physics calculations, ship data, and configuration management**

**Base URL**: `http://localhost:8080/api`  
**Version**: 1.2  
**Last Updated**: February 19, 2026

---

> 📘 **Quick Start:** New to the codebase? Start with the [Project Manifest](project-manifest/README.md) for a structured overview of the system architecture, constraints, and data flows.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [CORS Support](#cors-support)
4. [Error Handling](#error-handling)
5. [Endpoints](#endpoints)
   - [Physics Calculation](#1-physics-calculation)
   - [Batch Physics Calculation](#2-batch-physics-calculation)
   - [Class-Wide Range Calculation](#3-class-wide-range-calculation)
   - [Get Ship Types](#4-get-ship-types)
   - [Get Ships by Type](#5-get-ships-by-type)
   - [Get Ship Details](#6-get-ship-details)
   - [Get Engines for Ship](#7-get-engines-for-ship)
   - [Get Engine Details](#8-get-engine-details)
   - [Get All Engines](#9-get-all-engines)
   - [Get Configuration](#10-get-configuration)
   - [Update Configuration](#11-update-configuration)

---

## Overview

The X4 Cargo Sizes Mod GUI API provides endpoints for:
- **Physics calculations** with configurable parameters
- **Ship data** extracted from X4 game files
- **Engine data** with thrust specifications
- **Configuration management** for build settings

All responses are JSON with `Content-Type: application/json`.

---

## Authentication

**None required**. This is a local development API running on localhost.

---

## CORS Support

All endpoints support CORS with:
- **Allowed Origins**: `*` (all origins)
- **Allowed Methods**: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`
- **Allowed Headers**: `Content-Type`, `Accept`, `Authorization`
- **Preflight**: `OPTIONS` requests return `200 OK`

---

## Error Handling

All errors return JSON with consistent structure:

```json
{
  "error": "Error message describing what went wrong"
}
```

**HTTP Status Codes**:
- `200 OK` - Success
- `400 Bad Request` - Invalid input or validation failure
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server-side error

---

## Endpoints

### 1. Physics Calculation

Calculate physics adjustments for a ship with given parameters.

**Endpoint**: `POST /api/calculate/physics`

**Request Body**:
```json
{
  "baseMass": 50000,
  "originalCargo": 5000,
  "adjustedCargo": 20000,
  "cargoMultiplier": 4.0,
  "accelerationResponsiveness": 1.0,
  "engineId": "engine_arg_s_travel_01_mk1"
}
```

**Response**: `200 OK`
```json
{
  "massRatio": 1.3,
  "originalFullMass": 55000,
  "adjustedFullMass": 70000,
  "massIncrease": 15000,
  "originalCargo": 5000,
  "adjustedCargo": 20000,
  "accelerationScalingFactor": 1.3,
  "accelerationResponsiveness": 1.0,
  "enginePerformance": {
    "originalTWR": 2.5,
    "adjustedTWR": 1.96,
    "reductionPercent": -21.6,
    "originalAcceleration": 10.5,
    "adjustedAcceleration": 8.23
  }
}
```

**Notes**:
- `engineId` is optional. If omitted, `enginePerformance` will be `null`
- Calculation uses `PhysicsCalculator` from the main mod codebase
- Response time target: < 100ms

---

### 2. Batch Physics Calculation

Calculate physics for multiple configurations at once.

**Endpoint**: `POST /api/calculate/physics/batch`

**Request Body**:
```json
{
  "configs": [
    {
      "baseMass": 50000,
      "originalCargo": 5000,
      "adjustedCargo": 10000,
      "cargoMultiplier": 2.0,
      "accelerationResponsiveness": 1.0
    },
    {
      "baseMass": 50000,
      "originalCargo": 5000,
      "adjustedCargo": 20000,
      "cargoMultiplier": 4.0,
      "accelerationResponsiveness": 1.0
    }
  ]
}
```

**Response**: `200 OK`
```json
{
  "results": [
    { /* PhysicsResponse for first config */ },
    { /* PhysicsResponse for second config */ }
  ]
}
```

**Notes**:
- Useful for comparing multiple cargo multipliers
- Returns array of results in same order as input configs

---

### 3. Class-Wide Range Calculation

Calculate aggregated min/max/median ranges across all ships of a type to identify worst-case and best-case ships.

**Endpoint**: `POST /api/calculate/class-range`

**Request Body**:
```json
{
  "shipType": "transport",
  "cargoMultiplier": 4.0,
  "accelerationResponsiveness": 1.0,
  "engineId": "engine_arg_m_travel_01_mk1"
}
```

**Response**: `200 OK`
```json
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
    "topSpeed": {
      "min": 380.5,
      "max": 520.3,
      "median": 412.0,
      "unit": "m/s",
      "label": "Top Speed"
    },
    "acceleration": {
      "min": 28.5,
      "max": 45.2,
      "median": 35.0,
      "unit": "m/s\u00b2",
      "label": "Acceleration"
    }
  },
  "worstCase": {
    "shipId": "ship_arg_l_trans_container_01_a",
    "shipName": "Colossus",
    "size": "l",
    "massRatio": 1.8,
    "topSpeed": {
      "original": 450.2,
      "adjusted": 380.5
    },
    "acceleration": {
      "original": 35.0,
      "adjusted": 28.5
    }
  },
  "bestCase": {
    "shipId": "ship_tel_m_trans_container_01_a",
    "shipName": "Tern",
    "size": "m",
    "massRatio": 1.2,
    "topSpeed": {
      "original": 480.0,
      "adjusted": 455.0
    },
    "acceleration": {
      "original": 50.0,
      "adjusted": 45.2
    }
  }
}
```

**Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shipType` | string | Yes | Ship type filter: `transport`, `mining`, `auxiliary`, `carrier` |
| `cargoMultiplier` | number | Yes | Cargo capacity multiplier (e.g., 2.0, 4.0, 8.0) |
| `accelerationResponsiveness` | number | Yes | Acceleration responsiveness factor (0.1–5.0) |
| `engineId` | string | No | Engine ID for top speed/acceleration metrics (omit to skip engine-dependent metrics) |

**Response Fields**:

| Field | Description |
|-------|-------------|
| `shipCount` | Number of ships included in calculation (ships with zero cargo excluded) |
| `metrics` | Map of metric name to `RangeMetric` object with min/max/median/unit/label |
| `worstCase` | Ship with highest mass ratio (worst performance impact) |
| `bestCase` | Ship with lowest mass ratio (best performance retention) |

**Notes**:
- Iterates all ships of requested type (~80 ships for transport)
- Ships with zero cargo capacity are automatically excluded
- Engine-dependent metrics (`topSpeed`, `acceleration`) only included when `engineId` provided
- Typical response time: 50-80ms for 80 ships
- Frontend should use 500ms debounce (vs 300ms for single-ship)

---

### 4. Get Ship Types

Retrieve all available ship types.

**Endpoint**: `GET /api/ships/types`

**Response**: `200 OK`
```json
[
  {
    "type": "transport",
    "label": "Transport Ships"
  },
  {
    "type": "mining",
    "label": "Mining Ships"
  },
  {
    "type": "auxiliary",
    "label": "Auxiliary Ships"
  },
  {
    "type": "carrier",
    "label": "Carrier Ships"
  }
]
```

**Notes**:
- Ship types correspond to X4 game categories
- Used for filtering ships in the UI

---

### 5. Get Ships by Type

Retrieve all ships of a specific type.

**Endpoint**: `GET /api/ships/{type}`

**Path Parameters**:
- `type` (string, required): Ship type (`transport`, `mining`, `auxiliary`, `carrier`)

**Example**: `GET /api/ships/transport`

**Response**: `200 OK`
```json
[
  {
    "id": "ship_arg_l_trans_container_01_a",
    "name": "Colossus (Argon)",
    "size": "l"
  },
  {
    "id": "ship_arg_m_trans_container_01_a",
    "name": "Mercury (Argon)",
    "size": "m"
  }
]
```

**Notes**:
- Ship IDs are X4 game identifiers
- Size values: `xs`, `s`, `m`, `l`, `xl`
- Currently returns sample data; production should use extracted game data

---

### 6. Get Ship Details

Retrieve detailed information for a specific ship.

**Endpoint**: `GET /api/ships/{shipId}/details`

**Path Parameters**:
- `shipId` (string, required): Ship ID

**Example**: `GET /api/ships/ship_arg_l_trans_container_01_a/details`

**Response**: `200 OK`
```json
{
  "id": "ship_arg_l_trans_container_01_a",
  "name": "Colossus (Argon)",
  "type": "transport",
  "size": "l",
  "mass": 50000,
  "cargo": 20000,
  "engines": [
    "engine_arg_l_travel_01_mk1",
    "engine_arg_l_travel_01_mk2"
  ]
}
```

**Error**: `404 Not Found` if ship doesn't exist
```json
{
  "error": "Ship not found"
}
```

---

### 7. Get Engines for Ship

Retrieve compatible engines for a specific ship.

**Endpoint**: `GET /api/ships/{shipId}/engines`

**Path Parameters**:
- `shipId` (string, required): Ship ID

**Example**: `GET /api/ships/ship_arg_l_trans_container_01_a/engines`

**Response**: `200 OK`
```json
[
  {
    "id": "engine_arg_l_travel_01_mk1",
    "name": "Argon L Travel Engine Mk1",
    "forwardThrust": 150000,
    "reverseThrust": 75000,
    "boostThrust": 200000,
    "travelThrust": 500000
  },
  {
    "id": "engine_arg_l_travel_01_mk2",
    "name": "Argon L Travel Engine Mk2",
    "forwardThrust": 180000,
    "reverseThrust": 90000,
    "boostThrust": 240000,
    "travelThrust": 600000
  }
]
```

**Notes**:
- Thrust values in Newtons (N)
- Used for engine performance calculations

---

### 8. Get Engine Details

Retrieve detailed information for a specific engine.

**Endpoint**: `GET /api/engines/{engineId}`

**Path Parameters**:
- `engineId` (string, required): Engine ID

**Example**: `GET /api/engines/engine_arg_l_travel_01_mk1`

**Response**: `200 OK`
```json
{
  "id": "engine_arg_l_travel_01_mk1",
  "name": "Argon L Travel Engine Mk1",
  "forwardThrust": 150000,
  "reverseThrust": 75000,
  "boostThrust": 200000,
  "travelThrust": 500000
}
```

**Error**: `404 Not Found`
```json
{
  "error": "Engine not found"
}
```

---

### 9. Get All Engines

Retrieve all available engines.

**Endpoint**: `GET /api/engines`

**Response**: `200 OK`
```json
[
  {
    "id": "engine_arg_s_travel_01_mk1",
    "name": "Argon S Travel Engine Mk1",
    "forwardThrust": 50000,
    "reverseThrust": 25000,
    "boostThrust": 75000,
    "travelThrust": 150000
  },
  {
    "id": "engine_arg_m_travel_01_mk1",
    "name": "Argon M Travel Engine Mk1",
    "forwardThrust": 100000,
    "reverseThrust": 50000,
    "boostThrust": 150000,
    "travelThrust": 300000
  }
]
```

**Notes**:
- Returns all engines across all sizes and factions
- Large response; consider caching client-side

---

### 10. Get Configuration

Retrieve current build configuration.

**Endpoint**: `GET /api/config`

**Response**: `200 OK`
```json
{
  "cargo-multipliers": [2, 4, 6, 8, 10],
  "flight-mechanics": {
    "accelerationResponsiveness": 1.0
  }
}
```

**Notes**:
- Reads from `config/build-config.json` (project root)
- Path resolution: 4 levels up from `gui/backend/public/`

---

### 10. Update Configuration

Save updated build configuration.

**Endpoint**: `POST /api/config`

**Request Body**:
```json
{
  "cargo-multipliers": [2, 4, 8, 10],
  "flight-mechanics": {
    "accelerationResponsiveness": 1.1
  }
}
```

**Response**: `200 OK`
```json
{
  "success": true,
  "message": "Configuration saved successfully"
}
```

**Error**: `400 Bad Request` (validation failure)
```json
{
  "error": "Invalid configuration: accelerationResponsiveness must be between 0.1 and 5.0"
}
```

**Validation Rules**:
- `accelerationResponsiveness`: 0.1–5.0

**Notes**:
- Writes to `config/build-config.json`
- Backend validates config before saving
- Run `composer build` after saving to regenerate mod files

---

## Data Flow

### Physics Calculation Flow

```
Frontend → POST /api/calculate/physics → Backend
                                           ↓
                                    PhysicsService
                                           ↓
                                    PhysicsCalculator (core lib)
                                           ↓
                                    PhysicsResponse DTO
                                           ↓
Frontend ← JSON Response ← Backend
```

### Configuration Update Flow

```
Frontend → POST /api/config → Backend
                                 ↓
                          ConfigService::validateConfig()
                                 ↓
                          file_put_contents('config/build-config.json')
                                 ↓
Frontend ← Success Response ← Backend
```

---

## Performance Benchmarks

**Target Metrics**:
- Single physics calculation: < 100ms
- Config read: < 10ms
- Config write: < 50ms
- Ship data retrieval: < 20ms (cached)

**Optimization**:
- Frontend caches ship/engine data to avoid redundant fetches
- Frontend debounces slider changes (300ms) to reduce API load
- Backend uses synchronous file I/O (no async overhead)

---

## Development Notes

### Adding New Endpoints

1. Create endpoint class in `gui/backend/src/API/Endpoints/`
2. Register routes in `gui/backend/src/API/Router.php`
3. Add corresponding method to `gui/frontend/src/services/api.ts`
4. Update TypeScript types in `gui/frontend/src/types/`
5. Update this documentation

### Testing Endpoints

**Using curl**:
```bash
# Get configuration
curl http://localhost:8080/api/config

# Calculate physics
curl -X POST http://localhost:8080/api/calculate/physics \
  -H "Content-Type: application/json" \
  -d '{"baseMass": 50000, "originalCargo": 5000, "adjustedCargo": 20000, "cargoMultiplier": 4.0, "accelerationResponsiveness": 1.0}'

# Get ship types
curl http://localhost:8080/api/ships/types
```

**Using browser DevTools**:
- Network tab → Monitor API calls
- Console → Use `fetch()` for manual testing

---

## Troubleshooting

### 404 Errors for All Endpoints

**Cause**: Slim routing not configured correctly

**Solution**: Verify `gui/backend/public/index.php` includes Router and registers all routes

### CORS Errors

**Cause**: CorsMiddleware not applied

**Solution**: Check middleware registration in `index.php`

### 500 Errors

**Cause**: PHP errors in backend code

**Solution**: Check `gui/logs/backend.log` (Linux/Mac) or terminal output (Windows)

### Empty/NULL Responses

**Cause**: Missing return statements or incorrect DTO serialization

**Solution**: Verify endpoint returns JSON-serializable data

---

## API Client (Frontend)

The frontend provides a type-safe API client:

```typescript
import { physicsApi, shipsApi, configApi } from './services/api';

// Calculate physics
const result = await physicsApi.calculate(config);

// Get ship types
const types = await shipsApi.getTypes();

// Save config
await configApi.update(newConfig);
```

See `gui/frontend/src/services/api.ts` for implementation.

---

## Related Documentation

- [GUI README](../README.md) - Setup and usage instructions
- [Physics Tuning Guide](../../docs/physics-tuning-guide.md) - Physics concepts
- [Project Manifest](../../docs/agents/project-manifest/) - Architecture reference

---

**Version**: 1.2  
**Last Updated**: February 19, 2026  
**Maintainer**: Lead Implementation Engineer
