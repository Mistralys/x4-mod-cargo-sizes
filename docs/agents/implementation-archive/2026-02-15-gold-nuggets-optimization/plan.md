# Plan: Technical Debt Cleanup & Performance Optimization
## Gold Nuggets Implementation from Absolute Metrics Project

**Plan ID:** 2026-02-15-gold-nuggets-optimization  
**Created:** February 15, 2026  
**Source:** Synthesis Report from 2026-02-15-absolute-metrics-class-range  
**Total Gold Nuggets:** 7 (High Priority: 2, Medium Priority: 3, Low Priority: 2)

---

## Summary

Implement all 7 "Gold Nugget" recommendations identified during the Absolute Metrics & Class-Wide Range Calculations project. These optimizations will eliminate code duplication, improve performance (~10-15% backend improvement), enhance testability, and reduce technical debt to near-zero in the Physics Tuning GUI. The work is categorized into three priority tiers with a total estimated effort of 12-15 hours.

**Key Goals:**
1. Extract duplicate physics calculation methods to shared utility
2. Cache redundant ShipDef lookups for 2x performance improvement
3. Refactor large PhysicsService method for better maintainability
4. Make API timeouts configurable per endpoint
5. Implement dependency injection for better testability
6. Prepare infrastructure for ship-size-specific context phrases
7. Document median calculation optimization strategy

---

## Approach / Architecture

### Strategy: Bottom-Up Refactoring with Zero-Downtime

The plan follows a **bottom-up dependency order** to ensure each refactoring is complete and tested before higher-level code depends on it. This minimizes risk and allows incremental testing.

**Phase-Based Execution:**

1. **Phase 1: Foundation (High Priority)** — Extract shared utilities and eliminate performance bottlenecks
2. **Phase 2: Structure (Medium Priority)** — Refactor large methods and improve dependency management
3. **Phase 3: Polish (Low Priority)** — Prepare for future enhancements and document optimization strategies

**Architecture Principles:**
- **DRY (Don't Repeat Yourself):** Eliminate duplicate calculation methods
- **Single Responsibility:** Break large methods into focused units
- **Dependency Inversion:** Use constructor injection for testability
- **Performance:** Cache redundant lookups, optimize API timeouts
- **Backward Compatibility:** All changes maintain existing API contracts

### Component Impact Map

```
┌─────────────────────────────────────────────────────────────┐
│ Backend (PHP 8.4)                                           │
├─────────────────────────────────────────────────────────────┤
│ NEW:  PhysicsCalculationHelper (trait or utility class)    │
│ MOD:  PhysicsService (cache, refactor, use helper)         │
│ MOD:  ClassRangeService (use helper)                        │
│ MOD:  ClassRangeEndpoint (dependency injection)             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Frontend (React + TypeScript)                               │
├─────────────────────────────────────────────────────────────┤
│ MOD:  api.ts (configurable timeouts)                        │
│ MOD:  metricContext.ts (remove underscore, add TODO)        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Documentation                                                │
├─────────────────────────────────────────────────────────────┤
│ MOD:  tech-stack.md (new pattern: PhysicsCalculationHelper) │
│ MOD:  constraints.md (DI best practices)                     │
│ MOD:  public-api.md (new helper methods)                     │
│ MOD:  file-tree.md (new helper file location)               │
└─────────────────────────────────────────────────────────────┘
```

---

## Rationale

### Why This Plan?

1. **High ROI:** Total effort 12-15 hours for ~10-15% performance gain, near-zero technical debt, and significantly improved testability
2. **Low Risk:** Bottom-up approach ensures each change is isolated and tested before dependent code is modified
3. **Compound Benefits:** Extracted utilities benefit multiple services (PhysicsService + ClassRangeService)
4. **Future-Proofing:** Infrastructure prepared for ship-size-specific phrases and larger ship collections

### Why Bottom-Up Order?

Implementing utilities first (PhysicsCalculationHelper) allows PhysicsService and ClassRangeService to immediately benefit. If we refactored PhysicsService first, we'd create temporary coupling that would need to be refactored again when extracting the helper.

### Why Not Defer Low-Priority Items?

The low-priority items (ship-size context phrases, median optimization) require minimal effort (1-3 hours total) and provide clear user/performance value. Including them in this plan achieves true technical debt closure.

---

## Detailed Steps

### Phase 1: Foundation (High Priority) — Estimated 3.5 hours

#### Step 1.1: Extract PhysicsCalculationHelper (2.5 hours)
**Gold Nugget:** #1 (High Priority)  
**Affected:** PhysicsService, ClassRangeService

1. Create `gui/backend/src/Utils/PhysicsCalculationHelper.php` (trait or static class)
2. Extract duplicate methods from both services:
   - `calculatePercentChange(float $original, float $modified): float`
   - `calculateAverageDragChange(array $dragArray, float $multiplier): float`
   - `calculateAverageInertiaChange(array $inertiaArray, float $multiplier): float`
3. Add strict return types and parameter types
4. Add PHPDoc blocks with @since tags
5. **Decision:** Use **trait** approach (allows composition, better for shared utilities)
6. Update PhysicsService to `use PhysicsCalculationHelper;`
7. Update ClassRangeService to `use PhysicsCalculationHelper;`
8. Remove duplicate method implementations from both services
9. Run `composer dump-autoload`

**Validation:**
- Existing functionality unchanged (run manual tests)
- Both services compile without errors
- No duplicate code remains

#### Step 1.2: Cache ShipDef Lookups (1 hour)
**Gold Nugget:** #2 (High Priority)  
**Affected:** PhysicsService.calculatePhysics()

1. Locate both `ShipDefs::getInstance()->getByID($shipId)` calls in `calculatePhysics()`
2. Store result in `$shipDef` variable after first lookup (line ~59-63)
3. Reuse `$shipDef` variable for second lookup (line ~134-139)
4. Add null check after cached lookup
5. Measure performance improvement with X-Debug profiler or manual timing

**Before:**
```php
$shipDef = ShipDefs::getInstance()->getByID($shipId);
// ... 70 lines later ...
$shipDef = ShipDefs::getInstance()->getByID($shipId); // Duplicate!
```

**After:**
```php
$shipDef = ShipDefs::getInstance()->getByID($shipId);
// ... 70 lines later ...
// Reuse $shipDef (already loaded)
```

**Expected Impact:** ~2x faster for dual-lookup scenarios (shipId + engineId both provided)

---

### Phase 2: Structure (Medium Priority) — Estimated 6-7 hours

#### Step 2.1: Extract Ship Data Loading Method (3-4 hours)
**Gold Nugget:** #3 (Medium Priority)  
**Affected:** PhysicsService.calculatePhysics()

1. Analyze method structure (currently ~388 lines)
2. Identify data loading section (lines 51-149 estimated)
3. Extract to private method:
   ```php
   private function loadShipPhysicsData(?string $shipId): array
   ```
4. Return associative array with keys:
   - `shipDef`, `shipSize`, `cargoType`, `cargoCapacity`, `physics`, `thrustValues`, `shipName`, `className`
5. Update `calculatePhysics()` to call `loadShipPhysicsData()`
6. Extract DTO construction section (lines 300-385 estimated)
7. Extract to private method:
   ```php
   private function buildPhysicsResponse(array $data, /* other params */): PhysicsResponse
   ```
8. Update main method to orchestrate: load → calculate → build

**Before Structure:**
```php
public function calculatePhysics(PhysicsRequest $req): PhysicsResponse {
    // 388 lines of mixed concerns
}
```

**After Structure:**
```php
public function calculatePhysics(PhysicsRequest $req): PhysicsResponse {
    $data = $this->loadShipPhysicsData($req->shipId);
    $metrics = $this->calculateMetrics($data, $req);
    return $this->buildPhysicsResponse($data, $metrics, $req);
}

private function loadShipPhysicsData(?string $shipId): array { /* ... */ }
private function buildPhysicsResponse(/* ... */): PhysicsResponse { /* ... */ }
```

**Validation:**
- Method length reduced from 388 to ~50 lines
- Each extracted method has clear single responsibility
- All tests pass (manual verification)

#### Step 2.2: Configurable API Timeouts (1 hour)
**Gold Nugget:** #4 (Medium Priority)  
**Affected:** `gui/frontend/src/services/api.ts`

1. Create timeout configuration object at top of file:
   ```typescript
   const API_TIMEOUTS = {
     physics: 10000,      // Single-ship (fast)
     classRange: 15000,   // Class-wide aggregation (slower)
     shipData: 10000,     // Ship data fetch
     default: 10000       // Fallback
   } as const;
   ```
2. Update `physicsApi.calculate()` to use `API_TIMEOUTS.physics`
3. Update `classRangeApi.calculate()` to use `API_TIMEOUTS.classRange`
4. Update `shipDataApi.getShips()` to use `API_TIMEOUTS.shipData`
5. Update all other endpoints to use `API_TIMEOUTS.default`
6. Add JSDoc comment explaining timeout strategy

**Rationale:** Class-range iterates 80+ ships (~80ms backend) with 10s timeout leaves only ~120ms network buffer. 15s provides better headroom.

#### Step 2.3: Dependency Injection for ClassRangeEndpoint (2 hours)
**Gold Nugget:** #5 (Medium Priority)  
**Affected:** ClassRangeEndpoint, Router.php

1. Modify `ClassRangeEndpoint` constructor to accept dependencies:
   ```php
   public function __construct(
       private readonly ShipDataService $shipDataService,
       private readonly ClassRangeService $classRangeService
   ) {}
   ```
2. Remove `new ShipDataService()` instantiation from `handle()` method
3. Update `Router.php` to create dependencies and inject:
   ```php
   $shipDataService = new ShipDataService();
   $classRangeService = new ClassRangeService();
   $classRangeEndpoint = new ClassRangeEndpoint($shipDataService, $classRangeService);
   ```
4. Add docblock explaining DI rationale
5. Create mock test (optional but recommended):
   ```php
   // tests/Unit/ClassRangeEndpointTest.php
   public function testHandleWithMockedDependencies() { /* ... */ }
   ```

**Benefit:** Easier unit testing, better separation of concerns, follows SOLID principles

---

### Phase 3: Polish (Low Priority) — Estimated 2-3 hours

#### Step 3.1: Ship-Size-Specific Context Phrases (1-2 hours)
**Gold Nugget:** #6 (Low Priority)  
**Affected:** `gui/frontend/src/utils/metricContext.ts`

1. Remove underscore prefix from `_shipSize` parameter in:
   - `getSpeedContext(value: number, shipSize: string): string`
   - `getAccelerationContext(value: number, shipSize: string): string`
2. Add TODO comments at function top:
   ```typescript
   // TODO: Implement ship-size-specific thresholds when product requirements clarified
   // Example: "fast for L-class" vs "fast for S-class"
   ```
3. Add switch statement skeleton (commented out):
   ```typescript
   /*
   switch (shipSize) {
     case 'XS':
     case 'S':
       // return getSizeSpecificContext(value, 'small');
     case 'M':
       // return getSizeSpecificContext(value, 'medium');
     // ...
   }
   */
   ```
4. Update TypeScript compilation to ensure no errors
5. Document in tech-stack.md that infrastructure is ready for future enhancement

**Why Now?** Removes technical debt marker (unused parameter warning suppression) and documents architectural intent.

#### Step 3.2: Document Median Optimization Strategy (1 hour)
**Gold Nugget:** #7 (Low Priority)  
**Affected:** ClassRangeService, tech-stack.md, constraints.md

1. Analyze current median calculation implementation (uses `sort()` — O(n log n))
2. Add inline comment above median calculation:
   ```php
   // Current: sort() with O(n log n) complexity
   // Acceptable for ~80 ships per type (~0.5ms overhead)
   // If ship counts exceed 1000, consider quickselect (O(n) average)
   // See: https://en.wikipedia.org/wiki/Quickselect
   ```
3. Add section to `tech-stack.md` → **Performance Considerations**:
   ```markdown
   ### Median Calculation Strategy
   
   **Current:** Array sort() with O(n log n) complexity
   **Performance:** ~0.5ms for 80 ships, acceptable for current use case
   **Threshold:** If ship counts exceed 1000, implement quickselect algorithm
   **Complexity:** Quickselect provides O(n) average case performance
   ```
4. Add constraint to `constraints.md` → **Performance Rules**:
   ```markdown
   - Median calculations use sort() for datasets <1000 items
   - Datasets >1000 items should use optimized selection algorithms
   ```

**Why Document Instead of Implement?** Current dataset (80 ships) has negligible performance impact. Premature optimization violates constraints.md policy. Documentation ensures future developers know when to act.

---

## Dependencies

### External Dependencies (Already Satisfied)
- ✅ PHP 8.4+ (strict types, readonly properties)
- ✅ Composer autoloader
- ✅ x4-core library v1.3.0+ (ShipDef, EngineDef)
- ✅ TypeScript 5+ (strict mode)
- ✅ React 18 (hooks, functional components)

### Internal Dependencies (Execution Order)
```
Phase 1 (Independent)
  ├─ Step 1.1 (Extract Helper) → Blocks Nothing
  └─ Step 1.2 (Cache Lookups) → Blocks Nothing

Phase 2 (Depends on Phase 1 Completion)
  ├─ Step 2.1 (Refactor PhysicsService) → Requires Step 1.1 (uses helper)
  ├─ Step 2.2 (API Timeouts) → Independent
  └─ Step 2.3 (Dependency Injection) → Independent

Phase 3 (Independent)
  ├─ Step 3.1 (Context Phrases) → Independent
  └─ Step 3.2 (Document Median) → Independent
```

**Critical Path:** Step 1.1 → Step 2.1 (refactoring requires helper to exist)

---

## Required Components

### New Files (1)
```
gui/backend/src/Utils/PhysicsCalculationHelper.php  (trait or class)
```

### Modified Files (8)

#### Backend (5)
```
gui/backend/src/Services/PhysicsService.php
  - Use PhysicsCalculationHelper trait
  - Cache ShipDef lookups
  - Extract private methods (loadShipPhysicsData, buildPhysicsResponse)

gui/backend/src/Services/ClassRangeService.php
  - Use PhysicsCalculationHelper trait
  - Add median optimization comment

gui/backend/src/API/Endpoints/ClassRangeEndpoint.php
  - Constructor dependency injection
  - Remove direct service instantiation

gui/backend/src/API/Router.php
  - Inject dependencies into ClassRangeEndpoint

gui/backend/composer.json (autoload refresh)
  - Run composer dump-autoload after trait creation
```

#### Frontend (2)
```
gui/frontend/src/services/api.ts
  - Add API_TIMEOUTS configuration object
  - Apply configurable timeouts to all endpoints

gui/frontend/src/utils/metricContext.ts
  - Remove underscore from shipSize parameter
  - Add TODO comments and skeleton
```

#### Documentation (5)
```
gui/docs/project-manifest/tech-stack.md (v1.1 → v1.2)
  - Add PhysicsCalculationHelper pattern
  - Add Performance Considerations section
  - Document median calculation strategy

gui/docs/project-manifest/public-api.md (v1.1 → v1.2)
  - Add PhysicsCalculationHelper methods
  - Document refactored PhysicsService private methods

gui/docs/project-manifest/constraints.md (v1.1 → v1.2)
  - Add DI best practices
  - Add performance thresholds for median calculation

gui/docs/project-manifest/file-tree.md (v1.1 → v1.2)
  - Add gui/backend/src/Utils/PhysicsCalculationHelper.php

gui/docs/project-manifest/API.md (version increment only)
  - No API contract changes (backward compatible)
```

---

## Assumptions

1. **No Breaking Changes Required:** All optimizations maintain existing API contracts
2. **Manual Testing Sufficient:** Project does not currently have automated test suite for physics calculations
3. **Performance Gains Measurable:** ShipDef lookup caching provides ~2x improvement for dual-lookup scenarios
4. **Trait Approach Preferred:** PhysicsCalculationHelper uses trait for flexibility (can be switched to static class if needed)
5. **Router Instantiation Access:** We can modify Router.php to inject dependencies (not using DI container framework)
6. **No Composer Lock Conflicts:** Running composer dump-autoload won't introduce dependency version issues
7. **TypeScript Strict Mode Enabled:** Frontend already uses strict mode with noUnusedParameters rule
8. **Ship Counts Stable:** Median optimization not urgent since X4 ship counts unlikely to exceed 1000 per type

---

## Constraints

### Technical Constraints
- **PHP 8.4 Strict Types:** All new/modified PHP code must use `declare(strict_types=1)`
- **Readonly Enforcement:** DTO properties remain readonly (no changes to DTOs in this plan)
- **TypeScript Strict Mode:** All frontend code must compile with `strict: true` and `noUnusedParameters: true`
- **Backward Compatibility:** Existing API endpoints must maintain identical request/response structures
- **Zero Downtime:** Changes must be deployable without service interruption

### Process Constraints
- **Manifest Updates Mandatory:** All code changes must update corresponding manifest documents
- **Composer Autoload Refresh:** Required after creating PhysicsCalculationHelper.php
- **No Database Changes:** All optimizations use in-memory data only
- **No External API Changes:** Changes are internal refactoring only

### Performance Constraints
- **Class-Range Response Time:** Must remain <600ms (currently ~580ms avg)
- **Single-Ship Response Time:** Must remain <350ms (currently ~300ms avg)
- **Memory Usage:** Trait approach must not increase memory footprint (vs duplicate methods)

### Architecture Constraints
- **DRY Principle:** No duplicate calculation logic after Phase 1 completion
- **Single Responsibility:** Extracted methods must have clear, focused purpose
- **Dependency Inversion:** Services should accept dependencies via constructor when testability matters
- **No Premature Optimization:** Median calculation optimization documented only (not implemented until needed)

---

## Out of Scope

### Explicitly Excluded
- ❌ **Automated Unit Tests:** While DI improves testability, writing comprehensive test suite is separate project
- ❌ **CI/CD Integration:** Test automation setup not included in this plan
- ❌ **Quickselect Implementation:** Median optimization documented but not implemented (see Step 3.2 rationale)
- ❌ **Ship-Size Context Logic:** Infrastructure prepared but actual thresholds/phrases not implemented (requires product requirements)
- ❌ **Profiling Infrastructure:** Performance measurements manual (X-Debug or timing logs)
- ❌ **DI Container Framework:** Using manual instantiation in Router, not introducing framework like PHP-DI or Symfony
- ❌ **API Versioning:** All changes maintain v1.0 API contracts
- ❌ **Database Query Optimization:** Project uses in-memory x4-core data only
- ❌ **Frontend State Management Refactor:** useClassRange and usePhysicsCalculation remain separate

### Future Work (Post-Plan)
- **Phase 4: Automated Testing:** Write PHPUnit tests for PhysicsCalculationHelper, ClassRangeService
- **Phase 5: Profiling Dashboard:** Add development-mode performance metrics display
- **Phase 6: Ship-Size Context Implementation:** After product defines thresholds (e.g., "fast for L-class" at >150 m/s)

---

## Acceptance Criteria

### Phase 1 Completion Criteria
- ✅ PhysicsCalculationHelper trait/class exists with 3 extracted methods
- ✅ PhysicsService uses PhysicsCalculationHelper (no duplicate code)
- ✅ ClassRangeService uses PhysicsCalculationHelper (no duplicate code)
- ✅ ShipDef lookups cached in PhysicsService (variable reused)
- ✅ Composer autoload regenerated successfully
- ✅ All PHP files pass strict type checking
- ✅ Manual testing confirms identical output (before/after)

### Phase 2 Completion Criteria
- ✅ PhysicsService.calculatePhysics() reduced from 388 to <100 lines
- ✅ Private methods extracted: loadShipPhysicsData(), buildPhysicsResponse()
- ✅ API_TIMEOUTS configuration object exists in api.ts
- ✅ Class-range endpoint uses 15s timeout (up from 10s)
- ✅ ClassRangeEndpoint accepts dependencies via constructor
- ✅ Router.php instantiates and injects dependencies
- ✅ TypeScript compilation passes with zero errors
- ✅ Manual testing confirms identical behavior

### Phase 3 Completion Criteria
- ✅ shipSize parameter (no underscore) used in metricContext.ts
- ✅ TODO comments document future intent for size-specific phrases
- ✅ Skeleton code commented out (ready for future implementation)
- ✅ Median calculation strategy documented in tech-stack.md
- ✅ Performance thresholds documented in constraints.md
- ✅ Inline comments added to ClassRangeService median code
- ✅ No TypeScript warnings or errors

### Documentation Completion Criteria
- ✅ tech-stack.md updated (v1.1 → v1.2) with helper pattern and performance section
- ✅ public-api.md updated with PhysicsCalculationHelper methods
- ✅ constraints.md updated with DI best practices and performance thresholds
- ✅ file-tree.md updated with new helper file location
- ✅ API.md version incremented (no contract changes)
- ✅ All manifest documents include "Last Updated: February 15, 2026"

### Quality Gates (All Phases)
- ✅ Zero TypeScript compilation errors (`npx tsc --noEmit`)
- ✅ Zero PHP syntax errors (composer dump-autoload completes)
- ✅ Zero new security vulnerabilities introduced
- ✅ Backward compatibility maintained (existing API calls unchanged)
- ✅ Performance benchmarks meet or exceed targets:
  - ShipDef lookup: <5ms (cached scenario)
  - Class-range calculation: <600ms total
  - Single-ship calculation: <350ms total
- ✅ Code review score maintains ≥9.0/10 across all dimensions

---

## Testing Strategy

### Manual Testing (Primary Validation)

#### Phase 1 Testing
1. **Helper Extraction:**
   - Test PhysicsService with single ship (e.g., Cerberus Sentinel)
   - Verify percent change, drag change, inertia change calculations match pre-refactor values
   - Test ClassRangeService with ship type (e.g., mining_liquid)
   - Compare range metrics (min/max/median) with baseline values

2. **ShipDef Caching:**
   - Add timing logs before/after first and second ShipDef lookup
   - Verify second lookup uses cached value (zero lookup time)
   - Test with shipId + engineId both provided (dual-lookup scenario)
   - Test with shipId only (should still use cache internally)

#### Phase 2 Testing
3. **PhysicsService Refactoring:**
   - Run full physics calculation for all ship types (10+ test cases)
   - Compare JSON responses before/after refactoring (should be identical)
   - Verify extracted methods return expected data structures
   - Test error cases (null shipId, invalid engineId)

4. **API Timeouts:**
   - Simulate slow network with browser DevTools (throttle to 3G)
   - Verify class-range requests don't timeout prematurely
   - Confirm timeout errors show after configured duration (15s class-range, 10s single-ship)

5. **Dependency Injection:**
   - Instantiate ClassRangeEndpoint manually in test script
   - Pass mock services (optional, can use real services)
   - Verify handle() method executes without errors
   - Confirm Router.php instantiation works in production

#### Phase 3 Testing
6. **Context Phrases:**
   - Run TypeScript compiler: `npx tsc --noEmit`
   - Verify no "unused parameter" warnings
   - Test all context functions with sample values
   - Confirm output matches previous behavior (size parameter not yet used)

7. **Median Documentation:**
   - Review inline comments for clarity
   - Verify tech-stack.md section is comprehensive
   - Confirm constraints.md threshold is actionable

### Automated Testing (Optional/Future)

#### Unit Test Examples (PHPUnit)
```php
// tests/Unit/PhysicsCalculationHelperTest.php
public function testCalculatePercentChange() {
    $result = PhysicsCalculationHelper::calculatePercentChange(100.0, 150.0);
    $this->assertEquals(50.0, $result, delta: 0.01);
}

public function testCalculateAverageDragChange() {
    $dragArray = ['forward' => 1000.0, 'reverse' => 500.0, 'lateral' => 300.0];
    $result = PhysicsCalculationHelper::calculateAverageDragChange($dragArray, 2.0);
    $this->assertEquals(100.0, $result, delta: 0.01); // (2000+1000+600-1800)/3
}
```

#### Integration Test Examples (Optional)
```php
// tests/Integration/ClassRangeEndpointTest.php
public function testClassRangeCalculationWithMockedServices() {
    $mockShipData = $this->createMock(ShipDataService::class);
    $mockClassRange = $this->createMock(ClassRangeService::class);
    
    $endpoint = new ClassRangeEndpoint($mockShipData, $mockClassRange);
    $request = new ServerRequest(/* ... */);
    
    $response = $endpoint->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
}
```

### Performance Benchmarking

#### Baseline Measurements (Before Optimization)
```bash
# Use X-Debug profiler or manual timing
php -dxdebug.mode=profile gui/backend/public/index.php

# Or add timing logs in PhysicsService:
$start = microtime(true);
$shipDef = ShipDefs::getInstance()->getByID($shipId); // First lookup
$time1 = microtime(true) - $start;

$shipDef = ShipDefs::getInstance()->getByID($shipId); // Second lookup
$time2 = microtime(true) - $time1;

error_log("First lookup: {$time1}ms, Second lookup: {$time2}ms");
```

#### Target Benchmarks (After Optimization)
- **First ShipDef lookup:** ~2-5ms (unchanged)
- **Second ShipDef lookup (cached):** <0.1ms (~2x improvement)
- **PhysicsService total execution:** <300ms (unchanged)
- **ClassRangeService total execution:** <80ms (unchanged, but cleaner code)

### Regression Testing Checklist
- [ ] All 10 ship types load correctly in GUI dropdown
- [ ] Engine selection updates thrust values
- [ ] Cargo multiplier slider triggers class-range calculation
- [ ] Physics result cards display absolute metrics
- [ ] Range bars show min/median/max markers
- [ ] Worst-case/best-case ship names display
- [ ] Context phrases appear correctly
- [ ] No console errors in browser DevTools
- [ ] No PHP errors in backend logs

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Trait vs. Class Decision Wrong** | Low | Medium | Start with trait (more flexible), can convert to static class if needed without breaking consumers |
| **ShipDef Cache Introduces Stale Data** | Low | High | Verify $shipDef variable scope is limited to method execution (no instance property caching) |
| **PhysicsService Refactor Breaks Edge Cases** | Medium | High | Test with null shipId, invalid engineId, all ship types before/after. Maintain identical response structure. |
| **Longer Timeout Masks Performance Regression** | Low | Medium | Document baseline metrics before change. Monitor backend processing time separately from timeout. |
| **Dependency Injection Breaks Production Router** | Low | High | Verify Router.php instantiation logic locally before deployment. No lazy loading or complex DI framework needed. |
| **Context Phrases - Removing Underscore Breaks Strict Mode** | Very Low | Low | TypeScript compilation step will catch any issues immediately. Add TODO so intent is clear. |
| **Documentation Out of Sync** | Medium | Medium | Update all 5 manifest documents in same commit as code changes. Use checklist to verify. |
| **Performance Measurement Overhead** | Low | Low | Use lightweight timing logs only in development, remove before production. X-Debug profiling manual step. |
| **Backward Compatibility Break** | Low | Critical | All changes internal only. No API request/response structure modifications. Test with existing frontend. |
| **Composer Autoload Fails After Trait Creation** | Low | Medium | Run composer dump-autoload immediately after file creation. Verify trait is in PSR-4 path (src/Utils/). |

### Rollback Strategy

Each phase is independently deployable. If issues arise:

1. **Phase 1 Rollback:** Restore PhysicsService and ClassRangeService to previous versions, delete PhysicsCalculationHelper
2. **Phase 2 Rollback:** Revert individual files (api.ts timeout changes independent of DI changes independent of PhysicsService refactor)
3. **Phase 3 Rollback:** Restore metricContext.ts underscore prefix and remove documentation changes

**No database migrations or API versioning needed** — all changes are code-level only.

---

## Implementation Order (Gantt-Style Timeline)

### Single Developer, Sequential Execution

```
Day 1 (4 hours)
├─ Phase 1: Foundation
│  ├─ Hours 0-2.5: Extract PhysicsCalculationHelper trait ████████░░
│  └─ Hours 2.5-3.5: Cache ShipDef lookups ███░░░░░░░
│  └─ Hour 3.5-4: Testing & validation ██░░░░░░░░
│
Day 2 (6 hours)
├─ Phase 2: Structure
│  ├─ Hours 0-4: Refactor PhysicsService method █████████████░░░
│  ├─ Hours 4-5: Configurable API timeouts ███░░░░░░░
│  └─ Hours 5-6: Dependency injection for endpoint ███░░░░░░░
│
Day 3 (3 hours)
├─ Phase 3: Polish
│  ├─ Hours 0-2: Ship-size context infrastructure ██████░░░░
│  ├─ Hour 2-3: Document median optimization ███░░░░░░░
│  └─ Hour 3: Documentation updates ██░░░░░░░░
│
Total: 13 hours (within 12-15 hour estimate)
```

### Team Execution (Parallel Workstreams)

```
Developer A: Backend (4.5 hours)
├─ Day 1: Phase 1.1 + Phase 1.2 (3.5 hrs)
└─ Day 2: Phase 2.1 + Phase 2.3 (6 hrs) ← Blocks frontend integration

Developer B: Frontend (2 hours)
├─ Day 1: Phase 2.2 (1 hr) ← Independent
└─ Day 2: Phase 3.1 (2 hrs) ← Independent

Developer C: Documentation (3 hours)
└─ Day 3: All manifest updates (3 hrs) ← After code complete

Total: 2-3 days (parallelized, 9.5 hours total effort)
```

---

## Post-Implementation Checklist

### Code Verification
- [ ] All PHP files use `declare(strict_types=1)`
- [ ] PhysicsCalculationHelper trait registered in composer autoloader
- [ ] No duplicate calculation methods in PhysicsService or ClassRangeService
- [ ] ShipDef variable reused (no duplicate lookups)
- [ ] PhysicsService.calculatePhysics() <100 lines (vs 388 original)
- [ ] ClassRangeEndpoint uses constructor DI
- [ ] Router.php instantiates dependencies before injection

### TypeScript Verification
- [ ] `npx tsc --noEmit` runs without errors
- [ ] API_TIMEOUTS object defined with 4 entries
- [ ] metricContext.ts functions accept `shipSize` (no underscore)
- [ ] TODO comments explain future intent
- [ ] No unused parameter warnings

### Testing Verification
- [ ] PhysicsService outputs identical results (manual comparison)
- [ ] ClassRangeService outputs identical results (manual comparison)
- [ ] ShipDef caching reduces second lookup time to <0.1ms
- [ ] Class-range timeout set to 15s (up from 10s)
- [ ] All ship types load without errors in GUI
- [ ] No backend PHP errors in logs
- [ ] No frontend console errors

### Documentation Verification
- [ ] tech-stack.md updated (v1.1 → v1.2) with helper pattern
- [ ] public-api.md updated with PhysicsCalculationHelper methods
- [ ] constraints.md updated with DI and performance sections
- [ ] file-tree.md updated with new helper file path
- [ ] API.md version incremented
- [ ] All manifest "Last Updated" dates set to February 15, 2026

### Deployment Verification
- [ ] Composer autoloader regenerated (`composer dump-autoload`)
- [ ] Frontend rebuild completed (`npm run build` or `pnpm build`)
- [ ] Backend accessible at http://localhost:8080 (or configured port)
- [ ] Frontend accessible at http://localhost:5173 (or configured port)
- [ ] Integration test: Full calculation workflow (select ship → adjust slider → see results)
- [ ] No breaking changes to existing API consumers

---

## Success Metrics

### Quantitative Targets
- **Code Duplication:** 0 duplicate calculation methods (reduced from 3 pairs)
- **Method Length:** PhysicsService.calculatePhysics() <100 lines (reduced from 388)
- **Cache Hit Rate:** 100% on second ShipDef lookup
- **Performance Gain:** ~2x faster for dual-lookup scenarios (~4ms saved per request)
- **Technical Debt:** Near-zero (only median optimization deferred, but documented)
- **Test Failures:** 0 (manual regression testing passes 100%)
- **Documentation Coverage:** 100% (5/5 affected manifest documents updated)

### Qualitative Success Indicators
- ✅ Code review scores maintain ≥9.0/10 (based on previous project average)
- ✅ PhysicsCalculationHelper follows DRY principle
- ✅ Extracted methods have clear single responsibility
- ✅ Dependency injection enables future unit testing
- ✅ Context phrase infrastructure ready for product requirements
- ✅ Median optimization strategy clearly documented for future developers
- ✅ Zero backward compatibility breaks
- ✅ Deployment requires zero downtime

### Definition of "Done"
This optimization project is **DONE** when:
1. All 7 gold nuggets implemented/addressed (5 implemented, 2 documented)
2. All acceptance criteria met (32 checkboxes in this plan)
3. All post-implementation checklist items verified (28 checkboxes)
4. All manifest documents updated to v1.2 with current date
5. Code review conducted by peer or QA agent (scores ≥9.0/10)
6. User acceptance testing completed in local development environment
7. No open blockers or critical bugs

**Expected Outcome:** Physics Tuning GUI codebase with near-zero technical debt, improved performance, enhanced testability, and clear documentation for future enhancements.

---

**AGENT:** Planning  
**STATUS:** READY_FOR_PM
