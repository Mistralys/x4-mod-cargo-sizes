# Constraints & Conventions

> **Version:** 1.1  
> **Last Updated:** February 15, 2026  
> **Purpose:** Non-negotiable rules and established conventions

---

## Table of Contents

1. [Critical Constraints](#critical-constraints)
2. [PHP Backend Constraints](#php-backend-constraints)
3. [TypeScript Frontend Constraints](#typescript-frontend-constraints)
4. [File I/O Constraints](#file-io-constraints)
5. [API Design Constraints](#api-design-constraints)
6. [Naming Conventions](#naming-conventions)
7. [Code Style](#code-style)
8. [Testing Constraints](#testing-constraints)
9. [Performance Constraints](#performance-constraints)
10. [Security Constraints](#security-constraints)

---

## Critical Constraints

### ⚠️ MUST FOLLOW - Non-Negotiable

| Constraint | Rationale | Example |
|------------|-----------|---------|
| **All File I/O Must Be Synchronous** | Simple, predictable, no async complexity | `file_get_contents()` NOT `fopen()` streams |
| **No Database Connections** | Config stored in JSON files only | Use `build-config.json` only |
| **Strict Types Everywhere (PHP)** | Type safety, catch errors early | `declare(strict_types=1);` |
| **TypeScript Strict Mode** | Type safety on frontend | `"strict": true` in tsconfig.json |
| **No User-Generated Code Execution** | Security | Never use `eval()`, `exec()`, etc. |
| **Readonly Properties for DTOs** | Immutability | `public readonly float $baseMass` |
| **Local Development Only** | Not a production web app | No auth, no multi-user, localhost only |

---

## PHP Backend Constraints

### 1. **Strict Types Declaration**

**RULE:** Every PHP file MUST start with `declare(strict_types=1);`

```php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;
```

**Rationale:** Prevents type coercion bugs, enforces contract compliance.

---

### 2. **Type Hints Everywhere**

**RULE:** All function parameters and return types MUST have type hints.

```php
// ✅ CORRECT
public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
{
    // ...
}

// ❌ WRONG
public function calculatePhysics($request)
{
    // ...
}
```

**Exceptions:** None. Use `void` for functions with no return value.

---

### 3. **Readonly Properties for DTOs**

**RULE:** DTO properties MUST be `readonly` to enforce immutability.

```php
// ✅ CORRECT
class PhysicsRequest {
    public function __construct(
        public readonly float $baseMass,
        public readonly float $cargoMultiplier
    ) {}
}

// ❌ WRONG
class PhysicsRequest {
    public float $baseMass;
    public float $cargoMultiplier;
}
```

**Rationale:** DTOs should be immutable value objects.

---

### 4. **Exception Hierarchy**

**RULE:** All exceptions MUST extend `CargoSizeException` (parent project's base exception).

```php
namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;

class GUIException extends CargoSizeException
{
    public const int ERROR_UNHANDLED_SHIP_TYPE = 12001;
    
    public static function invalidConfiguration(string $reason): self
    {
        return new self(
            "Invalid configuration: $reason",
            '',
            self::ERROR_UNHANDLED_SHIP_TYPE
        );
    }
}
```

---

### 5. **Namespace Convention**

**RULE:** All GUI code MUST use namespace: `Mistralys\X4\Mods\CargoSizesMod\GUI\*`

**Structure:**
```
Mistralys\X4\Mods\CargoSizesMod\GUI
├── API
│   ├── Endpoints
│   └── Middleware
├── Services
├── DTOs
└── Exceptions
```

---

### 6. **PSR-12 Coding Standard**

**RULE:** Follow PSR-12 coding style for all PHP code.

**Key Rules:**
- Opening braces on same line (methods, classes)
- 4 spaces for indentation (NO tabs)
- Unix line endings (LF)
- One class per file
- Filename matches class name

---

## TypeScript Frontend Constraints

### 1. **Strict Mode Enabled**

**RULE:** TypeScript strict mode MUST be enabled in tsconfig.json.

```json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "strictFunctionTypes": true,
    "strictPropertyInitialization": true
  }
}
```

**Rationale:** Catch type errors at compile time.

---

### 2. **No Implicit Any**

**RULE:** Never use `any` type unless explicitly necessary (and document why).

```typescript
// ✅ CORRECT
function calculate(config: PhysicsConfig): PhysicsResponse {
  // ...
}

// ❌ WRONG
function calculate(config: any): any {
  // ...
}
```

**Exceptions:** External library quirks where types are unavailable (use `unknown` instead).

---

### 3. **Type Definition Files**

**RULE:** All shared types MUST be defined in `.d.ts` files under `src/types/`.

**Structure:**
```
src/types/
├── physics.d.ts     # Physics calculation types
├── ships.d.ts       # Ship and engine types
└── config.d.ts      # Configuration types
```

**Example:**
```typescript
// physics.d.ts
export interface PhysicsConfig {
  baseMass: number;
  cargoMultiplier: number;
}
```

---

### 4. **React Functional Components Only**

**RULE:** All components MUST be functional components (no class components).

```tsx
// ✅ CORRECT
export function ConfigPanel({ config, onChange }: ConfigPanelProps) {
  return <div>...</div>;
}

// ❌ WRONG
export class ConfigPanel extends React.Component {
  render() {
    return <div>...</div>;
  }
}
```

**Rationale:** React hooks ecosystem, simpler code, better performance.

---

### 5. **Named Exports for Components**

**RULE:** Components MUST use named exports (not default exports).

```typescript
// ✅ CORRECT
export function ConfigPanel() { ... }

// ❌ WRONG
export default function ConfigPanel() { ... }
```

**Rationale:** Better IDE autocomplete, easier refactoring.

---

## File I/O Constraints

### 1. **Synchronous File I/O Only**

**RULE:** All file operations MUST be synchronous.

```php
// ✅ CORRECT
$content = file_get_contents($path);
file_put_contents($path, $content);

// ❌ WRONG (async patterns not used)
$stream = fopen($path, 'r');
fread($stream, 1024);
```

**Rationale:** Simplicity, predictability, no async complexity for local file operations.

---

### 2. **Only One Config File**

**RULE:** Only `config/build-config.json` may be read/written by the GUI.

**Allowed:**
- Read: `config/build-config.json`
- Write: `config/build-config.json`

**Forbidden:**
- Writing to any other files
- Creating new config files
- Modifying parent mod source files

---

### 3. **JSON Error Handling**

**RULE:** Always use `JSON_THROW_ON_ERROR` flag for JSON operations.

```php
// ✅ CORRECT
$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

// ❌ WRONG
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // ...
}
```

**Rationale:** Cleaner exception handling, consistent error reporting.

---

## API Design Constraints

### 1. **Stateless API**

**RULE:** Backend API MUST be completely stateless.

**Forbidden:**
- Sessions (`$_SESSION`)
- In-memory state between requests
- Global variables for state

**Required:**
- All parameters in request body
- Each request is independent

---

### 2. **JSON Only**

**RULE:** All API requests and responses MUST be JSON with `Content-Type: application/json`.

```php
// Request
Content-Type: application/json
{"baseMass": 50000, "cargoMultiplier": 4.0}

// Response
Content-Type: application/json
{"massRatio": 1.3, "effectiveRatio": 1.3, ...}
```

**No HTML, XML, or other formats.**

---

### 3. **Error Response Format**

**RULE:** All API errors MUST return consistent JSON structure.

```json
{
  "error": "Error message describing what went wrong"
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server-side error

---

### 4. **CORS Always Enabled**

**RULE:** CORS middleware MUST be applied to all routes for cross-origin requests.

```php
$app->add(new CorsMiddleware());
```

**Headers:**
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Headers: Content-Type, Accept`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`

**Rationale:** Frontend (5173) and backend (8080) on different ports = cross-origin.

---

## Naming Conventions

### PHP

| Element | Convention | Example |
|---------|-----------|---------|
| **Classes** | PascalCase | `PhysicsService` |
| **Methods** | camelCase | `calculatePhysics()` |
| **Properties** | camelCase | `$baseMass` |
| **Constants** | SCREAMING_SNAKE_CASE | `ERROR_UNHANDLED_SHIP_TYPE` |
| **Namespaces** | PascalCase | `Mistralys\X4\Mods\CargoSizesMod\GUI\Services` |
| **Files** | Match class name | `PhysicsService.php` |

### TypeScript

| Element | Convention | Example |
|---------|-----------|---------|
| **Components** | PascalCase | `ConfigPanel` |
| **Hooks** | camelCase, start with `use` | `usePhysicsCalculation` |
| **Functions** | camelCase | `calculatePhysics` |
| **Variables** | camelCase | `baseMass` |
| **Types/Interfaces** | PascalCase | `PhysicsConfig` |
| **Constants** | SCREAMING_SNAKE_CASE | `API_BASE_URL` |
| **Files (components)** | Match component | `ConfigPanel.tsx` |
| **Files (hooks)** | Match hook | `usePhysicsCalculation.ts` |

---

## Code Style

### PHP Code Style

**PSR-12 Compliant:**
```php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

/**
 * Physics calculation service.
 */
class PhysicsService
{
    /**
     * Constructor with dependency injection.
     */
    public function __construct(private ConfigService $configService)
    {
    }
    
    /**
     * Calculate physics values.
     *
     * @param PhysicsRequest $request Input parameters
     * @return PhysicsResponse Calculated results
     */
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
    {
        // Implementation (4-space indent)
    }
}
```

### TypeScript Code Style

**ESLint + Prettier Enforced:**
```typescript
/**
 * Physics calculation hook with debouncing.
 */
export function usePhysicsCalculation(): UsePhysicsCalculationResult {
  const [result, setResult] = useState<PhysicsResponse | null>(null);
  const [loading, setLoading] = useState(false);
  
  const calculate = useCallback((config: PhysicsConfig) => {
    // Implementation (2-space indent for TS)
  }, []);
  
  return { result, loading, calculate };
}
```

**Indentation:**
- PHP: 4 spaces
- TypeScript/JSX: 2 spaces

---

## Testing Constraints

### Backend Testing (TODO)

**Status:** Not yet implemented.

**Planned:**
- PHPUnit tests for services
- Mock X4 Core dependencies
- Test DTOs, validation, file I/O

### Frontend Testing (TODO)

**Status:** Not yet implemented.

**Planned:**
- Vitest for unit tests
- React Testing Library for component tests
- Mock axios API calls

---

## Performance Constraints

### 1. **API Response Time Target**

**RULE:** Physics calculation endpoint MUST respond in <100ms.

**Measured:** From request received to response sent.

---

### 2. **Debounce Delays**

**RULE:** Frontend MUST debounce calculations to prevent API spam.

**Required Debounce Values:**

| Calculation Type | Debounce | Hook | Rationale |
|-----------------|----------|------|-----------|
| **Single-Ship Physics** | 300ms | `usePhysicsCalculation` | Fast response for real-time feedback |
| **Class-Wide Range** | 500ms | `useClassRange` | Heavier computation (~80 ships) needs longer debounce |

**Implementation Example:**

```typescript
// Single-ship: 300ms
function usePhysicsCalculation() {
  debounceTimerRef.current = setTimeout(async () => {
    await physicsApi.calculate(config);
  }, 300);
}

// Class-range: 500ms
function useClassRange() {
  debounceTimerRef.current = setTimeout(async () => {
    await classRangeApi.calculate(request);
  }, 500);
}
```

**Why Different Debounce Values?**
- **300ms** for single-ship: Balances responsiveness with API efficiency
- **500ms** for class-range: Accounts for heavier backend computation (iterating ~80 ships)

**Combined User Experience:**
- Single-ship results appear at ~400ms (300ms debounce + ~100ms API)
- Class-range results appear at ~600ms (500ms debounce + ~100ms API)
- Both feel near-instantaneous to the user

---

### 3. **No Premature Optimization**

**RULE:** Don't optimize unless profiling shows a bottleneck.

**Exception:** Known performance anti-patterns (e.g., no debouncing = API spam).

---

## Security Constraints

### 1. **No User-Generated Code Execution**

**RULE:** Never use `eval()`, `exec()`, `shell_exec()`, or similar functions.

```php
// ❌ FORBIDDEN
eval($userInput);
exec($userCommand);

// ✅ CORRECT
$data = json_decode($userInput, true, 512, JSON_THROW_ON_ERROR);
```

---

### 2. **File System Access Restricted**

**RULE:** Only `config/build-config.json` may be accessed.

**Forbidden:**
- Reading user home directories
- Writing to system paths
- Traversing parent directories beyond project root

---

### 3. **JSON Parsing Only**

**RULE:** Never parse or execute non-JSON formats from user input.

**Allowed:**
- JSON (with `JSON_THROW_ON_ERROR`)

**Forbidden:**
- XML parsing of user input
- YAML parsing
- INI files from user
- Serialized PHP objects

---

### 4. **No Authentication Required**

**RULE:** This is a local development tool - no auth layer.

**Rationale:** Target audience is mod developers running on their own machine.

**Implication:** Never deploy this publicly.

---

## Architectural Constraints

### 1. **Service Layer Pattern Required**

**RULE:** All business logic MUST go in service classes, NOT endpoints.

**Correct Structure:**
```
Endpoint → Service → Business Logic
```

**Forbidden:**
- Business logic in endpoint handlers
- Direct PhysicsCalculator instantiation in endpoints

---

### 2. **DTOs for All API Contracts**

**RULE:** All API requests/responses MUST be defined as type-safe DTOs.

**Required:**
- PHP DTO class with readonly properties
- TypeScript interface mirroring PHP DTO
- `fromArray()` static factory method

---

### 3. **No Direct Database Access**

**RULE:** No SQL queries, no database connections.

**Rationale:** All data comes from JSON files or parent mod's X4 Core library integration.

---

## Documentation Constraints

### 1. **All Public Methods Must Have Docblocks**

**RULE:** Every public method MUST have a PHPDoc or JSDoc comment.

```php
/**
 * Calculate physics values.
 *
 * @param PhysicsRequest $request Input parameters
 * @return PhysicsResponse Calculated results
 * @throws GUIException If validation fails
 */
public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
```

---

### 2. **Update Manifest on Code Changes**

**RULE:** When adding/modifying public APIs, update the manifest.

**Files to Update:**
- `public-api.md` - Add new method signatures
- `data-flows.md` - Update flow diagrams if architecture changes
- `file-tree.md` - Add new files/directories
- `tech-stack.md` - Add new dependencies or patterns

---

## Version Control Constraints

### 1. **Commit Message Format**

**RULE:** Follow Conventional Commits specification.

```
<type>(<scope>): <description>

[optional body]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Build/tooling changes

**Examples:**
```
feat(frontend): add tier editor for jerk reduction
fix(backend): correct engine thrust calculation
docs(manifest): update public-api.md with new endpoints
refactor(hooks): extract debounce logic to utility
```

---

## Dependency Constraints

### 1. **No Additional Backend Dependencies**

**RULE:** Do not add new Composer dependencies without justification.

**Current dependencies are sufficient:**
- Slim Framework 4
- PSR-7 libraries
- FastRoute

**Rationale:** Keep backend lightweight, minimize attack surface.

---

### 2. **Frontend Dependencies Must Be Justified**

**RULE:** New npm packages must have clear value proposition.

**Ask:**
- Can we implement this ourselves in <100 lines?
- Does this duplicate existing dependency functionality?
- Is the package actively maintained?

---

**End of Constraints Documentation**

