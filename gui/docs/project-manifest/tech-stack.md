# Tech Stack & Architectural Patterns

> **Version:** 1.3  
> **Last Updated:** February 16, 2026  
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

### 9. **Class-Wide Aggregation Pattern**

Aggregate physics calculations across all ships of a type to show min/max/median ranges and identify worst/best case ships.

**Purpose:**  
Modders need to understand the full impact of physics changes across an entire ship class, not just one ship. This pattern efficiently computes class-wide metrics and identifies edge cases.

**Implementation:**
```php
class ClassRangeService {
    public function calculateClassRange(ClassRangeRequest $request): ClassRangeResponse {
        // 1. Get all ships of requested type
        $ships = $this->shipDataService->getShipsByType($request->shipType);
        
        // 2. Calculate physics for each ship
        $shipMetrics = [];
        foreach ($ships as $shipDef) {
            $metrics = $this->calculateShipMetrics($shipDef, $request);
            if ($metrics !== null) { // Skip ships with zero cargo
                $shipMetrics[] = $metrics;
            }
        }
        
        // 3. Aggregate into min/max/median ranges
        $ranges = $this->aggregateRanges($shipMetrics);
        
        // 4. Identify worst/best cases
        $worstCase = $this->findWorstCase($shipMetrics);
        $bestCase = $this->findBestCase($shipMetrics);
        
        return new ClassRangeResponse(
            shipCount: count($shipMetrics),
            metrics: $ranges,
            worstCase: $worstCase,
            bestCase: $bestCase
        );
    }
}
```

**Frontend Integration:**
```typescript
// Separate hook with 500ms debounce (vs 300ms for single-ship)
function useClassRange() {
  const [result, setResult] = useState<ClassRangeResponse | null>(null);
  
  const calculate = useCallback((request: ClassRangeRequest) => {
    clearTimeout(debounceTimerRef.current);
    
    debounceTimerRef.current = setTimeout(async () => {
      const response = await classRangeApi.calculate(request);
      setResult(response);
    }, 500); // Longer debounce for heavier computation
  }, []);
  
  return { result, loading, calculate };
}
```

**Benefits:**
- Modders see worst-case and best-case ships immediately
- Min/max/median ranges show full distribution
- Performance optimized: 80 ships × calculations complete in <100ms
- Separate debounce tuning (500ms vs 300ms) prevents API spam

**Key Optimizations:**
- Ships with zero cargo skipped early (prevents division-by-zero)
- Shared `PhysicsCalculator` instance reused across iterations
- Engine-dependent metrics (top speed, acceleration) only calculated when engineId provided
- Efficient median calculation: sort once, index middle value (O(n log n))

---

### 10. **Shared Calculation Utilities via Trait**

Eliminate code duplication by extracting shared physics calculations into a reusable trait.

**Pattern:** `PhysicsCalculationHelper` trait provides common calculation methods used across multiple services.

**Location:** `gui/backend/src/Utils/PhysicsCalculationHelper.php`  
**Since:** 1.2.0

**Problem Solved:**  
Both `PhysicsService` and `ClassRangeService` needed identical methods for:
- Calculating percentage changes
- Computing average drag changes across 7 axes
- Computing average inertia changes across 3 axes

**Solution:** Extract into a trait that both services can mix in.

**Implementation:**
```php
/**
 * Shared physics calculation utilities.
 */
trait PhysicsCalculationHelper
{
    /**
     * Calculate percentage change between original and modified values.
     */
    private function calculatePercentChange(float $original, float $modified): float
    {
        if ($original == 0) {
            return 0.0;
        }
        return (($modified - $original) / $original) * 100.0;
    }
    
    /**
     * Calculate average drag change across all axes.
     */
    private function calculateAverageDragChange(
        Drag $original,
        AdjustedDrag $adjusted
    ): float {
        $changes = [
            $this->calculatePercentChange($original->getForward(), $adjusted->getForward()),
            $this->calculatePercentChange($original->getReverse(), $adjusted->getReverse()),
            // ... all 7 axes
        ];
        return array_sum($changes) / count($changes);
    }
    
    /**
     * Calculate average inertia change across all axes.
     */
    private function calculateAverageInertiaChange(
        Inertia $original,
        AdjustedInertia $adjusted
    ): float {
        $changes = [
            $this->calculatePercentChange($original->getPitch(), $adjusted->getPitch()),
            $this->calculatePercentChange($original->getYaw(), $adjusted->getYaw()),
            $this->calculatePercentChange($original->getRoll(), $adjusted->getRoll())
        ];
        return array_sum($changes) / count($changes);
    }
}
```

**Usage:**
```php
class PhysicsService
{
    use PhysicsCalculationHelper;
    
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
    {
        // Can now call trait methods directly
        $dragChange = $this->calculateAverageDragChange($original, $adjusted);
        $inertiaChange = $this->calculateAverageInertiaChange($original, $adjusted);
        // ...
    }
}

class ClassRangeService
{
    use PhysicsCalculationHelper;
    
    public function calculateClassRange(ClassRangeRequest $request): ClassRangeResponse
    {
        // Same methods available here too
        $percentChange = $this->calculatePercentChange($original, $modified);
        // ...
    }
}
```

**Benefits:**
- **DRY Principle:** Single source of truth for calculation logic
- **Maintainability:** Update calculation in one place, both services benefit
- **Testability:** Trait can have dedicated unit tests
- **Private Methods:** Calculation helpers remain implementation details (not part of public API)
- **Zero Overhead:** Traits copy code at compile time (no runtime cost)

**Why Trait vs Base Class?**
- Services have different responsibilities (don't share true inheritance relationship)
- Traits allow mixing utilities without coupling service hierarchies
- Services can use multiple traits if needed (PHP doesn't support multiple inheritance)

**See Also:**
- `public-api.md` → Backend Utilities → PhysicsCalculationHelper
- `file-tree.md` → `gui/backend/src/Utils/PhysicsCalculationHelper.php`

---

### 11. **Dependency Injection for Endpoints**

Endpoints accept service dependencies via constructor injection instead of instantiating them internally.

**Pattern:** Constructor injection for testability and explicit dependencies.

**Since:** 1.2.0 (applied to ClassRangeEndpoint)

**Problem:**
```php
// Old pattern - services instantiated inside endpoint
class ClassRangeEndpoint
{
    public function __construct()
    {
        $this->shipDataService = new ShipDataService();
        $this->classRangeService = new ClassRangeService($this->shipDataService);
    }
}
```

**Issues:**
- Hard to unit test (can't mock services)
- Dependencies hidden until reading constructor body
- Violates Dependency Inversion Principle (SOLID)

**Solution:**
```php
// New pattern - dependencies injected via constructor
class ClassRangeEndpoint
{
    public function __construct(
        private readonly ShipDataService $shipDataService,
        private readonly ClassRangeService $classRangeService
    ) {}
    
    public function calculate(Request $request, Response $response): Response
    {
        // Use $this->classRangeService directly
        $result = $this->classRangeService->calculateClassRange($dto);
        // ...
    }
}
```

**Router Instantiation:**
```php
// Router.php - manual dependency injection
public static function register(App $app): void
{
    // Instantiate services
    $shipDataService = new ShipDataService();
    $classRangeService = new ClassRangeService($shipDataService);
    
    // Inject into endpoint
    $classRangeEndpoint = new ClassRangeEndpoint($shipDataService, $classRangeService);
    $app->post('/api/calculate/class-range', [$classRangeEndpoint, 'calculate']);
}
```

**Benefits:**
- **Testability:** Can inject mock services for unit tests
- **Explicit Dependencies:** Constructor signature shows what's needed
- **SOLID Compliance:** Depends on abstractions, not concrete implementations
- **Lifecycle Control:** Services instantiated once per request in Router

**Why Not DI Container?**
- Small project (only 4 endpoints) doesn't justify framework overhead
- Manual instantiation in Router is simple and clear
- No complex dependency graphs to resolve

**Future:** If project grows to 20+ endpoints with complex dependencies, consider PHP-DI or Symfony DI container.

**See Also:**
- `constraints.md` → Dependency Injection Best Practices
- `public-api.md` → ClassRangeService constructor signature

---

### 12. **Parameter Object Pattern**

Methods with excessive parameters (>5) use parameter objects to group related values and improve maintainability.

**Pattern:** Encapsulate related parameters in DTOs with readonly properties.

**Since:** 1.3.0 (applied to PhysicsService::buildPhysicsResponse)

**Problem:**
```php
// Old pattern - 11 parameters
private function buildPhysicsResponse(
    PhysicsCalculator $calculator,
    Drag $originalDrag,
    AdjustedDrag $adjustedDrag,
    Inertia $originalInertia,
    AdjustedInertia $adjustedInertia,
    Jerk $originalJerk,
    AdjustedJerk $adjustedJerk,
    ReductionTier $dragTier,
    ReductionTier $jerkTier,
    PhysicsRequest $request,
    ?EnginePerformance $enginePerformance
): PhysicsResponse
```

**Issues:**
- Hard to remember parameter order
- IDE autocomplete becomes unwieldy
- Adding new physics types requires signature changes
- Poor cohesion - related parameters scattered across signature

**Solution:**
```php
// New pattern - 5 parameters using DTOs
private function buildPhysicsResponse(
    PhysicsCalculator $calculator,
    PhysicsData $physicsData,           // Groups 6 physics parameters
    ReductionTiers $tiers,              // Groups 2 tier parameters
    PhysicsRequest $request,
    ?EnginePerformance $enginePerformance
): PhysicsResponse
```

**Parameter Objects:**
```php
// Groups original and adjusted physics values
final readonly class PhysicsData
{
    public function __construct(
        public Drag $originalDrag,
        public AdjustedDrag $adjustedDrag,
        public Inertia $originalInertia,
        public AdjustedInertia $adjustedInertia,
        public Jerk $originalJerk,
        public AdjustedJerk $adjustedJerk
    ) {}
}

// Groups reduction tier configuration
final readonly class ReductionTiers
{
    public function __construct(
        public ReductionTier $drag,
        public ReductionTier $jerk
    ) {}
    
    public function getActiveTierLabel(): string
    {
        return sprintf(
            'Drag: %.0f%% reduction | Jerk: %.0f%% reduction',
            $this->drag->getReductionPercent() * 100,
            $this->jerk->getReductionPercent() * 100
        );
    }
}
```

**Usage at Call Site:**
```php
// Clear intent - creating physics data package
$physicsData = new PhysicsData(
    $originalDrag, $adjustedDrag,
    $originalInertia, $adjustedInertia,
    $originalJerk, $adjustedJerk
);
$tiers = new ReductionTiers($dragTier, $jerkTier);

return $this->buildPhysicsResponse(
    $calculator,
    $physicsData,
    $tiers,
    $request,
    $enginePerformance
);
```

**Benefits:**
- **Reduced Parameter Count:** 11 → 5 (55% reduction)
- **Improved Cohesion:** Related data grouped together
- **Better Readability:** Intent clear at call site
- **Easier to Extend:** Add physics types without changing signature
- **Encapsulation:** `getActiveTierLabel()` moves logic into DTO
- **Type Safety:** PHP 8.4 readonly properties prevent mutation

**When to Use:**
- Method has >5 parameters
- Parameters fall into cohesive groups (e.g., original/adjusted pairs)
- Multiple methods share same parameter groups
- Adding new parameters would make method unreadable

**When NOT to Use:**
- Parameters are unrelated (no cohesion)
- Only 2-4 parameters total
- One-off method with no shared parameter patterns

**See Also:**
- `public-api.md` → DTOs → PhysicsData, ReductionTiers
- `file-tree.md` → `gui/backend/src/DTOs/`
- Martin Fowler's "Introduce Parameter Object" refactoring

---

## Performance Considerations

### Median Calculation Strategy

**Current Implementation:** Array sort() with O(n log n) complexity

**Performance Profile:**
- Dataset size: ~80 ships per ship type
- Overhead: ~0.5ms per calculation
- Acceptable: For datasets <1000 items

**Optimization Threshold:** If ship counts exceed 1000 per type

**Recommended Algorithm:** Quickselect
- Complexity: O(n) average case, O(n²) worst case
- Implementation: Randomized pivot selection for worst-case protection
- Reference: [Quickselect - Wikipedia](https://en.wikipedia.org/wiki/Quickselect)

**Trade-offs:**

| Aspect | sort() (Current) | quickselect (Future) |
|--------|------------------|----------------------|
| Complexity | O(n log n) always | O(n) average case |
| Implementation | 3 lines (built-in) | ~30 lines (custom) |
| Stability | Stable sort | Not required for median |
| Worst Case | Predictable | O(n²) if poor pivot |
| Maintenance | Zero (language built-in) | Low (well-known algorithm) |

**Decision Rationale:**

Current dataset size (80 items) has negligible performance impact. Implementing quickselect now would violate the "No Premature Optimization" principle from `constraints.md`. When ship counts exceed 1000 items (13x current size), the optimization effort becomes justified.

**Action Trigger:**

If profiling shows median calculation >5ms consistently, or dataset size grows beyond 1000 items per type, implement quickselect with randomized pivot selection.

**Implementation Location:**
- `gui/backend/src/Services/ClassRangeService.php` → `computeMedian()` method
- See inline comments for detailed optimization guidance

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

