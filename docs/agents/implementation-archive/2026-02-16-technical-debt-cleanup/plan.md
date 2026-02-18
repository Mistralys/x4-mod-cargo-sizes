# Plan: Technical Debt Cleanup - Post Gold Nuggets Optimization

**Plan ID:** 2026-02-16-technical-debt-cleanup  
**Created:** February 16, 2026  
**Context:** Follow-up to 2026-02-15-gold-nuggets-optimization  
**Trigger:** 5 non-blocking suggestions identified in synthesis report (TD-1 through TD-5)

---

## Summary

Address 5 technical debt items identified during the Gold Nuggets Optimization project. These items were deferred as non-blocking but represent opportunities for improved code quality, testability, and maintainability. Primary focus on **test infrastructure** (TD-3) and **service container pattern** (TD-4) as these provide the highest value for future development velocity.

**Technical Debt Items:**
- **TD-1:** Strict equality operators in PhysicsCalculationHelper (Code Style)
- **TD-2:** Parameter object pattern for buildPhysicsResponse() (Architecture)
- **TD-3:** ✨ **PHPUnit test infrastructure** for backend (Testing) - **HIGH VALUE**
- **TD-4:** ✨ **Service container for dependency management** (Performance/Architecture) - **HIGH VALUE**
- **TD-5:** Configurable context phrase thresholds (Configuration)

---

## Approach / Architecture

### Priority-Based Implementation

**Phase 1: Foundation (HIGH VALUE)**
1. **TD-3: Test Infrastructure** - Establish PHPUnit testing capability
2. **TD-4: Service Container** - Implement lightweight DI container

**Phase 2: Code Quality (MEDIUM VALUE)**
3. **TD-2: Parameter Object** - Refactor buildPhysicsResponse() to use DTO
4. **TD-1: Strict Equality** - Replace loose equality with strict comparison

**Phase 3: Configuration (LOW VALUE)**
5. **TD-5: Context Thresholds** - Extract hardcoded thresholds to config (defer if no product requirement)

### Architectural Approach

#### Test Infrastructure (TD-3)
```
gui/backend/
├── tests/                              # NEW: Test suite
│   ├── phpunit.xml                     # PHPUnit configuration
│   ├── bootstrap.php                   # Test bootstrap (autoloader)
│   │
│   ├── Unit/                           # Unit tests
│   │   ├── Utils/
│   │   │   └── PhysicsCalculationHelperTest.php  # Test trait methods
│   │   └── Services/
│   │       └── ClassRangeServiceTest.php          # Demonstrate DI mocking
│   │
│   └── Integration/                    # Integration tests (future)
│       └── Endpoints/
│           └── ClassRangeEndpointTest.php
```

#### Service Container (TD-4)
```php
// Pattern: Lightweight PSR-11-inspired container
class ServiceContainer {
    private array $services = [];
    private array $factories = [];
    
    public function register(string $id, callable $factory): void
    public function get(string $id): object
    public function has(string $id): bool
}

// Usage in Router.php
$container = new ServiceContainer();
$container->register('ship_data', fn() => new ShipDataService());
$container->register('class_range', fn($c) => new ClassRangeService($c->get('ship_data')));
```

#### Parameter Object (TD-2)
```php
// Before: 11 parameters
private function buildPhysicsResponse(
    PhysicsCalculator $calculator,
    PhysicsData $physicsData,
    ReductionTiers $tiers,
    PhysicsRequest $request,
    ?EnginePerformance $enginePerformance,
    // ... 6 more parameters
): PhysicsResponse

// After: Single DTO parameter
private function buildPhysicsResponse(PhysicsResponseData $data): PhysicsResponse

// New DTO
readonly class PhysicsResponseData {
    public function __construct(
        public PhysicsCalculator $calculator,
        public PhysicsData $physicsData,
        public ReductionTiers $tiers,
        public PhysicsRequest $request,
        public ?EnginePerformance $enginePerformance,
        // ... remaining properties
    ) {}
}
```

---

## Rationale

### Why Address These Now?

#### TD-3: Test Infrastructure (HIGH PRIORITY)
**Business Value:**
- Unlocks DI pattern benefits introduced in WP-005 (dependency injection)
- Enables confident refactoring without manual regression testing
- Demonstrates testing best practices for future endpoint development
- Faster iteration velocity (automated tests vs manual API testing)

**Current Pain:**
- DI pattern exists but cannot be unit tested (no mocking infrastructure)
- Manual API testing for every change (slow, error-prone)
- No confidence in refactoring safety

**ROI:** HIGH - Enables faster development with confidence

---

#### TD-4: Service Container (MEDIUM PRIORITY)
**Business Value:**
- Eliminates service instantiation duplication in Router.php
- Enables singleton pattern for expensive service initialization (future-proofing)
- Provides centralized service lifecycle management
- Reduces coupling between Router and service constructors

**Current Pain:**
- Router.php creates new instances for every endpoint (currently negligible overhead)
- Service dependencies hard-coded in Router (tight coupling)
- Adding new services requires Router modifications

**Trigger Point:** If service initialization exceeds 5ms OR services need shared state

**ROI:** MEDIUM - Future-proofing without current performance issue

---

#### TD-2: Parameter Object Pattern (MEDIUM PRIORITY)
**Business Value:**
- Reduces cognitive load (1 parameter vs 11)
- Easier to extend (add properties to DTO vs method signature changes)
- Type-safe parameter passing (readonly DTO)
- Follows established DTO pattern (PhysicsRequest, PhysicsResponse)

**Current Pain:**
- `buildPhysicsResponse()` has 11 parameters (cognitive load)
- Adding new response data requires signature changes

**ROI:** MEDIUM - Maintainability improvement

---

#### TD-1: Strict Equality (LOW PRIORITY)
**Business Value:**
- Type safety (prevents ==0 edge cases)
- Consistency with codebase standards (strict types everywhere)
- Prevents subtle bugs from type coercion

**Current Pain:**
- `calculatePercentChange()` uses `==` instead of `===`
- Theoretically could cause bugs if $original is null/false (edge case)

**Trade-off:** Backward compatibility - current code may rely on type coercion

**ROI:** LOW - Theoretical safety improvement, no known bugs

---

#### TD-5: Configurable Thresholds (LOW PRIORITY)
**Business Value:**
- User-configurable context phrases without code changes
- A/B testing of threshold values
- Per-user customization (if multi-user ever needed)

**Current Pain:**
- Context phrase thresholds hard-coded in TypeScript
- No product requirement for configurability

**Action:** DEFER until product requirement emerges

**ROI:** LOW - No current requirement

---

## Detailed Steps

### Phase 1: Foundation

#### TD-3: Establish PHPUnit Test Infrastructure

**Step 3.1: Install PHPUnit**
```bash
cd gui/backend
composer require --dev phpunit/phpunit:^11.0
```

**Step 3.2: Create Directory Structure**
```bash
mkdir -p tests/Unit/Utils
mkdir -p tests/Unit/Services
mkdir -p tests/Integration/Endpoints
```

**Step 3.3: Create phpunit.xml Configuration**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <report>
            <html outputDirectory="coverage"/>
        </report>
    </coverage>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Step 3.4: Create Test Bootstrap**
```php
<?php
// tests/bootstrap.php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap X4 Core (if needed for test fixtures)
require_once __DIR__ . '/../../dev-config.php';
```

**Step 3.5: Write PhysicsCalculationHelperTest**
- Test `calculatePercentChange()` with positive/negative/zero values
- Test `calculateAverageDragChange()` with drag fixtures
- Test `calculateAverageInertiaChange()` with inertia fixtures
- Goal: Demonstrate trait testing pattern

**Step 3.6: Write ClassRangeServiceTest**
- Create mock ShipDataService using PHPUnit's mock builder
- Inject mock into ClassRangeService constructor (DI pattern)
- Test `calculateClassRangeData()` with controlled fixtures
- Goal: Demonstrate DI mocking benefits

**Step 3.7: Add Composer Script**
```json
{
  "scripts": {
    "test": "phpunit",
    "test:unit": "phpunit --testsuite=Unit",
    "test:coverage": "phpunit --coverage-html coverage/"
  }
}
```

**Step 3.8: Run Tests and Verify**
```bash
composer test
# Expected: All tests PASS, code coverage report generated
```

---

#### TD-4: Implement Service Container

**Step 4.1: Create ServiceContainer Class**
```php
// src/API/ServiceContainer.php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API;

/**
 * Lightweight dependency injection container.
 *
 * Provides service registration and retrieval with lazy instantiation.
 * Singleton pattern: services instantiated once and reused.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - API
 * @since 1.3.0
 */
class ServiceContainer
{
    /** @var array<string, object> Instantiated services (singletons) */
    private array $services = [];
    
    /** @var array<string, callable> Service factory functions */
    private array $factories = [];
    
    /**
     * Register a service factory.
     *
     * @param string $id Service identifier (e.g., 'ship_data')
     * @param callable $factory Factory function: function(ServiceContainer): object
     * @return void
     */
    public function register(string $id, callable $factory): void
    
    /**
     * Get a service instance (lazy instantiation, singleton).
     *
     * @param string $id Service identifier
     * @return object Service instance
     * @throws \RuntimeException if service not registered
     */
    public function get(string $id): object
    
    /**
     * Check if service is registered.
     *
     * @param string $id Service identifier
     * @return bool True if registered
     */
    public function has(string $id): bool
}
```

**Step 4.2: Update Router to Use Container**
```php
// src/API/Router.php
public static function register(App $app): void
{
    $container = new ServiceContainer();
    
    // Register services
    $container->register('ship_data', fn() => new ShipDataService());
    $container->register('physics', fn() => new PhysicsService());
    $container->register('class_range', fn($c) => new ClassRangeService($c->get('ship_data')));
    
    // Instantiate endpoints with container-managed services
    $physicsEndpoint = new PhysicsEndpoint($container->get('physics'));
    $classRangeEndpoint = new ClassRangeEndpoint(
        $container->get('ship_data'),
        $container->get('class_range')
    );
    
    // Register routes (unchanged)
    $app->post('/api/calculate/physics', [$physicsEndpoint, 'calculate']);
    $app->post('/api/calculate/class-range', [$classRangeEndpoint, 'calculate']);
    // ... remaining routes
}
```

**Step 4.3: Update PhysicsEndpoint to Accept Service**
```php
// src/API/Endpoints/PhysicsEndpoint.php
class PhysicsEndpoint
{
    public function __construct(
        private readonly PhysicsService $physicsService
    ) {}
    
    public function calculate(Request $request, Response $response): Response
    {
        // Use $this->physicsService instead of instantiating
    }
}
```

**Step 4.4: Add Container Tests**
```php
// tests/Unit/API/ServiceContainerTest.php
- Test service registration
- Test singleton behavior (same instance returned)
- Test lazy instantiation (factory not called until get())
- Test exception on missing service
```

**Step 4.5: Verify No Behavior Changes**
- Start backend: `composer gui:start-backend`
- Test all endpoints manually
- Verify response times unchanged (container overhead <1ms)

---

### Phase 2: Code Quality

#### TD-2: Extract Parameter Object for buildPhysicsResponse()

**Step 2.1: Create PhysicsResponseData DTO**
```php
// src/DTOs/PhysicsResponseData.php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Parameter object for buildPhysicsResponse() method.
 *
 * Encapsulates all data needed to construct a PhysicsResponse.
 * Reduces method signature from 11 parameters to 1.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - DTOs
 * @since 1.3.0
 */
readonly class PhysicsResponseData
{
    public function __construct(
        public PhysicsCalculator $calculator,
        public PhysicsData $physicsData,
        public ReductionTiers $tiers,
        public PhysicsRequest $request,
        public ?EnginePerformance $enginePerformance
    ) {}
}
```

**Step 2.2: Refactor buildPhysicsResponse() Signature**
```php
// Before (11 parameters)
private function buildPhysicsResponse(
    PhysicsCalculator $calculator,
    PhysicsData $physicsData,
    ReductionTiers $tiers,
    PhysicsRequest $request,
    ?EnginePerformance $enginePerformance
): PhysicsResponse

// After (1 parameter)
private function buildPhysicsResponse(PhysicsResponseData $data): PhysicsResponse
{
    return new PhysicsResponse(
        massRatio: $data->calculator->getMassRatio(),
        originalFullMass: $data->calculator->getOriginalFullMass(),
        // ... use $data->* throughout
    );
}
```

**Step 2.3: Update Call Site in calculatePhysics()**
```php
// Build parameter object
$responseData = new PhysicsResponseData(
    calculator: $calculator,
    physicsData: $physicsData,
    tiers: $tiers,
    request: $request,
    enginePerformance: $enginePerformance
);

// Pass single parameter
return $this->buildPhysicsResponse($responseData);
```

**Step 2.4: Add Unit Test**
```php
// tests/Unit/DTOs/PhysicsResponseDataTest.php
- Test constructor instantiation
- Test readonly property enforcement
```

**Step 2.5: Verify No Behavior Changes**
- Run PHPUnit tests
- Test physics endpoint manually
- Verify response structure unchanged

---

#### TD-1: Replace Loose Equality with Strict Equality

**Step 1.1: Research Backward Compatibility**
- Search codebase for all calls to `calculatePercentChange()`
- Analyze parameter types (always float? could be null/false?)
- Determine if type coercion is ever relied upon

**Step 1.2: Update PhysicsCalculationHelper**
```php
// Before
if ($original == 0.0) {
    return 0.0;
}

// After
if ($original === 0.0) {
    return 0.0;
}
```

**Step 1.3: Add Edge Case Tests**
```php
// tests/Unit/Utils/PhysicsCalculationHelperTest.php
public function testPercentChangeWithZeroOriginal(): void
{
    $result = $this->calculatePercentChange(0.0, 10.0);
    $this->assertSame(0.0, $result);
}

public function testPercentChangeStrictTypeEnforcement(): void
{
    $this->expectException(\TypeError::class);
    $this->calculatePercentChange(null, 10.0); // Should fail with strict types
}
```

**Step 1.4: Run Full Test Suite**
- Verify all tests pass
- Check for any unexpected behavior changes

---

### Phase 3: Configuration (CONDITIONAL)

#### TD-5: Extract Context Phrase Thresholds to Config

**Trigger Condition:** Product team requests user-configurable thresholds

**Step 5.1: Add to build-config.json Schema**
```json
{
  "contextThresholds": {
    "speed": {
      "S": { "verySlow": 50, "slow": 100, "moderate": 150, "fast": 200, "veryFast": 300 },
      "M": { "verySlow": 40, "slow": 80, "moderate": 120, "fast": 160, "veryFast": 250 },
      "L": { "verySlow": 30, "slow": 60, "moderate": 100, "fast": 140, "veryFast": 200 },
      "XL": { "verySlow": 20, "slow": 50, "moderate": 80, "fast": 120, "veryFast": 180 }
    },
    "acceleration": { /* similar structure */ }
  }
}
```

**Step 5.2: Create TypeScript Types**
```typescript
// frontend/src/types/contextThresholds.ts
export interface ShipSizeThresholds {
  verySlow: number;
  slow: number;
  moderate: number;
  fast: number;
  veryFast: number;
}

export interface ContextThresholds {
  speed: Record<ShipSize, ShipSizeThresholds>;
  acceleration: Record<ShipSize, ShipSizeThresholds>;
}
```

**Step 5.3: Load from Config API**
```typescript
// Load thresholds on app initialization
const config = await configApi.getConfig();
const thresholds = config.contextThresholds;

// Use in metricContext.ts
function getSpeedContext(value: number, shipSize: ShipSize): string {
  const thresholds = contextThresholds.speed[shipSize];
  if (value < thresholds.verySlow) return "Very Slow";
  // ... use loaded thresholds
}
```

**Step 5.4: Update Documentation**
- Add to constraints.md: Context threshold configuration rules
- Add to data-flows.md: Config loading flow for thresholds
- Add to public-api.md: ConfigService.getContextThresholds() method

**IF NOT TRIGGERED:** Skip this phase entirely (no product requirement)

---

## Dependencies

### External Dependencies

| Dependency | Version | Purpose | Installation |
|------------|---------|---------|--------------|
| **PHPUnit** | 11.0+ | Testing framework | `composer require --dev phpunit/phpunit` |

### Internal Dependencies

| Component | Location | Purpose |
|-----------|----------|---------|
| **X4 Core** | `../../x4-core/` | Game data access for test fixtures |
| **PhysicsCalculator** | `src/Mods/.../Output/Physics/` | Physics calculation logic (test target) |
| **ShipDefs** | `x4-core` | Ship data for integration tests |

### Optional Dependencies (TD-4)

No external dependencies needed for ServiceContainer (custom implementation).

**Alternative Considered:** PSR-11 compliant container (e.g., PHP-DI)
- **Decision:** Custom lightweight implementation
- **Rationale:** Avoid dependency bloat for simple use case, GUI is local-only

---

## Required Components

### New Files

| File Path | Purpose | Size Est. |
|-----------|---------|-----------|
| `gui/backend/tests/phpunit.xml` | PHPUnit configuration | ~30 lines |
| `gui/backend/tests/bootstrap.php` | Test autoloader | ~10 lines |
| `gui/backend/tests/Unit/Utils/PhysicsCalculationHelperTest.php` | Trait unit tests | ~150 lines |
| `gui/backend/tests/Unit/Services/ClassRangeServiceTest.php` | DI mocking demo | ~200 lines |
| `gui/backend/tests/Unit/API/ServiceContainerTest.php` | Container tests | ~150 lines |
| `gui/backend/src/API/ServiceContainer.php` | DI container | ~80 lines |
| `gui/backend/src/DTOs/PhysicsResponseData.php` | Parameter object | ~30 lines |

### Modified Files

| File Path | Changes | Risk |
|-----------|---------|------|
| `gui/backend/src/API/Router.php` | Add ServiceContainer instantiation | LOW - Isolated change |
| `gui/backend/src/API/Endpoints/PhysicsEndpoint.php` | Constructor injection | LOW - DI pattern already used |
| `gui/backend/src/API/Endpoints/ClassRangeEndpoint.php` | No change (already uses DI) | NONE |
| `gui/backend/src/Services/PhysicsService.php` | Refactor buildPhysicsResponse() | MEDIUM - Method signature change |
| `gui/backend/src/Utils/PhysicsCalculationHelper.php` | Replace == with === | LOW - Well-tested method |
| `gui/backend/composer.json` | Add PHPUnit dev dependency + scripts | LOW - Dev-only change |

### Tools & Scripts

| Tool | Purpose | Command |
|------|---------|---------|
| **PHPUnit** | Run test suite | `composer test` |
| **PHPUnit Coverage** | Code coverage report | `composer test:coverage` |
| **PHPUnit Unit Only** | Fast unit tests | `composer test:unit` |

---

## Assumptions

### Technical Assumptions

1. **PHPUnit 11.0+ is compatible** with PHP 8.4 and current Composer setup
2. **Service initialization overhead is <5ms** (container optimization unnecessary unless proven)
3. **Parameter object pattern** is acceptable for private methods (not just public APIs)
4. **Backward compatibility** is not broken by strict equality (no code relies on type coercion)
5. **Test fixtures** can use X4 Core data (ShipDefs, EngineDefs) without full game data extraction

### Process Assumptions

1. **Testing best practices** should follow PHPUnit 11 documentation
2. **Code coverage target:** Not specified (aim for >80% of new test files)
3. **CI/CD integration:** Not required (local development only)
4. **Performance benchmarking:** Manual testing acceptable (no automated performance tests)

### Product Assumptions

1. **TD-5 (context thresholds):** No product requirement exists, defer until requested
2. **Multi-user support:** Not needed (service container for performance, not state management)
3. **Deployment:** Local development only (no production deployment concerns)

---

## Constraints

### Architectural Constraints

**MUST FOLLOW:**
- All PHP files start with `declare(strict_types=1);`
- All function parameters and return types have type hints
- DTO properties use `readonly` keyword
- Test files follow PHPUnit 11 conventions
- Service container MUST NOT introduce state management (stateless API)

### Performance Constraints

**Container Overhead:**
- Service instantiation overhead MUST be <5ms per request
- Singleton pattern MUST NOT introduce stale data bugs (request-scoped services)
- Container factory functions MUST be lazy (not called until get())

### Backward Compatibility Constraints

**Strict Equality (TD-1):**
- MUST research all call sites before changing == to ===
- MUST add edge case tests before refactoring
- MUST verify no behavior changes with full test suite

### Testing Constraints

**Test Coverage:**
- Unit tests MUST cover PhysicsCalculationHelper (trait testing pattern)
- Integration tests SHOULD demonstrate DI mocking (ClassRangeService)
- Tests MUST run in <10 seconds (fast feedback loop)
- Tests MUST NOT require full X4 game data extraction (use minimal fixtures)

### Documentation Constraints

**Manifest Updates:**
- ALL changes MUST update relevant manifest documents
- tech-stack.md MUST document ServiceContainer pattern (#12)
- constraints.md MUST document testing conventions
- public-api.md MUST document PhysicsResponseData DTO
- file-tree.md MUST show tests/ directory structure
- Version bump: v1.2 → v1.3 (new patterns added)

---

## Out of Scope

### Explicitly NOT Included

1. **Full Integration Test Suite** - Only demonstration tests (ClassRangeEndpoint)
   - **Rationale:** Integration tests require running backend server, slower feedback
   - **Future Work:** Add when CI/CD pipeline exists

2. **Code Coverage Mandates** - No minimum coverage percentage enforced
   - **Rationale:** Local development project, not production SaaS
   - **Future Work:** Set targets when team grows

3. **Performance Benchmarking** - No automated performance regression tests
   - **Rationale:** Manual testing sufficient for local-only tool
   - **Future Work:** Add if service initialization becomes measurable bottleneck

4. **PSR-11 Compliance** - ServiceContainer not PSR-11 compliant
   - **Rationale:** Avoid dependency bloat for simple use case
   - **Future Work:** Migrate to PHP-DI or Symfony DIC if complexity grows

5. **Mock Framework** - Using PHPUnit's built-in mocking only
   - **Rationale:** Built-in mocking sufficient for DI demonstration
   - **Future Work:** Add Mockery/Prophecy if complex mocking needed

6. **TD-5 Implementation** - Context threshold configuration deferred
   - **Rationale:** No product requirement exists
   - **Trigger:** Implement only if product team requests user-configurable thresholds

7. **Frontend Unit Tests** - Only backend testing in this plan
   - **Rationale:** Frontend has separate testing strategy (Vitest)
   - **Scope:** Backend PHP testing only

8. **Mutation Testing** - No mutation testing framework (Infection)
   - **Rationale:** Overkill for current project size
   - **Future Work:** Consider if test quality becomes concern

---

## Acceptance Criteria

### TD-3: Test Infrastructure ✅

**Foundation:**
- [ ] PHPUnit 11.0+ installed via Composer (dev dependency)
- [ ] `tests/` directory structure created (Unit/, Integration/, bootstrap.php)
- [ ] `phpunit.xml` configuration file created and validates
- [ ] Composer scripts added: `test`, `test:unit`, `test:coverage`

**Test Coverage:**
- [ ] `PhysicsCalculationHelperTest.php` created with 6+ test methods
  - [ ] Tests `calculatePercentChange()` with positive/negative/zero values
  - [ ] Tests `calculateAverageDragChange()` with drag fixtures
  - [ ] Tests `calculateAverageInertiaChange()` with inertia fixtures
- [ ] `ClassRangeServiceTest.php` created with 3+ test methods
  - [ ] Demonstrates ShipDataService mocking with PHPUnit
  - [ ] Tests `calculateClassRangeData()` with controlled fixtures
  - [ ] Verifies DI pattern enables unit testing

**Execution:**
- [ ] `composer test` runs successfully (all tests PASS)
- [ ] Test execution time <10 seconds
- [ ] Code coverage report generates in `coverage/` directory
- [ ] All tests follow PHPUnit 11 conventions

---

### TD-4: Service Container ✅

**Implementation:**
- [ ] `ServiceContainer.php` created with register(), get(), has() methods
- [ ] Container implements lazy instantiation (factories not called until get())
- [ ] Container implements singleton pattern (same instance returned)
- [ ] Container throws exception when requesting unregistered service

**Integration:**
- [ ] `Router.php` updated to use ServiceContainer
- [ ] All endpoints receive services via container (PhysicsEndpoint, ClassRangeEndpoint)
- [ ] No service instantiation logic remains in endpoints (moved to container)

**Testing:**
- [ ] `ServiceContainerTest.php` created with 5+ test methods
  - [ ] Tests service registration
  - [ ] Tests singleton behavior
  - [ ] Tests lazy instantiation
  - [ ] Tests exception handling
  - [ ] Tests container->has() method

**Verification:**
- [ ] All endpoints functional (manual API testing)
- [ ] Response times unchanged (<1ms container overhead)
- [ ] No stale data bugs introduced (stateless services)

---

### TD-2: Parameter Object Pattern ✅

**Implementation:**
- [ ] `PhysicsResponseData.php` DTO created with 5 readonly properties
- [ ] `buildPhysicsResponse()` signature changed to accept single DTO parameter
- [ ] Call site updated in `calculatePhysics()` to construct DTO
- [ ] Method implementation updated to use `$data->*` throughout

**Testing:**
- [ ] `PhysicsResponseDataTest.php` created
  - [ ] Tests DTO construction
  - [ ] Tests readonly property enforcement
- [ ] Existing PhysicsService tests still pass (no behavior change)

**Verification:**
- [ ] Physics endpoint functional (manual testing)
- [ ] Response structure unchanged (JSON comparison)
- [ ] PHPStan reports no type errors

---

### TD-1: Strict Equality ✅

**Research:**
- [ ] All call sites to `calculatePercentChange()` identified
- [ ] Parameter types analyzed (always float, never null/false)
- [ ] Backward compatibility risk assessed (LOW)

**Implementation:**
- [ ] PhysicsCalculationHelper updated: `== 0.0` → `=== 0.0`
- [ ] Edge case tests added to PhysicsCalculationHelperTest
  - [ ] Tests zero original value with strict equality
  - [ ] Tests TypeError when non-float passed (proves strict types work)

**Verification:**
- [ ] Full test suite passes (no behavior changes)
- [ ] Manual physics endpoint testing (zero edge cases)

---

### TD-5: Context Thresholds (CONDITIONAL) ⏸️

**IF TRIGGERED:**
- [ ] Product requirement documented (threshold configurability requested)
- [ ] Schema added to `build-config.json`
- [ ] TypeScript types created (`contextThresholds.ts`)
- [ ] Frontend loads config on initialization
- [ ] `metricContext.ts` updated to use loaded thresholds
- [ ] Documentation updated (constraints.md, data-flows.md, public-api.md)

**IF NOT TRIGGERED:**
- [x] Phase 3 skipped (deferred until product requirement)

---

### Documentation ✅

**Manifest Updates:**
- [ ] tech-stack.md updated to v1.3
  - [ ] Pattern #12: Service Container (Lightweight DI)
  - [ ] Testing Infrastructure section added
  - [ ] Parameter Object Pattern documented
- [ ] constraints.md updated to v1.3
  - [ ] Testing conventions section added
  - [ ] Test coverage guidelines
  - [ ] Mock framework best practices
- [ ] public-api.md updated to v1.3
  - [ ] ServiceContainer class documented
  - [ ] PhysicsResponseData DTO documented
- [ ] file-tree.md updated to v1.3
  - [ ] tests/ directory structure added
  - [ ] ServiceContainer.php location added
  - [ ] PhysicsResponseData.php location added

**Code Documentation:**
- [ ] All new classes have comprehensive PHPDoc
- [ ] `@since 1.3.0` tags added to new code
- [ ] Inline comments explain DI patterns and testing approaches

---

## Testing Strategy

### How We Test the Tests

#### Unit Testing Strategy

**PhysicsCalculationHelper (Pure Functions):**
- **Approach:** Direct trait testing using anonymous class
- **Fixtures:** Hardcoded float values, Drag/Inertia objects
- **Assertions:** assertSame(), assertEqualsWithDelta() for float comparisons
- **Coverage:** All three trait methods, edge cases (zero, negative, large values)

**ServiceContainer (Stateful Object):**
- **Approach:** Test each method independently
- **Fixtures:** Mock service factories using closures
- **Assertions:** assertSame() for singleton, assertThrows() for missing services
- **Coverage:** register(), get(), has(), lazy instantiation, exceptions

**PhysicsResponseData (DTO):**
- **Approach:** Constructor testing, readonly enforcement
- **Fixtures:** Mock PhysicsCalculator, PhysicsData, etc.
- **Assertions:** assertInstanceOf(), property access tests
- **Coverage:** Construction, readonly properties

#### Integration Testing Strategy

**ClassRangeService (DI Mocking Demo):**
- **Approach:** Mock ShipDataService with PHPUnit mock builder
- **Fixtures:** Controlled ship data (3-5 ships, known values)
- **Assertions:** Assert calculated results match expected values
- **Coverage:** Full method execution with mocked dependencies

#### Manual Testing Strategy

**API Endpoints (Regression Testing):**
- **Approach:** Manual Postman/curl requests after changes
- **Test Cases:**
  1. Physics endpoint with shipId only
  2. Physics endpoint with shipId + engineId (tests cached ShipDef lookup)
  3. Class-range endpoint with ship class filter
  4. Config endpoint read/write
- **Assertions:** Response structure unchanged, response times <500ms
- **Frequency:** After each TD item completion

### Test Execution Workflow

```bash
# Step 1: Run unit tests (fast, <5 seconds)
composer test:unit

# Step 2: Run full test suite (includes integration, <10 seconds)
composer test

# Step 3: Generate coverage report (after major changes)
composer test:coverage
# Open coverage/index.html in browser

# Step 4: Manual API testing (after all changes)
cd gui
./start-dev.sh
# Test endpoints with Postman/curl
```

### Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Test Execution Time** | <10 seconds | `composer test` timing |
| **Unit Test Coverage** | >80% of new code | PHPUnit coverage report |
| **Integration Tests** | 1+ demonstrating DI mocking | Count of tests in Integration/ |
| **Zero Regressions** | All existing functionality works | Manual API testing checklist |
| **Container Overhead** | <1ms per request | Manual timing comparison before/after |

---

## Risks & Mitigations

### Risk 1: PHPUnit 11 Incompatibility with PHP 8.4
**Likelihood:** LOW  
**Impact:** HIGH (blocks all testing)  
**Symptoms:** Composer installation fails or PHPUnit crashes on execution

**Mitigation:**
1. **Research:** Check PHPUnit 11 documentation for PHP 8.4 compatibility before installation
2. **Fallback:** Use PHPUnit 10.x if 11.x incompatible
3. **Verification:** Run `vendor/bin/phpunit --version` after installation
4. **Alternative:** Use PHP 8.3 for testing if 8.4 blockers exist (unlikely)

---

### Risk 2: Service Container Introduces Stale Data Bugs
**Likelihood:** MEDIUM  
**Impact:** HIGH (incorrect calculation results)  
**Symptoms:** Tests pass but manual testing shows wrong values on subsequent requests

**Mitigation:**
1. **Design:** Container MUST NOT manage stateful services (e.g., caching across requests)
2. **Testing:** Add integration test that makes 2+ consecutive requests with different data
3. **Code Review:** Verify all services are stateless (no instance variables accumulating state)
4. **Documentation:** Document singleton behavior and request-scoped constraints in tech-stack.md

---

### Risk 3: Parameter Object Pattern Breaks Existing Code
**Likelihood:** LOW  
**Impact:** MEDIUM (physics endpoint returns errors)  
**Symptoms:** PHPStan errors or runtime exceptions when calling buildPhysicsResponse()

**Mitigation:**
1. **Single Refactoring:** Only refactor buildPhysicsResponse(), not other methods
2. **Testing:** Verify PhysicsService tests pass after refactoring
3. **Manual Testing:** Test physics endpoint with all parameter combinations
4. **Rollback:** Private method change easy to revert if issues found

---

### Risk 4: Strict Equality Breaks Backward Compatibility
**Likelihood:** LOW  
**Impact:** LOW (edge case bugs)  
**Symptoms:** calculatePercentChange() returns different values for edge cases

**Mitigation:**
1. **Research Phase:** Search all call sites, analyze parameter types before changing
2. **Testing:** Add edge case tests BEFORE refactoring (zero, null, false values)
3. **Verification:** Run full test suite after change
4. **Rollback:** Easy to revert if unexpected behavior found

---

### Risk 5: Test Infrastructure Too Complex for Benefit
**Likelihood:** MEDIUM  
**Impact:** LOW (time wasted, but no breakage)  
**Symptoms:** Tests take >30 seconds to run, require extensive fixtures, flaky results

**Mitigation:**
1. **Start Small:** Begin with pure function tests (PhysicsCalculationHelper) - fastest
2. **Minimal Fixtures:** Use hardcoded values instead of full game data extraction
3. **Time Boxing:** If test setup takes >1 hour, simplify approach
4. **Pragmatism:** Testing should increase velocity, not slow it down

---

### Risk 6: Service Container Overhead Impacts Performance
**Likelihood:** VERY LOW  
**Impact:** LOW (slightly slower API responses)  
**Symptoms:** Response times increase from 300ms → 305ms+ after container introduction

**Mitigation:**
1. **Measurement:** Time Router::register() execution before/after container
2. **Benchmark:** Compare average response times over 100 requests
3. **Threshold:** If overhead >5ms, investigate factory function complexity
4. **Optimization:** Cache container instance if overhead measurable (unlikely)

---

### Risk 7: Scope Creep into Frontend Testing
**Likelihood:** MEDIUM  
**Impact:** MEDIUM (delays completion, dilutes focus)  
**Symptoms:** Plan expands to include Vitest setup, React component tests, etc.

**Mitigation:**
1. **Clear Scope:** This plan is BACKEND ONLY (PHP testing)
2. **Defer Frontend:** Frontend already has testing strategy (Vitest in package.json)
3. **Separate Plan:** If frontend testing needed, create separate plan
4. **Focus:** Complete backend testing infrastructure before considering frontend

---

## Summary of Risk Mitigation Strategy

**Overall Approach:**
1. **Start with lowest-risk items** (PhysicsCalculationHelper unit tests - pure functions)
2. **Measure performance** before/after each change (container overhead, test execution time)
3. **Test incrementally** (verify after each TD item, not all at once)
4. **Maintain rollback capability** (private method changes easy to revert)
5. **Time-box research phases** (TD-1 strict equality research, TD-5 product requirements)

**Abort Conditions:**
- PHPUnit 11 incompatible with PHP 8.4 → Downgrade to PHPUnit 10
- Test setup takes >2 hours → Simplify or defer
- Service container overhead >5ms → Remove container, keep manual instantiation
- Strict equality breaks tests → Revert to loose equality
- Scope creeps into frontend → Explicitly defer out-of-scope items

---

## Estimated Effort

| Phase | Work Packages | Estimated Time | Priority |
|-------|---------------|----------------|----------|
| **Phase 1** | TD-3: Test Infrastructure | 3-4 hours | HIGH |
| **Phase 1** | TD-4: Service Container | 2-3 hours | HIGH |
| **Phase 2** | TD-2: Parameter Object | 1-2 hours | MEDIUM |
| **Phase 2** | TD-1: Strict Equality | 1 hour | MEDIUM |
| **Phase 3** | TD-5: Context Thresholds | 2-3 hours | LOW (conditional) |
| **Documentation** | Manifest updates | 1 hour | REQUIRED |
| **Testing & Verification** | Manual testing, QA | 1-2 hours | REQUIRED |

**Total Effort (excluding TD-5):** 9-13 hours  
**Total Effort (including TD-5):** 11-16 hours

**Recommended Approach:**
- **Sprint 1:** Phase 1 only (5-7 hours) - Establish foundation
- **Sprint 2:** Phase 2 (2-3 hours) - Code quality improvements
- **Sprint 3:** Phase 3 if triggered by product requirement

---

## Success Criteria Summary

**Project is complete when:**

1. ✅ **Test infrastructure exists** and demonstrates value
   - PHPUnit installed, configured, runnable via Composer
   - Unit tests cover PhysicsCalculationHelper (pure function testing)
   - Integration test demonstrates DI mocking (ClassRangeService)
   - Test execution time <10 seconds

2. ✅ **Service container exists** and improves architecture
   - ServiceContainer class implemented with lazy singletons
   - Router uses container for all service instantiation
   - No behavior changes or performance degradation
   - Container benefits documented in manifest

3. ✅ **Parameter object pattern** applied to buildPhysicsResponse()
   - PhysicsResponseData DTO created
   - Method signature simplified (11 params → 1 param)
   - No behavior changes

4. ✅ **Strict equality** used in PhysicsCalculationHelper
   - Backward compatibility verified
   - Edge case tests added
   - No behavior changes

5. ⏸️ **Context thresholds** configurable (IF triggered by product)
   - Schema added to build-config.json
   - Frontend loads and uses thresholds
   - Documentation updated

6. ✅ **Manifest updated** to v1.3
   - All patterns documented
   - Testing infrastructure documented
   - Version bumped, dates updated

7. ✅ **Zero regressions** in API functionality
   - All endpoints tested manually
   - Response times maintained (<500ms)
   - Response structures unchanged

---

**AGENT:** Planning  
**STATUS:** READY_FOR_PM
