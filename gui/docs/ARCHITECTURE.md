# Architecture Documentation

> **X4 Cargo Sizes Mod - Physics Tuning GUI**  
> **Version:** 1.3  
> **Last Updated:** February 17, 2026

---

> 📘 **Quick Start:** New to the codebase? Start with the [Project Manifest](project-manifest/README.md) for a structured overview of the system architecture, constraints, and data flows. This document provides deeper architectural details.

---

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Backend Architecture](#backend-architecture)
4. [Frontend Architecture](#frontend-architecture)
5. [Data Flow](#data-flow)
6. [Key Design Decisions](#key-design-decisions)
7. [API Contracts](#api-contracts)
8. [Security Considerations](#security-considerations)

---

## Overview

### Purpose

The Physics Tuning GUI provides an interactive web-based interface for configuring and testing the X4 Cargo Sizes Mod's physics adjustments in real-time. It bridges the gap between the existing CLI-based build system and game modding workflow by offering:

- **Visual Configuration:** Edit physics parameters with sliders, toggles, and tier editors
- **Real-Time Feedback:** See how parameter changes affect ship performance immediately
- **Game Data Integration:** Browse extracted X4 game data for accurate testing
- **Configuration Persistence:** Save tuned configurations to `build-config.json` for use with the build system

### High-Level Architecture

The GUI uses a **client-server architecture** with a stateless REST API backend and a reactive frontend:

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend                            │
│  React 18 + TypeScript + Vite + TailwindCSS (Port 5173)    │
│  • User interactions (sliders, ship selection)             │
│  • API client (axios)                                       │
│  • State management (React hooks)                           │
│  • Results visualization (charts, comparisons)              │
└─────────────────────────────────────────────────────────────┘
                            ↓ HTTP/JSON
┌─────────────────────────────────────────────────────────────┐
│                       REST API Layer                         │
│       PHP 8.4 + Slim Framework 4 (Port 8080)                │
│  • Endpoints for physics calculation, ships, config         │
│  • CORS middleware for cross-origin requests                │
│  • Request validation and error handling                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      Service Layer                           │
│  • PhysicsService (wraps PhysicsCalculator)                 │
│  • ShipDataService (reads extracted X4 game data)           │
│  • ConfigService (reads/writes build-config.json)           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                      │
│  • PhysicsCalculator (core physics calculations)            │
│  • AdjustedDrag, AdjustedInertia, AdjustedJerk             │
│  • ReductionTier (tier-based reduction system)              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      Data Access Layer                       │
│  • X4 Core library (ShipDefs, EngineDefs, etc.)            │
│  • Extracted game XML files (ships, engines, wares)         │
│  • build-config.json (flight mechanics configuration)       │
└─────────────────────────────────────────────────────────────┘
```

---

## System Architecture

### Components

| Component | Technology | Port | Purpose |
|-----------|-----------|------|---------|
| **Frontend** | React 18 + TypeScript + Vite | 5173 | User interface and interaction |
| **Backend** | PHP 8.4 + Slim Framework 4 | 8080 | REST API and business logic |
| **Data Layer** | X4 Core + XML files | N/A | Game data and configuration |

### Communication Protocol

- **Format:** JSON over HTTP
- **CORS:** Enabled for `http://localhost:5173`
- **Authentication:** None (local development only)
- **Content-Type:** `application/json` for all requests/responses

### Deployment Model

This is a **local development tool** designed to run on the developer's machine. It is **not intended for production deployment** or public hosting. Both servers run on localhost:

- Frontend dev server: `http://localhost:5173`
- Backend API server: `http://localhost:8080`

---

## Backend Architecture

### Directory Structure

```
gui/backend/
├── public/
│   └── index.php           # API entry point (Slim bootstrap)
├── src/
│   ├── API/
│   │   ├── Router.php      # Route definitions
│   │   ├── Endpoints/      # API endpoint handlers
│   │   │   ├── PhysicsEndpoint.php
│   │   │   ├── ShipsEndpoint.php
│   │   │   └── ConfigEndpoint.php
│   │   └── Middleware/
│   │       └── CorsMiddleware.php
│   ├── Services/           # Service layer (wraps business logic)
│   │   ├── PhysicsService.php
│   │   ├── ShipDataService.php
│   │   └── ConfigService.php
│   ├── DTOs/               # Data Transfer Objects
│   │   ├── PhysicsRequest.php
│   │   ├── PhysicsResponse.php
│   │   ├── PhysicsResponseData.php
│   │   ├── EnginePerformance.php
│   │   ├── ShipDetails.php
│   │   └── ValidationResult.php
│   └── Exceptions/
│       └── GUIException.php
└── composer.json
```

### Patterns

#### 1. Service Layer Pattern

All business logic is wrapped in service classes to provide a clean API for endpoints:

```php
// Endpoint delegates to service
class PhysicsEndpoint {
    private PhysicsService $physicsService;
    
    public function calculate(Request $req, Response $res) {
        $dto = PhysicsRequest::fromArray($req->getParsedBody());
        $result = $this->physicsService->calculatePhysics($dto);
        return $res->withJson($result);
    }
}
```

**Benefits:**
- Clear separation of concerns
- Endpoint handlers remain thin and focused on HTTP concerns
- Services can be unit tested independently
- Reusable logic across multiple endpoints

#### 2. Dependency Injection (DI) with Service Container

Dependencies are managed by a custom `ServiceContainer` implementation:

```php
// ServiceContainer: Singleton-based lazy loading
$container->register('physics_service', fn($c) => new PhysicsService());

// Router: Injects services into endpoints
$endpoints['/api/physics'] = fn() => new PhysicsEndpoint($container->get('physics_service'));
```

**Benefits:**
- **Lazy Instantiation:** Services created only when requested (performance)
- **Singleton Lifecycle:** Services are shared across the application request
- **Testability:** Mock services can be injected during testing
- **Decoupling:** Components don't instantiate their dependencies

#### 3. Data Transfer Objects (DTOs)

All API contracts are defined as strongly-typed DTOs:

```php
class PhysicsRequest {
    public function __construct(
        public readonly float $baseMass,
        public readonly float $originalCargo,
        public readonly float $adjustedCargo,
        public readonly float $cargoMultiplier,
        // ... more fields
    ) {}
    
    public static function fromArray(array $data): self {
        // Validation and construction
    }
}
```

**Benefits:**
- Type safety (PHP 8.4 readonly properties)
- Contract-first API design
- Validation at construction time
- Mirror frontend TypeScript types exactly

#### 4. Exception Hierarchy

All exceptions extend `CargoSizeException` to maintain consistency with the parent project:

```php
class GUIException extends CargoSizeException {
    public static function invalidConfiguration(string $reason): self {
        return new self("Invalid configuration: $reason");
    }
}
```

### Dependency Management

The backend has **dual autoloaders**:

1. **Backend autoloader** (`gui/backend/vendor/autoload.php`): Slim Framework and dependencies
2. **Parent project autoloader** (`../../vendor/autoload.php`): PhysicsCalculator, X4 Core, etc.

Both are loaded in `public/index.php`:

```php
require_once __DIR__ . '/../../../vendor/autoload.php'; // Parent
require_once __DIR__ . '/../vendor/autoload.php';        // Backend
```

This allows the backend to reuse all existing business logic without duplication.

---

## Frontend Architecture

### Directory Structure

```
gui/frontend/
├── public/                 # Static assets
├── src/
│   ├── components/         # React components
│   │   ├── Layout/         # Layout components (Header, Footer, etc.)
│   │   ├── ConfigPanel/    # Configuration editing UI
│   │   ├── ShipSelector/   # Ship and engine selection UI
│   │   ├── ResultsPanel/   # Results display UI
│   │   └── UI/             # Reusable UI components (Card, Spinner, etc.)
│   ├── hooks/              # Custom React hooks
│   │   ├── usePhysicsCalculation.ts
│   │   ├── useShipData.ts
│   │   └── useConfig.ts
│   ├── services/           # API client and utilities
│   │   ├── api.ts          # Axios-based API client
│   │   └── storage.ts      # LocalStorage helpers
│   ├── types/              # TypeScript type definitions
│   │   ├── physics.d.ts
│   │   ├── ships.d.ts
│   │   └── config.d.ts
│   ├── App.tsx             # Main application component
│   └── main.tsx            # Application entry point
├── index.html              # HTML template
├── vite.config.ts          # Vite configuration
├── tsconfig.json           # TypeScript configuration
└── package.json
```

### Patterns

#### 1. Custom Hooks for Business Logic

All stateful logic is extracted into custom hooks:

```typescript
// usePhysicsCalculation.ts
export function usePhysicsCalculation() {
  const [result, setResult] = useState<PhysicsResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const calculate = useMemo(
    () =>
      debounce(async (config: PhysicsConfig) => {
        setLoading(true);
        try {
          const response = await physicsApi.calculate(config);
          setResult(response);
        } catch (err) {
          setError(err);
        } finally {
          setLoading(false);
        }
      }, 300),
    []
  );

  return { result, loading, error, calculate };
}
```

**Benefits:**
- Reusable stateful logic
- Testable in isolation
- Keeps components focused on rendering
- Debouncing handled at the hook level

#### 2. Component Composition

UI is built from small, focused components:

```
App.tsx
  ├── Header
  ├── TwoColumnLayout
  │     ├── ConfigPanel
  │     │     ├── SliderInput
  │     │     ├── TierEditor
  │     │     ├── ToggleInput
  │     │     └── ActionButtons
  │     └── ResultsPanel
  │           ├── PhysicsOverview
  │           ├── ComparisonView
  │           └── DiagnosticsPanel
  └── Footer
```

Each component has a **single responsibility** and communicates via props.

#### 3. Type Safety with TypeScript

All types mirror backend DTOs exactly:

```typescript
// frontend/src/types/physics.d.ts
export interface PhysicsConfig {
  baseMass: number;
  originalCargo: number;
  adjustedCargo: number;
  cargoMultiplier: number;
  useEffectiveRatioCap: boolean;
  dragReductionFactor: number;
  // ... matches PhysicsRequest.php
}
```

TypeScript `strict` mode is enabled to catch errors at compile time.

#### 4. Centralized API Client

All backend communication goes through a single API client:

```typescript
// api.ts
export const physicsApi = {
  calculate: async (request: PhysicsConfig) => { ... },
  calculateBatch: async (requests: PhysicsConfig[]) => { ... },
};

export const shipsApi = {
  getTypes: async () => { ... },
  getShipsByType: async (type: string) => { ... },
  // ...
};

export const configApi = {
  get: async () => { ... },
  update: async (config: BuildConfig) => { ... },
  validate: async (config: BuildConfig) => { ... },
};
```

---

## Data Flow

### Real-Time Physics Calculation Flow

```
User adjusts slider
      ↓
SliderInput fires onChange
      ↓
ConfigPanel updates local state
      ↓
App.tsx detects state change (useEffect)
      ↓
Debounced API call (300ms)
      ↓
POST /api/calculate/physics
      ↓
PhysicsEndpoint → PhysicsService
      ↓
PhysicsCalculator performs calculations
      ↓
PhysicsResponse returned to frontend
      ↓
ResultsPanel re-renders with new data
      ↓
User sees updated values (total: <500ms)
```

### Ship Selection Flow

```
User selects ship type
      ↓
TypeFilter fires onTypeChange
      ↓
ShipSelector updates filter state
      ↓
GET /api/ships/{type}
      ↓
ShipsEndpoint → ShipDataService
      ↓
X4 Core ShipDefs filtered by type
      ↓
Ship list returned to frontend
      ↓
ShipPicker dropdown populated
      ↓
User selects specific ship
      ↓
GET /api/ships/details/{shipId}
      ↓
Ship details (mass, cargo, drag, inertia, jerk) returned
      ↓
Physics calculation triggered with new ship data
```

### Configuration Save Flow

```
User clicks "Save Config"
      ↓
Confirmation dialog shown
      ↓
User confirms
      ↓
POST /api/config (with BuildConfig JSON)
      ↓
ConfigEndpoint → ConfigService
      ↓
ConfigService.validateConfig() checks validity
      ↓
If valid: write to build-config.json
      ↓
Success response returned
      ↓
ActionButtons displays success message
      ↓
User can now run `composer build` with new config
```

---

## Key Design Decisions

### 1. Stateless Backend

**Decision:** The backend API is completely stateless. No sessions, no state stored between requests.

**Rationale:**
- Simplicity: No need for session management or state synchronization
- Scalability: Each request is independent (future multi-user scenarios)
- Predictability: Easy to reason about and test

**Implementation:** All required data is passed in each request.

### 2. Debounced Calculations

**Decision:** Physics calculations are debounced at 300ms to reduce API calls during slider dragging.

**Rationale:**
- **Performance:** Avoids flooding the backend with requests
- **UX:** User still gets near-instant feedback (<500ms total)
- **Resource Efficiency:** Reduced server load during rapid slider adjustments

**Implementation:** Debouncing is implemented in the `usePhysicsCalculation` hook using lodash's `debounce`.

### 3. Type Mirroring Between Backend and Frontend

**Decision:** TypeScript types in the frontend **exactly mirror** PHP DTOs in the backend.

**Rationale:**
- **Contract Consistency:** Ensures frontend and backend always agree on data shape
- **Compile-Time Safety:** TypeScript catches mismatches before runtime
- **Documentation:** Types serve as API documentation

**Implementation:** Manual synchronization (no code generation). Changes to DTOs must be reflected in types.

### 4. X4 Core Integration for Game Data

**Decision:** Use X4 Core library (`ShipDefs`, `EngineDefs`) instead of parsing XML directly in the GUI.

**Rationale:**
- **Reuse Existing Logic:** X4 Core already handles XML parsing, data extraction, and caching
- **Consistency:** Same data access patterns as the build system
- **Maintainability:** Changes to X4 game data format handled by X4 Core

**Implementation:** ShipDataService delegates to X4 Core's `ShipDefs::getInstance()` and `EngineDefs::getInstance()`.

### 5. No Database

**Decision:** No database or persistent storage. All data comes from XML files and `build-config.json`.

**Rationale:**
- **Simplicity:** No database setup, migrations, or ORM complexity
- **Project Constraints:** The parent project uses synchronous file I/O only (per constraints.md)
- **Local Tool:** GUI is a single-user local tool, not a multi-user web app

**Implementation:** Configuration persisted to `build-config.json`, game data read from extracted XML.

### 6. TailwindCSS for Styling

**Decision:** Use TailwindCSS v4 for all styling instead of component libraries like Material UI.

**Rationale:**
- **Flexibility:** Precise control over styling without fighting component library conventions
- **Performance:** No heavy component library bundle
- **Consistency:** Utility-first approach keeps styling uniform

**Implementation:** TailwindCSS configured in `tailwind.config.js`, imported in `src/styles/globals.css`.

---

## API Contracts

### Request/Response Format

All API requests and responses use **JSON**. Endpoints follow RESTful conventions:

| Endpoint | Method | Purpose | Success Code |
|----------|--------|---------|--------------|
| `/api/calculate/physics` | POST | Single physics calculation | 200 |
| `/api/calculate/batch` | POST | Batch physics calculations | 200 |
| `/api/ships/types` | GET | List ship types | 200 |
| `/api/ships/{type}` | GET | Ships by type | 200 |
| `/api/ships/details/{shipId}` | GET | Ship details | 200 |
| `/api/ships/{shipId}/engines` | GET | Engines for ship | 200 |
| `/api/engines` | GET | All engines | 200 |
| `/api/config` | GET | Current config | 200 |
| `/api/config` | POST | Save config | 200 |
| `/api/config/validate` | POST | Validate config | 200 |

### Error Responses

All errors return a consistent JSON structure:

```json
{
  "error": "Human-readable error message",
  "code": "ERROR_CODE",
  "details": {
    "field": "Additional context"
  }
}
```

HTTP status codes:
- `200`: Success
- `400`: Bad request (invalid input)
- `404`: Not found (ship ID, engine ID)
- `500`: Internal server error

---

## Security Considerations

### Threat Model

Since this is a **local development tool**, the threat model is limited:

- **No authentication:** Single user on localhost
- **No authorization:** All endpoints publicly accessible
- **No data privacy concerns:** All data is local game data

### Security Measures

1. **CORS Restrictions:** Only `http://localhost:5173` allowed to call backend
2. **Input Validation:** All DTOs validate input at construction time
3. **File System Constraints:** `ConfigService` only reads/writes `build-config.json` (no arbitrary file access)
4. **No User-Supplied Code Execution:** No `eval()` or dynamic code execution
5. **Type Safety:** PHP strict types and TypeScript strict mode catch errors early

### Known Limitations

- **No HTTPS:** Local development uses HTTP (not a risk for localhost)
- **No rate limiting:** API can be called as fast as desired (acceptable for single user)
- **No input sanitization for XSS:** Not applicable (no HTML rendering from user input)

---

## Future Enhancements

### Potential Improvements

1. **Batch Comparison Mode:** Calculate physics for multiple ships simultaneously and compare side-by-side
2. **Configuration Presets:** Save/load named configuration presets for different playstyles
3. **Visual Charts:** Add Recharts-based charts to visualize drag/inertia curves across multiplier ranges
4. **Export to Markdown:** Generate a report showing all adjusted values for documentation
5. **Undo/Redo:** Add undo/redo history for configuration changes
6. **Backend Caching:** Cache ship data to reduce X4 Core calls (in-memory cache with TTL)

### Constraints to Respect

Any future enhancements must respect the project constraints:
- **Synchronous file I/O only** (no async operations)
- **No database connections**
- **Strict type hints** (PHP and TypeScript)
- **Follow existing patterns** (Service Layer, DTOs, Custom Hooks)

---

## Appendix: Tech Stack Summary

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Backend Language** | PHP | 8.4+ | Business logic and API |
| **Backend Framework** | Slim Framework | 4.13 | HTTP routing and middleware |
| **HTTP Library** | Slim PSR-7 | 1.6 | PSR-7 HTTP message implementation |
| **Frontend Language** | TypeScript | 5.6+ | Type-safe UI development |
| **Frontend Framework** | React | 18.3+ | Component-based UI |
| **Build Tool** | Vite | 7.0+ | Fast dev server and bundler |
| **Styling** | TailwindCSS | 4.0 | Utility-first CSS |
| **HTTP Client** | Axios | 1.7+ | API communication |
| **Game Data Access** | X4 Core | dev-main | Extracted X4 game data |
| **Testing** | PHPUnit | 12.0+ | Backend unit tests |
| **Static Analysis** | PHPStan | 2.1+ | PHP type checking |

**Development Environment:**
- **PHP CLI:** 8.4+
- **Node.js:** 18+
- **Composer:** 2.x
- **npm:** 9+

---

**Last Updated:** February 17, 2026  
**Maintainer:** Sebastian Mordziol  
**Contact:** s.mordziol@mistralys.eu
