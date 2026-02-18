# Plan

## Summary

Extend the GUI backend's test coverage to the remaining untested services (`ShipDataService`, `ConfigService`, `PhysicsService`), integrate PHPStan static analysis into the GUI backend build pipeline, and update the React frontend to align with the refined backend API contracts. This plan addresses three of the four "Next Steps" from the `2026-02-16-technical-debt-cleanup` synthesis report (the MCP Tool issue has been resolved separately).

## Approach / Architecture

The work decomposes into three parallel tracks that share a common prerequisite (DI refactoring):

```
Track 1: Expand Test Coverage
├── Prerequisite: Fix DI inconsistencies to make services testable
├── ShipDataService tests
├── ConfigService tests
├── PhysicsService tests
└── Endpoint integration tests (optional stretch)

Track 2: PHPStan Integration
├── Create gui/backend/phpstan.neon
├── Fix any level-5 violations
└── Add Composer script for GUI analysis

Track 3: Frontend Integration
├── Audit frontend TypeScript types against backend DTOs
├── Fix type mismatches
├── Update manifest documentation
└── Verify end-to-end data flow
```

### Prerequisite: DI Refactoring

Before services can be properly tested, the following architectural issues identified in the synthesis report must be addressed:

1. **`ShipsEndpoint` and `ConfigEndpoint` bypass the `ServiceContainer`** — they instantiate their services directly in constructors, making them impossible to test with mocks.
2. **`ShipDataService` uses static caches** (`$shipCache`, `$engineCache`) that persist across test runs.
3. **`ConfigService` uses `__DIR__`-relative path** for the config file, making it untestable without the real file.
4. **Duplicated `errorResponse()` method** exists in all 4 endpoints (code smell, not blocking tests but should be cleaned up).
5. **Duplicated `findTierForMultiplier()`** in both `PhysicsService` and `ClassRangeService` (should be moved to the shared `PhysicsCalculationHelper` trait).

## Rationale

- **Test coverage first:** The previous session established a PHPUnit 12 test infrastructure with 25 passing tests covering `ServiceContainer`, `ClassRangeService`, `PhysicsResponseData` DTO, and `PhysicsCalculationHelper` trait. The three remaining services (`ShipDataService`, `ConfigService`, `PhysicsService`) represent the untested core business logic.
- **DI fix as prerequisite:** Without DI refactoring, the X4 Core static singletons (`ShipDefs::getInstance()`, `EngineDefs::getInstance()`) and hardcoded file paths make true unit testing impossible. The `ServiceContainer` pattern established in the previous session should be extended to all endpoints.
- **PHPStan separately:** Static analysis is an independent track that can proceed in parallel with test writing.
- **Frontend last:** Frontend changes depend on stable, tested, and verified backend contracts.

## Detailed Steps

### Step 1: Refactor DI for Testability

1. **Make `ConfigService` path injectable:** Add a constructor parameter `string $configPath` with a default that resolves to the production path. This allows tests to inject a temp file path.
2. **Make `ShipDataService` mockable:** Replace static caches (`private static ?array $shipCache`) with instance-level caches (`private ?array $shipCache`). This ensures test isolation. Add constructor parameters to optionally inject `ShipDefs` and `EngineDefs` instances (or data arrays) for testing without X4 Core.
3. **Make `PhysicsService` injectable:** Accept `ShipDataService` (or equivalent data source) via constructor DI instead of calling `ShipDefs::getInstance()` directly.
4. **Update `ShipsEndpoint`:** Accept `ShipDataService` via constructor injection (as `PhysicsEndpoint` already does for `PhysicsService`).
5. **Update `ConfigEndpoint`:** Accept `ConfigService` via constructor injection.
6. **Update `Router.php`:** Register `ConfigService` and resolve all four endpoints through the `ServiceContainer`.
7. **Extract shared `errorResponse()` method:** Move the duplicated private `errorResponse()` from all 4 endpoints into a shared trait (e.g., `ErrorResponseTrait`) or a base endpoint class.
8. **Move `findTierForMultiplier()` to `PhysicsCalculationHelper` trait:** Remove the duplicate from both `PhysicsService` and `ClassRangeService`.

### Step 2: Write `ShipDataService` Tests

1. Create `tests/Unit/Services/ShipDataServiceTest.php`.
2. Test `getShipTypes()` — returns exactly 4 types (transport, mining, auxiliary, carrier).
3. Test `getShipsByType()` — with valid type, with invalid type.
4. Test `getShipDetails()` — returns `ShipDetails` DTO with expected properties.
5. Test `getEnginesForShip()` — returns engines matching ship size.
6. Test `getAllEngines()` — returns non-empty engine list.
7. Test instance-level cache isolation (after static cache removal).
8. Strategy: If mocking X4 Core singletons is not feasible, write integration-style tests that use real game data (available via `dev-config.php` bootstrap). Document this decision.

### Step 3: Write `ConfigService` Tests

1. Create `tests/Unit/Services/ConfigServiceTest.php`.
2. Test `getConfig()` — reads and parses JSON correctly from injected path.
3. Test `getConfig()` — handles missing file gracefully (throws `GUIException`).
4. Test `updateConfig()` — writes valid config, file contents match.
5. Test `updateConfig()` — rejects invalid config (validation failure).
6. Test `validateConfig()` — valid config returns success `ValidationResult`.
7. Test `validateConfig()` — missing `cargo-multipliers` key.
8. Test `validateConfig()` — empty `cargo-multipliers` array.
9. Test `validateConfig()` — negative multiplier values.
10. Test `validateConfig()` — invalid `flight-mechanics` values (out of range).
11. Test `validateConfig()` — tier validation: non-ascending tiers, out-of-range values.
12. Strategy: Use PHP's `tempnam()` / `sys_get_temp_dir()` to create temporary config files for isolated testing.

### Step 4: Write `PhysicsService` Tests

1. Create `tests/Unit/Services/PhysicsServiceTest.php`.
2. Test `calculatePhysics()` with a well-formed `PhysicsRequest` — returns valid `PhysicsResponse`.
3. Test `calculatePhysics()` with no `shipId` — uses default physics values.
4. Test `calculatePhysics()` with specific `shipId` — loads real ship data.
5. Test `calculatePhysics()` with engine ID — includes engine performance metrics.
6. Test `calculatePhysics()` without engine ID — omits engine performance.
7. Test `findTierForMultiplier()` (now in trait) — tier boundary selection.
8. Test physics calculation determinism — same input always produces same output.
9. Strategy: Depends on DI refactoring (Step 1). May need integration-style tests for X4 Core dependencies.

### Step 5: Create PHPStan Configuration for GUI Backend

1. Create `gui/backend/phpstan.neon` with:
   - Level 5 (matches main project).
   - Paths: `src/`, `tests/`.
   - Bootstrap: `tests/bootstrap.php` (ensures autoloading).
   - Any necessary `ignoreErrors` for X4 Core static singleton patterns.
2. Add Composer script `analyze` to `gui/backend/composer.json`:
   ```json
   "analyze": "phpstan analyse --configuration=phpstan.neon"
   ```
3. Run PHPStan and fix all level-5 violations found in `gui/backend/src/`.
4. Common expected issues:
   - Missing return types.
   - Possible `null` access on array results.
   - Mixed type usage in JSON parsing (`json_decode` returns `mixed`).
   - Unused parameters or variables.

### Step 6: Audit Frontend Types Against Backend DTOs

1. Compare each TypeScript type definition with its PHP DTO counterpart:
   - `frontend/src/types/physics.d.ts` ↔ `backend/src/DTOs/Physics*.php`
   - `frontend/src/types/ships.d.ts` ↔ `backend/src/DTOs/ShipDetails.php`
   - `frontend/src/types/config.d.ts` ↔ `ConfigService` response shape
2. Document all mismatches. Known issues from research:
   - `EnginePerformance` DTO: PHP has `engineId` and `thrustForward` fields that may not appear in TS type.
   - `ShipInfo` type: TS has `{id, name, size}` but manifest describes `{id, name, size, mass, cargo}`.
   - `PhysicsResponse` shape: possible flat vs. nested object serialization difference.
3. For each mismatch, determine the source of truth (backend DTO `toArray()` output is authoritative) and update the frontend TypeScript type to match.

### Step 7: Fix Frontend Type Mismatches

1. Update TypeScript type definitions in `frontend/src/types/` to match actual backend API responses.
2. Verify no React components break after type changes (TypeScript compiler will catch errors).
3. Run `npm run build` (or `tsc -b`) to validate TypeScript compilation.
4. Run `npm run lint` to verify ESLint passes.

### Step 8: Update Manifest Documentation

1. Update `gui/docs/project-manifest/public-api.md` — correct any DTO signatures that changed.
2. Update `gui/docs/project-manifest/tech-stack.md` — correct React/TypeScript/Vite version references (React 19, TS 5.9, Vite 7.3), document PHPStan integration.
3. Update `gui/docs/project-manifest/constraints.md` — add PHPStan level-5 as a constraint for GUI backend.
4. Update `gui/docs/project-manifest/file-tree.md` — add new test files, `phpstan.neon`.
5. Update `gui/docs/project-manifest/README.md` — update version to 1.4, correct statistics.
6. Update parent `docs/agents/project-manifest/` if any shared references need correction.

## Dependencies

- **X4 Core library** (`mistralys/x4-core`) must be available via Composer autoload for ship/engine data access in tests.
- **`dev-config.php`** must exist and point to valid extracted game data (required by bootstrap for integration tests).
- **PHPUnit 12.5+** (already installed in `gui/backend/vendor/`).
- **PHPStan >=1.6.1** must be added as a dev dependency to `gui/backend/composer.json` (currently only in main project).
- **Node.js / npm** required for frontend TypeScript compilation and linting.
- Steps 2-4 depend on Step 1 (DI refactoring).
- Steps 6-7 can proceed in parallel with Steps 2-5.
- Step 8 depends on Steps 1-7 being complete.

## Required Components

### Files to Create
- `gui/backend/phpstan.neon` — New PHPStan configuration
- `gui/backend/tests/Unit/Services/ShipDataServiceTest.php` — New test file
- `gui/backend/tests/Unit/Services/ConfigServiceTest.php` — New test file
- `gui/backend/tests/Unit/Services/PhysicsServiceTest.php` — New test file

### Files to Modify
- `gui/backend/src/Services/ConfigService.php` — Add config path DI
- `gui/backend/src/Services/ShipDataService.php` — Replace static caches, add DI
- `gui/backend/src/Services/PhysicsService.php` — Add DI, remove duplicated method
- `gui/backend/src/Services/ClassRangeService.php` — Remove duplicated `findTierForMultiplier()`
- `gui/backend/src/Utils/PhysicsCalculationHelper.php` — Add `findTierForMultiplier()`
- `gui/backend/src/API/Endpoints/ShipsEndpoint.php` — Constructor DI
- `gui/backend/src/API/Endpoints/ConfigEndpoint.php` — Constructor DI
- `gui/backend/src/API/Endpoints/PhysicsEndpoint.php` — Extract error response
- `gui/backend/src/API/Endpoints/ClassRangeEndpoint.php` — Extract error response
- `gui/backend/src/API/Router.php` — Register all services, resolve all endpoints via container
- `gui/backend/composer.json` — Add PHPStan dev dependency and `analyze` script
- `gui/frontend/src/types/physics.d.ts` — Fix type mismatches
- `gui/frontend/src/types/ships.d.ts` — Fix type mismatches (if needed)

### Documentation to Update
- `gui/docs/project-manifest/public-api.md`
- `gui/docs/project-manifest/tech-stack.md`
- `gui/docs/project-manifest/constraints.md`
- `gui/docs/project-manifest/file-tree.md`
- `gui/docs/project-manifest/README.md`

## Assumptions

- The X4 Core library's static singletons (`ShipDefs::getInstance()`, `EngineDefs::getInstance()`) can be wrapped or proxied for testing. If not, integration-style tests using real game data are acceptable.
- The `dev-config.php` bootstrap file provides valid paths to extracted game data on the development machine.
- The current 25 tests continue to pass after DI refactoring (regression protection).
- PHPStan level 5 is the target for the GUI backend (matching the main project).
- Frontend type mismatches are limited to the ones identified during research; additional mismatches may be discovered during implementation.
- The `findTierForMultiplier()` implementation is identical in both services and can be moved to the trait without behavioral changes.

## Constraints

- **Synchronous file I/O only** — per project constraints.
- **No database connections** — all data from XML/JSON files.
- **`declare(strict_types=1)`** in every new PHP file.
- **TypeScript strict mode** for all frontend files.
- **Readonly DTO properties** — DTOs must not be mutated after construction.
- **PHPUnit 12.5+ API** — use attribute-based test configuration (not annotations).
- **Backward compatibility** — DI refactoring must not change the public API contracts or behavior, only the internal wiring.
- **No Git write operations** — the user handles version control.

## Out of Scope

- **Endpoint integration tests** — The `tests/Integration/Endpoints/` directory exists with a `.gitkeep` but writing full HTTP-level endpoint tests (using Slim's test helpers) is a stretch goal, not a requirement.
- **Frontend unit tests** — React component testing (Jest/Vitest) is not in scope.
- **CorsMiddleware tests** — Low value for a local development tool.
- **Router tests** — Route registration is verified implicitly by endpoint integration tests.
- **PHPStan level 6+** — Level 5 is sufficient for this phase.
- **Performance optimization** — No performance tuning of tests or services.
- **MCP Tool issue** — Already resolved separately.

## Acceptance Criteria

1. **All existing 25 tests continue to pass** (no regressions from DI refactoring).
2. **`ShipDataServiceTest`** has at least 5 passing tests covering all public methods.
3. **`ConfigServiceTest`** has at least 8 passing tests covering CRUD operations and validation edge cases.
4. **`PhysicsServiceTest`** has at least 5 passing tests covering calculation with/without ship ID and engine.
5. **Total test count ≥ 43** (25 existing + 18 new minimum).
6. **All tests execute in < 5 seconds** total.
7. **`gui/backend/phpstan.neon`** exists at level 5 with the `analyze` script in `composer.json`.
8. **PHPStan reports 0 errors** at level 5 for `gui/backend/src/`.
9. **All 4 endpoints use constructor DI** via the `ServiceContainer`.
10. **`findTierForMultiplier()` exists only in `PhysicsCalculationHelper` trait** (removed from both services).
11. **`errorResponse()` is not duplicated** — extracted to a shared trait or base class.
12. **Frontend TypeScript compiles without errors** (`npm run build` succeeds).
13. **Frontend type definitions match backend DTO `toArray()` output** for all endpoints.
14. **All 5 GUI manifest documents updated** with correct versions, file trees, and API signatures.
15. **Manifest version bumped to 1.4.**

## Testing Strategy

### Unit Tests (Primary)
- Each service gets its own test class in `tests/Unit/Services/`.
- Tests use dependency injection to provide mock/stub dependencies.
- `ConfigService` tests use temporary files (`sys_get_temp_dir()`) for isolation.
- `ShipDataService` tests either mock X4 Core data or use real data via bootstrap (document choice).
- `PhysicsService` tests verify deterministic output for known inputs.

### Integration Tests (Stretch)
- If time permits, add basic integration tests for endpoints in `tests/Integration/Endpoints/`.
- These would test the full request→service→response cycle using Slim's PSR-7 test helpers.

### Static Analysis
- PHPStan level 5 run as `composer analyze` in `gui/backend/`.
- All errors must be resolved before marking complete.

### Frontend Validation
- TypeScript compilation (`tsc -b`) serves as the type-safety check.
- ESLint (`npm run lint`) validates code style.
- Manual verification: Start both dev servers and confirm the GUI loads and performs calculations correctly.

### Regression Protection
- Run the full existing test suite after every DI refactoring step.
- Use `composer test` in `gui/backend/` to execute all tests.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **X4 Core static singletons prevent true unit testing** | Accept integration-style tests that use real game data via bootstrap. Document this as a known limitation. Future work could introduce a data abstraction layer. |
| **DI refactoring breaks existing behavior** | Run all 25 existing tests after each refactoring step. DI changes are internal wiring only — no public API changes. |
| **PHPStan finds many violations at level 5** | Start at level 3, fix those issues, then incrementally raise to level 5. This prevents being overwhelmed. |
| **Frontend type changes cascade into component errors** | TypeScript compiler catches all type errors at build time. Fix component props/usage as part of Step 7. |
| **`findTierForMultiplier()` implementations differ subtly between services** | Verify both implementations are identical before extracting to trait. Write a test for the trait method first. |
| **Static caches in `ShipDataService` used intentionally for performance** | Replace with instance caches. Performance impact is negligible for a local dev tool. The `ServiceContainer` singleton pattern ensures only one instance exists in production. |
| **`ConfigService` path refactoring breaks production path resolution** | Use default parameter value that resolves to the current production path. Tests override with temp path. Existing behavior unchanged when no argument is passed. |
