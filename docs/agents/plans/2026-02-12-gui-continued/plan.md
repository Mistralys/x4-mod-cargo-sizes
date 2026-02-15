# Backend Implementation Plan: Ship & Engine Data Integration

> **Project:** Physics Tuning GUI - Phase 2  
> **Date:** February 12, 2026  
> **Status:** 🟡 **PLANNED**  
> **Priority:** MEDIUM  
> **Estimated Effort:** 8-12 hours

---

## Executive Summary

The Physics Tuning GUI currently uses **hardcoded placeholder data** for ships and engines (7 sample ships, 4 sample engines). This plan outlines the work required to integrate real X4 game data using the existing X4 Core library, enabling users to tune physics for **all game ships** instead of just the test subset.

---

## Current State Analysis

### Ships Data (Hardcoded)
**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getShipsByType(string $type): array` (lines 60-100)

**Current Implementation:**
```php
// Returns hardcoded array of 7 ships total:
'transport' => [
    ['id' => 'ship_arg_l_trans_container_01_a', 'name' => 'Colossus Vanguard', ...],
    ['id' => 'ship_arg_m_trans_container_01_a', 'name' => 'Mercury Vanguard', ...],
    ['id' => 'ship_par_xl_trans_container_01_a', 'name' => 'Shuyaku Vanguard', ...]
],
'mining' => [...],  // 2 ships
'auxiliary' => [...],  // 1 ship
'carrier' => [...]  // 1 ship
```

**TODO Comment Present:** "In production, this would query extracted game data to get all ships of this type"

### Ship Details (Partially Hardcoded)
**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getShipDetails(string $shipId): ShipDetails` (line 115)

**Issue:**
```php
$cargo = 10000.0; // Placeholder - needs real cargo capacity from ship storage modules
```

### Engine Compatibility (Hardcoded)
**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getEnginesForShip(string $shipId): array` (lines 145-160)

**Current Implementation:**
```php
// Returns hardcoded sample engines based on ship size only
private function getSampleEnginesBySize(string $size): array
{
    $engines = [
        's' => [['id' => 'engine_arg_s_allround_01_mk1', ...]],
        'm' => [['id' => 'engine_arg_m_allround_01_mk1', ...]],
        'l' => [['id' => 'engine_arg_l_allround_01_mk1', ...]],
        'xl' => [['id' => 'engine_arg_xl_allround_01_mk1', ...]]
    ];
    return $engines[$size] ?? $engines['m'];
}
```

**TODO Comment Present:** "In production, retrieve compatible engines from ShipDef"

### Engine Data (Partial Implementation)
**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getAllEngines(): array` (lines 168-186)

**Current State:**
- ✅ Uses X4 Core: `EngineDefs::getInstance()->getAll()`
- ❌ Only returns `thrustForward` (missing thrustReverse, thrustBoost, thrustTravel)

---

## Implementation Tasks

### Task 1: Implement Real Ship List Loading
**Priority: HIGH** | **Effort: 3-4 hours**

**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getShipsByType(string $type): array`

**Implementation Steps:**

1. **Use X4 Core ShipDefs API**
```php
public function getShipsByType(string $type): array
{
    if (!isset(self::SHIP_TYPE_MAP[$type])) {
        throw new GUIException(
            sprintf('Unknown ship type: %s', $type),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }
    
    $shipDefs = ShipDefs::getInstance();
    $ships = [];
    
    foreach ($shipDefs->getAll() as $shipDef) {
        // Determine if ship matches requested type
        $shipType = $this->determineShipType($shipDef);
        if ($shipType !== $type) {
            continue;
        }
        
        $shipId = $shipDef->getID();
        $size = $this->extractShipSize($shipId);
        
        // Filter by supported sizes (s, m, l, xl)
        if (!in_array($size, CargoSizeExtractor::SHIP_SIZES)) {
            continue;
        }
        
        $ships[] = [
            'id' => $shipId,
            'name' => $shipDef->getLabel(),
            'size' => $size,
            'mass' => $shipDef->getMass(),
            'cargo' => $this->getShipCargoCapacity($shipDef)  // See Task 2
        ];
    }
    
    return $ships;
}
```

2. **Implement Ship Type Classification**
```php
/**
 * Determines ship type (transport, mining, auxiliary, carrier) from ShipDef.
 *
 * @param \Mistralys\X4\Database\Ships\ShipDef $shipDef
 * @return string Ship type or null if not classifiable
 */
private function determineShipType(\Mistralys\X4\Database\Ships\ShipDef $shipDef): ?string
{
    $shipId = $shipDef->getID();
    
    // Strategy 1: Use existing SHIP_TYPE_MAP classification from CargoSizeExtractor
    foreach (self::SHIP_TYPE_MAP as $type => $idPrefixes) {
        foreach ($idPrefixes as $prefix) {
            if (str_starts_with($shipId, $prefix)) {
                return $type;
            }
        }
    }
    
    // Strategy 2: Check ship class/purpose if available from X4 Core
    // TODO: Investigate if ShipDef has getClass(), getPurpose(), or getTags() methods
    
    return null;
}
```

**Research Required:**
- Investigate `ShipDef` API methods: `get_class_methods($shipDef)`
- Confirm ship classification approach (ID patterns vs. metadata)
- Verify SHIP_TYPE_MAP coverage for all game ships

---

### Task 2: Get Real Ship Cargo Capacity
**Priority: HIGH** | **Effort: 2-3 hours**

**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getShipCargoCapacity(\Mistralys\X4\Database\Ships\ShipDef $shipDef): float`

**Implementation Steps:**

1. **Investigate X4 Core Ship Storage API**
```php
// Research needed: Check if ShipDef has methods like:
$shipDef->getStorageModules();
$shipDef->getCargoCapacity();
$shipDef->getStorage();
```

2. **Implement Cargo Capacity Extraction**
```php
/**
 * Extracts total cargo capacity from ship definition.
 *
 * @param \Mistralys\X4\Database\Ships\ShipDef $shipDef
 * @return float Total cargo capacity in cubic meters
 */
private function getShipCargoCapacity(\Mistralys\X4\Database\Ships\ShipDef $shipDef): float
{
    // TODO: Investigate X4 Core API for cargo/storage access
    
    // Possible approaches:
    // 1. $shipDef->getCargoCapacity() (if method exists)
    // 2. Sum storage modules: $shipDef->getStorageModules()->getTotalCapacity()
    // 3. Parse ship XML directly if X4 Core doesn't expose it
    
    // Fallback: Return default based on ship size if method not available
    $size = $this->extractShipSize($shipDef->getID());
    return match($size) {
        's' => 5000.0,
        'm' => 12000.0,
        'l' => 30000.0,
        'xl' => 50000.0,
        default => 10000.0
    };
}
```

**Research Required:**
- Document available X4 Core API methods for cargo/storage
- Verify cargo values against game data
- Handle ships with no cargo (combat vessels)

---

### Task 3: Implement Real Engine Compatibility
**Priority: MEDIUM** | **Effort: 2-3 hours**

**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getEnginesForShip(string $shipId): array`

**Implementation Steps:**

1. **Get Compatible Engines from ShipDef**
```php
public function getEnginesForShip(string $shipId): array
{
    try {
        $shipDef = ShipDefs::getInstance()->getByID($shipId);
        
        // Strategy 1: Check if ShipDef provides compatible engines directly
        // $compatibleEngines = $shipDef->getCompatibleEngines();
        
        // Strategy 2: Filter all engines by size/class compatibility
        $size = $this->extractShipSize($shipId);
        $allEngines = EngineDefs::getInstance()->getAll();
        $compatibleEngines = [];
        
        foreach ($allEngines as $engineDef) {
            // Check if engine size matches ship size
            $engineSize = $this->extractEngineSize($engineDef->getID());
            if ($engineSize !== $size) {
                continue;
            }
            
            // TODO: Add class/type compatibility checks if available
            // e.g., military engines only for military ships
            
            $compatibleEngines[] = [
                'id' => $engineDef->getID(),
                'name' => $engineDef->getLabel(),
                'thrustForward' => $engineDef->getThrustForward(),
                'thrustReverse' => $this->getEngineThrustReverse($engineDef),  // See Task 4
                'thrustBoost' => $this->getEngineThrustBoost($engineDef),      // See Task 4
                'thrustTravel' => $this->getEngineThrustTravel($engineDef)     // See Task 4
            ];
        }
        
        return $compatibleEngines;
        
    } catch (\Exception $e) {
        throw new GUIException(
            sprintf('Failed to get engines for ship %s: %s', $shipId, $e->getMessage()),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE,
            $e
        );
    }
}
```

2. **Add Engine Size Extraction Helper**
```php
/**
 * Extracts engine size from engine ID.
 *
 * @param string $engineId Engine identifier
 * @return string Size code (s, m, l, xl)
 */
private function extractEngineSize(string $engineId): string
{
    // Engine IDs follow pattern: engine_{faction}_{size}_...
    // Example: engine_arg_m_allround_01_mk1
    foreach (['s', 'm', 'l', 'xl'] as $size) {
        if (str_contains($engineId, '_' . $size . '_')) {
            return $size;
        }
    }
    return 'm'; // Default
}
```

**Research Required:**
- Check if ShipDef provides compatible engines list
- Verify engine size extraction logic
- Document engine compatibility rules (if any beyond size matching)

---

### Task 4: Add Full Engine Thrust Data
**Priority: LOW** | **Effort: 1-2 hours**

**File:** `gui/backend/src/Services/ShipDataService.php`  
**Methods:** Multiple helper methods + update `getAllEngines()`

**Implementation Steps:**

1. **Research EngineDef API for Thrust Values**
```php
// Test what methods are available:
$engineDef = EngineDefs::getInstance()->getAll()[0];
var_dump(get_class_methods($engineDef));

// Look for methods like:
// - getThrustReverse()
// - getThrustBoost()
// - getThrustTravel()
// - getThrust() with parameters
```

2. **Implement Thrust Extraction Helpers**
```php
/**
 * Gets reverse thrust for engine.
 *
 * @param \Mistralys\X4\Database\Engines\EngineDef $engineDef
 * @return float|null Reverse thrust in kN
 */
private function getEngineThrustReverse(\Mistralys\X4\Database\Engines\EngineDef $engineDef): ?float
{
    // TODO: Check if method exists
    if (method_exists($engineDef, 'getThrustReverse')) {
        return $engineDef->getThrustReverse();
    }
    
    // Fallback: Estimate as 50% of forward thrust
    return $engineDef->getThrustForward() * 0.5;
}

/**
 * Gets boost thrust for engine.
 *
 * @param \Mistralys\X4\Database\Engines\EngineDef $engineDef
 * @return float|null Boost thrust in kN
 */
private function getEngineThrustBoost(\Mistralys\X4\Database\Engines\EngineDef $engineDef): ?float
{
    // TODO: Check if method exists
    if (method_exists($engineDef, 'getThrustBoost')) {
        return $engineDef->getThrustBoost();
    }
    
    // Fallback: Estimate as 200% of forward thrust
    return $engineDef->getThrustForward() * 2.0;
}

/**
 * Gets travel thrust for engine.
 *
 * @param \Mistralys\X4\Database\Engines\EngineDef $engineDef
 * @return float|null Travel thrust in kN
 */
private function getEngineThrustTravel(\Mistralys\X4\Database\Engines\EngineDef $engineDef): ?float
{
    // TODO: Check if method exists
    if (method_exists($engineDef, 'getThrustTravel')) {
        return $engineDef->getThrustTravel();
    }
    
    // Fallback: Estimate as 400% of forward thrust
    return $engineDef->getThrustForward() * 4.0;
}
```

3. **Update getAllEngines() Method**
```php
public function getAllEngines(): array
{
    try {
        $engineDefs = EngineDefs::getInstance();
        $engines = [];

        foreach ($engineDefs->getAll() as $engineDef) {
            $engines[] = [
                'id' => $engineDef->getID(),
                'name' => $engineDef->getLabel(),
                'thrustForward' => $engineDef->getThrustForward(),
                'thrustReverse' => $this->getEngineThrustReverse($engineDef),  // NEW
                'thrustBoost' => $this->getEngineThrustBoost($engineDef),      // NEW
                'thrustTravel' => $this->getEngineThrustTravel($engineDef)     // NEW
            ];
        }

        return $engines;
    } catch (\Exception $e) {
        throw new GUIException(
            'Failed to get engines: ' . $e->getMessage(),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE,
            $e
        );
    }
}
```

**Research Required:**
- Document all available thrust methods in EngineDef
- Verify actual thrust ratios (reverse, boost, travel vs forward)
- Confirm thrust values match game data

---

### Task 5: Update Ship Details Method
**Priority: MEDIUM** | **Effort: 1 hour**

**File:** `gui/backend/src/Services/ShipDataService.php`  
**Method:** `getShipDetails(string $shipId): ShipDetails`

**Implementation:**

```php
public function getShipDetails(string $shipId): ShipDetails
{
    try {
        $shipDef = ShipDefs::getInstance()->getByID($shipId);
        
        return new ShipDetails(
            id: $shipId,
            name: $shipDef->getLabel(),
            type: $this->determineShipType($shipDef),  // From Task 1
            size: $this->extractShipSize($shipId),
            mass: $shipDef->getMass(),
            cargo: $this->getShipCargoCapacity($shipDef),  // From Task 2
            engines: array_column($this->getEnginesForShip($shipId), 'id')  // From Task 3
        );
    } catch (\Exception $e) {
        throw new GUIException(
            sprintf('Failed to get ship details for %s: %s', $shipId, $e->getMessage()),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE,
            $e
        );
    }
}
```

---

## Testing Strategy

### Unit Tests
**Effort: 2 hours**

Create integration tests for each method:

```php
// tests/GUI/Services/ShipDataServiceTest.php

class ShipDataServiceTest extends TestCase
{
    private ShipDataService $service;
    
    protected function setUp(): void
    {
        $this->service = new ShipDataService();
    }
    
    public function testGetShipsByType_Transport_ReturnsMultipleShips()
    {
        $ships = $this->service->getShipsByType('transport');
        
        $this->assertIsArray($ships);
        $this->assertGreaterThan(7, count($ships)); // More than sample data
        
        foreach ($ships as $ship) {
            $this->assertArrayHasKey('id', $ship);
            $this->assertArrayHasKey('name', $ship);
            $this->assertArrayHasKey('size', $ship);
            $this->assertArrayHasKey('mass', $ship);
            $this->assertArrayHasKey('cargo', $ship);
            $this->assertGreaterThan(0, $ship['cargo']); // Real cargo value
        }
    }
    
    public function testGetShipDetails_ReturnRealCargoCapacity()
    {
        // Use known ship ID
        $details = $this->service->getShipDetails('ship_arg_m_trans_container_01_a');
        
        $this->assertNotEquals(10000.0, $details->cargo); // Not placeholder
        $this->assertGreaterThan(0, $details->cargo);
    }
    
    public function testGetEnginesForShip_ReturnsFullThrustData()
    {
        $engines = $this->service->getEnginesForShip('ship_arg_m_trans_container_01_a');
        
        $this->assertGreaterThan(1, count($engines)); // More than 1 sample engine
        
        foreach ($engines as $engine) {
            $this->assertArrayHasKey('thrustForward', $engine);
            $this->assertArrayHasKey('thrustReverse', $engine);
            $this->assertArrayHasKey('thrustBoost', $engine);
            $this->assertArrayHasKey('thrustTravel', $engine);
        }
    }
}
```

### Manual API Testing
**Effort: 30 minutes**

Test endpoints with real data:

```bash
# 1. Get transport ships (should return >7 ships)
curl http://localhost:8080/api/ships/transport

# 2. Get ship details (should have real cargo value)
curl http://localhost:8080/api/ships/details/ship_arg_m_trans_container_01_a

# 3. Get engines for ship (should return multiple engines)
curl http://localhost:8080/api/ships/ship_arg_m_trans_container_01_a/engines

# 4. Get all engines (should have full thrust data)
curl http://localhost:8080/api/engines | jq '.[0]'  # Check first engine

# 5. Verify calculation still works with real data
curl -X POST http://localhost:8080/api/calculate/physics \
  -H "Content-Type: application/json" \
  -d '{"baseMass": 5000, "originalCargo": 12000, ...}'
```

### Frontend Validation
**Effort: 30 minutes**

1. Start GUI: `composer gui:start-win`
2. Browse to: `http://localhost:5173`
3. Verify:
   - ✅ Transport ships show >7 options
   - ✅ Engine list shows multiple options per ship
   - ✅ Ship details show realistic cargo values
   - ✅ Physics calculations work with real data
   - ✅ No console errors
   - ✅ UI remains responsive

---

## X4 Core API Research Tasks

### Priority Research Items

Before implementation, document these X4 Core APIs:

#### 1. ShipDef Methods
```php
$shipDef = ShipDefs::getInstance()->getByID('ship_arg_m_trans_container_01_a');

// Document available methods:
var_dump(get_class_methods($shipDef));

// Key questions:
// - How to get cargo capacity?
// - How to determine ship type/classification?
// - How to get compatible engines?
// - Are there ship tags/categories available?
```

#### 2. EngineDef Methods
```php
$engineDef = EngineDefs::getInstance()->getAll()[0];

// Document available methods:
var_dump(get_class_methods($engineDef));

// Key questions:
// - Does getThrustReverse() exist?
// - Does getThrustBoost() exist?
// - Does getThrustTravel() exist?
// - What thrust values are available?
```

#### 3. Ship Storage/Cargo API
```php
// Investigate storage module access:
$shipDef = ShipDefs::getInstance()->getByID('ship_arg_m_trans_container_01_a');

// Check for:
// - $shipDef->getStorageModules()
// - $shipDef->getCargoCapacity()
// - $shipDef->getStorage()
// - $shipDef->getStorageTotal()
```

**Deliverable:** Create `docs/agents/x4-core-api-research.md` with findings

---

## Implementation Order

### Phase 1: Research (Day 1)
**Effort: 2 hours**

1. ✅ Review X4 Core API documentation
2. ✅ Test ShipDef methods
3. ✅ Test EngineDef methods
4. ✅ Document findings in research doc
5. ✅ Finalize implementation strategy

### Phase 2: Core Implementation (Day 1-2)
**Effort: 6-8 hours**

1. ✅ Task 1: Implement real ship list loading (3-4 hours)
2. ✅ Task 2: Get real ship cargo capacity (2-3 hours)
3. ✅ Task 5: Update ship details method (1 hour)

### Phase 3: Enhancement (Day 2)
**Effort: 3-4 hours**

1. ✅ Task 3: Implement real engine compatibility (2-3 hours)
2. ✅ Task 4: Add full engine thrust data (1-2 hours)

### Phase 4: Testing & Validation (Day 2-3)
**Effort: 2-3 hours**

1. ✅ Write unit tests (2 hours)
2. ✅ Manual API testing (30 min)
3. ✅ Frontend validation (30 min)
4. ✅ Bug fixes as needed (1 hour buffer)

---

## Expected Outcomes

### Before Implementation
- ❌ 7 sample ships available
- ❌ 4 sample engines (1 per size)
- ❌ Placeholder cargo value (10000.0)
- ❌ Limited ship/engine combinations

### After Implementation
- ✅ **All X4 game ships** available (~100+ ships)
- ✅ **All X4 engines** available (~130 engines)
- ✅ **Real cargo capacity** values from game data
- ✅ **Proper engine compatibility** matching
- ✅ **Full thrust data** (forward, reverse, boost, travel)

### User Experience Impact

**Before:**
```
Transport Ships:
  - Colossus Vanguard (L)
  - Mercury Vanguard (M)
  - Shuyaku Vanguard (XL)

Engines for Mercury Vanguard:
  - Argon M Engine MK1  [thrust: 1500 kN]
```

**After:**
```
Transport Ships:
  - Colossus Vanguard (L)
  - Mercury Vanguard (M)
  - Shuyaku Vanguard (XL)
  - ... [97+ more ships]

Engines for Mercury Vanguard:
  - Argon M Engine MK1     [F: 1500, R: 750, B: 3000, T: 6000]
  - Argon M Engine MK2     [F: 1800, R: 900, B: 3600, T: 7200]
  - Paranid M Engine MK1   [F: 1600, R: 800, B: 3200, T: 6400]
  - ... [10-15+ more engines]
```

---

## Risk Assessment

### Low Risk
- ✅ X4 Core library already integrated
- ✅ API structure already defined
- ✅ Frontend already handles dynamic data
- ✅ Sample data validates API contract

### Medium Risk
- ⚠️ X4 Core API may not expose all needed data (cargo capacity, engine thrust variants)
- **Mitigation:** Implement fallback strategies (size-based estimates, thrust ratios)

### Zero Risk
- ✅ Frontend changes: None required (already designed for dynamic data)
- ✅ API contract changes: None required (response format stays same)
- ✅ Backwards compatibility: Sample data can coexist during development

---

## Success Criteria

### Functional Criteria
- ✅ All X4 game ships appear in ship selection (>90 ships)
- ✅ Real cargo capacity values displayed (not 10000.0 placeholder)
- ✅ Multiple engines available per ship (>1 per size)
- ✅ Full thrust data returned (forward, reverse, boost, travel)
- ✅ Physics calculations work with real ship/engine data

### Quality Criteria
- ✅ All existing tests still pass (147/147)
- ✅ New unit tests added for data loading methods
- ✅ No performance degradation (<500ms response time)
- ✅ No console errors in browser
- ✅ Code follows project conventions (strict types, PHPDoc, etc.)

### Documentation Criteria
- ✅ X4 Core API research documented
- ✅ Implementation notes added to code comments
- ✅ Known limitations documented (if any fallbacks used)
- ✅ Testing results recorded

---

## Related Code Review Recommendations

This implementation addresses the following items from the project work report:

### CR-004: Add Caching for Ship/Engine Data Lookups
**Priority: MEDIUM** | **Effort: 1 hour**

Since we'll be loading all ships/engines from X4 Core, implement caching:

```php
class ShipDataService
{
    private static ?array $shipCache = null;
    private static ?array $engineCache = null;
    
    public function getShipsByType(string $type): array
    {
        if (self::$shipCache === null) {
            self::$shipCache = $this->loadAllShips();
        }
        
        return array_filter(
            self::$shipCache,
            fn($ship) => $ship['type'] === $type
        );
    }
    
    private function loadAllShips(): array
    {
        // Load once, cache forever (data doesn't change at runtime)
        // Implementation from Task 1
    }
}
```

**Benefit:** Reduces repeated X4 Core queries, improves responsiveness

---

## Appendix: Code Review Context

From [2026-02-11-physics-tuning-gui-work-report.md](2026-02-11-physics-tuning-gui-work-report.md):

**Deliberate Technical Debt - Sample Ship Data:**
> **Location:** `ShipDataService.php` lines 60-100  
> **Reason:** Temporary for initial GUI implementation  
> **Mitigation:** TODO comments indicate future enhancement. Clear extension path via ship data extraction integration.  
> **Priority:** MEDIUM  
> **Impact:** Limits ship selection to small subset

This plan directly addresses this documented technical debt item.

---

**Plan Created:** February 12, 2026  
**Author:** System Architect Agent  
**Status:** Ready for Implementation
