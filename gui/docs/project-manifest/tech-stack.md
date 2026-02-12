# Tech Stack & Architectural Patterns

> **Version:** 1.0  
> **Last Updated:** February 12, 2026  
> **Purpose:** Runtime, dependencies, and architectural patterns reference

---

## Table of Contents

1. [Runtime Environment](#runtime-environment)
2. [Backend Stack](#backend-stack)
3. [Frontend Stack](#frontend-stack)
4. [Key Dependencies](#key-dependencies)
5. [Architectural Patterns](#architectural-patterns)
6. [Build System](#build-system)
7. [Deployment Model](#deployment-model)

---

## Runtime Environment

### System Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | 8.4+ | Requires CLI support |
| **Node.js** | 18+ | LTS recommended |
| **npm** | 9+ | Package manager for frontend |
| **Composer** | 2.x | PHP dependency manager |
| **OS** | Windows/Linux/Mac | Local development only |

### Runtime Ports

| Service | Port | Protocol | Purpose |
|---------|------|----------|---------|
| **Frontend Dev Server** | 5173 | HTTP | Vite dev server with HMR |
| **Backend API Server** | 8080 | HTTP | PHP built-in server |

---

## Backend Stack

### Core Framework

**Slim Framework 4** - Lightweight PHP micro-framework for REST APIs

```php
// Example: Slim route definition
$app->post('/api/calculate/physics', [PhysicsEndpoint::class, 'calculate']);
```

**Why Slim?**
- Minimalist design perfect for API-only backends
- Fast routing (FastRoute library)
- PSR-7 HTTP middleware support
- No overhead from unused features

### PHP Version & Features

**PHP 8.4+** enables modern language features:

| Feature | Usage | Example |
|---------|-------|---------|
| **Strict Types** | Every file | `declare(strict_types=1);` |
| **Readonly Properties** | DTOs | `public readonly float $baseMass` |
| **Constructor Property Promotion** | DTOs | `__construct(public float $mass)` |
| **Union Types** | Nullables | `?string $engineId` |
| **Named Arguments** | DTO construction | `new PhysicsRequest(baseMass: 50.0)` |

### Backend Dependencies

```json
{
  "require": {
    "php": "^8.4",
    "slim/slim": "^4.0",
    "slim/psr7": "^1.6",
    "nikic/fast-route": "^1.3"
  }
}
```

**Dependency Roles:**
- `slim/slim` - Core framework (routing, middleware, DI)
- `slim/psr7` - PSR-7 HTTP message implementation
- `nikic/fast-route` - High-performance URL router

---

## Frontend Stack

### Core Framework

**React 18** - Component-based UI library with concurrent rendering

**Why React 18?**
- Concurrent rendering for smooth UI
- Automatic batching for better performance
- Mature ecosystem (hooks, context, etc.)
- TypeScript first-class support

### Build Tool

**Vite 7** - Next-generation frontend build tool

**Why Vite?**
- Lightning-fast HMR (Hot Module Replacement)
- Native ES modules in development
- Optimized production builds (Rollup-based)
- TypeScript support out of the box

### Styling

**TailwindCSS v4** - Utility-first CSS framework

**Why Tailwind?**
- Rapid UI development with utility classes
- No CSS file clutter (styles in JSX)
- Responsive design made easy
- Customizable theme (via tailwind.config.js)

### Frontend Dependencies

```json
{
  "dependencies": {
    "react": "^18.3.1",
    "react-dom": "^18.3.1",
    "axios": "^1.7.9",
    "react-hook-form": "^7.54.2",
    "recharts": "^2.15.0",
    "lodash": "^4.17.21"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.3.4",
    "vite": "^7.0.0",
    "typescript": "~5.6.2",
    "tailwindcss": "^4.0.0",
    "postcss": "^8.4.49",
    "autoprefixer": "^10.4.20",
    "eslint": "^9.17.0"
  }
}
```

**Dependency Roles:**
- `axios` - HTTP client for API calls
- `react-hook-form` - Form state management
- `recharts` - Charts for results visualization
- `lodash` - Utility functions (debounce, etc.)

---

## Key Dependencies

### Parent Project Integration

This GUI integrates with the parent X4 Cargo Sizes Mod codebase:

| Dependency | Source | Purpose |
|------------|--------|---------|
| **PhysicsCalculator** | Parent mod | Core physics calculations |
| **AdjustedDrag** | Parent mod | Drag force adjustments |
| **AdjustedInertia** | Parent mod | Rotational inertia adjustments |
| **AdjustedJerk** | Parent mod | Jerk (acceleration rate) adjustments |
| **ReductionTier** | Parent mod | Tier-based reduction system |
| **X4 Core Library** | composer dependency | Game data access (ships, engines) |

**Integration Pattern:**
```php
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;

// Backend services wrap parent mod classes
$calculator = new PhysicsCalculator($baseMass, $originalCargo, ...);
```

### X4 Core Library

**mistralys/x4-core** - Game data extraction and access

**Key Classes Used:**
- `ShipDefs` - Ship database
- `EngineDefs` - Engine database
- `X4Exception` - Exception hierarchy

---

## Architectural Patterns

### 1. **Stateless REST API**

Backend is fully stateless - no sessions, no in-memory state.

**Benefits:**
- Simple deployment (just start PHP built-in server)
- Easy testing (no state to manage)
- Horizontally scalable (though not needed for local dev)

**Example:**
```php
// Each request is independent
POST /api/calculate/physics
{
  "baseMass": 50000,
  "cargoMultiplier": 4.0,
  // ... all parameters in request
}
```

### 2. **Service Layer Pattern**

All business logic wrapped in service classes.

**Structure:**
```
Endpoint → Service → Business Logic
   ↓         ↓            ↓
HTTP I/O   API      PhysicsCalculator
```

**Example:**
```php
class PhysicsEndpoint {
    public function __construct(private PhysicsService $service) {}
    
    public function calculate(Request $req, Response $res): Response {
        $dto = PhysicsRequest::fromArray($req->getParsedBody());
        $result = $this->service->calculatePhysics($dto);
        return $res->withJson($result);
    }
}
```

**Benefits:**
- Thin endpoints (only HTTP concerns)
- Testable service layer
- Reusable business logic

### 3. **Data Transfer Objects (DTOs)**

Type-safe contracts between frontend and backend.

**Pattern:**
```php
class PhysicsRequest {
    public function __construct(
        public readonly float $baseMass,
        public readonly float $cargoMultiplier,
        // ...
    ) {}
    
    public static function fromArray(array $data): self {
        // Validation and construction
    }
}
```

**Benefits:**
- Type safety (PHP 8.4 readonly properties)
- Contract-first design
- Mirrors TypeScript types exactly

### 4. **React Hooks Pattern**

Custom hooks encapsulate stateful logic.

**Example:**
```typescript
function usePhysicsCalculation() {
  const [result, setResult] = useState<PhysicsResponse | null>(null);
  const [loading, setLoading] = useState(false);
  
  const calculate = useCallback((config: PhysicsConfig) => {
    // Debounce and API call
  }, []);
  
  return { result, loading, calculate };
}
```

**Benefits:**
- Reusable stateful logic
- Clean component code
- Easy testing

### 5. **Debounced API Calls**

300ms debounce on physics calculations to prevent API spam.

**Implementation:**
```typescript
// In usePhysicsCalculation hook
const calculate = useCallback((config: PhysicsConfig) => {
  clearTimeout(debounceTimerRef.current);
  
  debounceTimerRef.current = setTimeout(async () => {
    const response = await physicsApi.calculate(config);
    setResult(response);
  }, 300); // 300ms debounce
}, []);
```

**Result:** User experience target of <500ms feedback (300ms debounce + ~100ms API call).

### 6. **Component Composition**

UI built from composable, single-responsibility components.

**Structure:**
```
App
├── Layout
│   └── Header
├── ConfigPanel
│   ├── CargoMultiplierSlider
│   ├── DragReductionSlider
│   └── TierEditor
├── ShipSelector
│   ├── ShipTypeDropdown
│   └── ShipListTable
└── ResultsPanel
    ├── MassRatioDisplay
    ├── DragComparisonChart
    └── EnginePerformanceCard
```

**Benefits:**
- Easy to reason about
- Reusable components
- Testable in isolation

### 7. **Middleware Pattern**

CORS support via middleware for cross-origin requests.

**Implementation:**
```php
class CorsMiddleware {
    public function __invoke(Request $req, RequestHandler $handler): Response {
        $response = $handler->handle($req);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}
```

**Usage:**
```php
$app->add(new CorsMiddleware());
```

### 8. **Configuration as Code**

All build configuration stored in JSON files (synchronous file I/O).

**Pattern:**
```php
class ConfigService {
    private const CONFIG_PATH = __DIR__ . '/../../../../config/build-config.json';
    
    public function getConfig(): array {
        $content = file_get_contents(self::CONFIG_PATH);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
```

**Benefits:**
- Human-readable configuration
- Version control friendly
- Shareable between GUI and CLI build system

---

## Build System

### Development Build

**Frontend:**
```bash
cd gui/frontend
npm run dev
# Starts Vite dev server on http://localhost:5173
# With HMR (Hot Module Replacement)
```

**Backend:**
```bash
cd gui/backend
php -S localhost:8080 -t public
# Starts PHP built-in server
```

### Production Build

**Frontend only (backend runs as-is):**
```bash
cd gui/frontend
npm run build
# Output: dist/ directory with optimized static assets
```

### Composer Scripts

From project root:
```bash
composer gui:install       # Install all dependencies
composer gui:start-win     # Start both servers (Windows)
```

---

## Deployment Model

### Local Development Only

This GUI is **NOT intended for production deployment**. It's a local development tool.

**Characteristics:**
- No authentication/authorization
- No database (file-based config)
- Localhost-only servers
- No HTTPS/encryption
- No multi-user support

**Rationale:**
- Target audience: Mod developers tuning physics
- Use case: Adjust parameters, export to build-config.json, run `composer build`
- Security model: Local filesystem access only

---

## Type System Integration

### Backend → Frontend Type Mirroring

PHP DTOs and TypeScript types are **intentionally identical**:

**PHP DTO:**
```php
class PhysicsRequest {
    public function __construct(
        public readonly float $baseMass,
        public readonly float $cargoMultiplier,
        public readonly bool $useEffectiveRatioCap
    ) {}
}
```

**TypeScript Type:**
```typescript
export interface PhysicsConfig {
  baseMass: number;
  cargoMultiplier: number;
  useEffectiveRatioCap: boolean;
}
```

**Benefits:**
- Contract-first API design
- Frontend ↔ Backend compatibility guaranteed
- IDE autocomplete for API calls
- Compile-time type checking

---

## Performance Targets

| Metric | Target | Notes |
|--------|--------|-------|
| **API Response Time** | <100ms | Physics calculation endpoint |
| **Frontend Render** | <16ms | 60fps target |
| **Debounce Delay** | 300ms | Prevents API spam |
| **Total User Feedback** | <500ms | Debounce + API + render |
| **Frontend Bundle Size** | <500KB | Gzipped production build |

---

## Security Considerations

### Local Development Only

- ✅ **No authentication** - Not needed for local tool
- ✅ **No SQL injection** - No database
- ✅ **File I/O restricted** - Only config/build-config.json
- ✅ **No user-generated code execution** - Read-only JSON parsing
- ✅ **CORS open** - Localhost-to-localhost only

### What's NOT Implemented (Intentionally)

- ❌ User authentication
- ❌ Authorization/permissions
- ❌ HTTPS/TLS
- ❌ Rate limiting
- ❌ Input sanitization for HTML (not needed - JSON API)
- ❌ CSRF protection (stateless API)

---

**End of Tech Stack Documentation**

