# Public API - Signatures Only

> **Version:** 1.3  
> **Last Updated:** February 16, 2026  
> **Purpose:** Public method signatures and contracts (NO implementations)

---

## Overview

This document lists **all public properties, methods, and constructors** for the GUI codebase. **Implementation details are NOT included** - only signatures and contracts.

Use this as a quick reference to understand what methods exist without reading source code.

---

## Table of Contents

1. [Backend Utilities](#backend-utilities)
2. [Backend API](#backend-api)
3. [Backend Services](#backend-services)
4. [Backend DTOs](#backend-dtos)
5. [Backend Exceptions](#backend-exceptions)
6. [Frontend Services (API Client)](#frontend-services-api-client)
7. [Frontend Hooks](#frontend-hooks)
8. [Frontend TypeScript Types](#frontend-typescript-types)

---

## Backend Utilities

### PhysicsCalculationHelper (Trait)

**Location:** `gui/backend/src/Utils/PhysicsCalculationHelper.php`  
**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Utils`  
**Since:** 1.2.0  
**Usage:** Mixed into `PhysicsService` and `ClassRangeService`

**Purpose:** Shared physics calculation utilities to eliminate code duplication.

#### Methods

##### calculatePercentChange()

```php
private function calculatePercentChange(float $original, float $modified): float
```

Calculate percentage change between original and modified values.

**Parameters:**
- `$original` — Original value
- `$modified` — Modified value

**Returns:** Percentage change (positive = increase, negative = decrease). Returns 0.0 if original is zero.

**Formula:** `((modified - original) / original) * 100`

---

##### calculateAverageDragChange()

```php
private function calculateAverageDragChange(
    \Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag $original,
    \Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag $adjusted
): float
```

Calculate average drag change percentage across all drag axes.

**Parameters:**
- `$original` — Original drag values (forward, reverse, horizontal, vertical, pitch, yaw, roll)
- `$adjusted` — Adjusted drag values after modifications

**Returns:** Average percentage change across all 7 drag axes.

---

##### calculateAverageInertiaChange()

```php
private function calculateAverageInertiaChange(
    \Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia $original,
    \Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia $adjusted
): float
```

Calculate average inertia change percentage across all inertia axes.

**Parameters:**
- `$original` — Original inertia values (pitch, yaw, roll)
- `$adjusted` — Adjusted inertia values after modifications

**Returns:** Average percentage change across all 3 inertia axes.

---

## Backend API

### ServiceContainer

**Location:** `gui/backend/src/API/ServiceContainer.php`  
**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\API`  
**Since:** 1.3.0

**Purpose:** Lightweight dependency injection container for service lifecycle management. Provides service registration and retrieval with lazy instantiation and singleton pattern.

**Not PSR-11 compliant** (intentionally lightweight for local-only tool).

#### Methods

##### register()

```php
public function register(string $id, callable $factory): void
```

Register a service factory function.

Factory is not called until `get()` is invoked (lazy instantiation). Factory receives container instance as parameter for dependency resolution.

**Parameters:**
- `$id` — Service identifier (e.g., 'ship_data', 'physics')
- `$factory` — Factory function: `function(ServiceContainer): object`

**Returns:** void

**Throws:** None

**Example:**
```php
// Simple service (no dependencies)
$container->register('logger', fn() => new Logger());

// Service with dependencies
$container->register('user_service', fn(ServiceContainer $c) =>
    new UserService($c->get('logger'))
);
```

---

##### get()

```php
public function get(string $id): object
```

Get a service instance (lazy instantiation, singleton pattern).

If service not yet instantiated, calls factory and caches result. Subsequent calls return cached instance (singleton pattern).

**Parameters:**
- `$id` — Service identifier

**Returns:** Service instance (object)

**Throws:** `RuntimeException` if service not registered

---

##### has()

```php
public function has(string $id): bool
```

Check if service is registered.

Returns true if a factory is registered for the given service ID, regardless of whether the service has been instantiated yet.

**Parameters:**
- `$id` — Service identifier

**Returns:** True if service factory registered, false otherwise

**Throws:** None

---

## Backend Services

### PhysicsService

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Services`

**Purpose:** Physics calculation service wrapping PhysicsCalculator.

**Uses:** `PhysicsCalculationHelper` trait (since 1.2.0) for shared calculation methods.

```php
class PhysicsService
{
    /**
     * Calculates adjusted physics values for a ship.
     *
     * @param PhysicsRequest $request Physics calculation parameters
     * @return PhysicsResponse Complete physics calculation results
     * @throws GUIException
     */
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse;
    
    /**
     * Calculate physics for multiple configurations in batch.
     *
     * @param array<PhysicsRequest> $requests Array of physics requests
     * @return array<PhysicsResponse> Array of physics responses
     * @throws GUIException
     */
    public function calculateBatch(array $requests): array;
}
```

---

### ShipDataService

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Services`

**Purpose:** Ship and engine data service using X4 Core.

```php
class ShipDataService
{
    /**
     * Gets all supported ship types.
     *
     * @return array{type: string, label: string}[]
     */
    public function getShipTypes(): array;
    
    /**
     * Gets ships filtered by type.
     *
     * @param string $type Ship type (transport, mining, auxiliary, carrier)
     * @return array<array{id: string, name: string, size: string, mass: float, cargo: float}>
     * @throws GUIException
     */
    public function getShipsByType(string $type): array;
    
    /**
     * Gets detailed information about a specific ship.
     *
     * @param string $shipId Ship identifier
     * @return ShipDetails
     * @throws GUIException
     */
    public function getShipDetails(string $shipId): ShipDetails;
    
    /**
     * Gets compatible engines for a ship.
     *
     * @param string $shipId Ship identifier
     * @return array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>
     * @throws GUIException
     */
    public function getEnginesForShip(string $shipId): array;
    
    /**
     * Gets all available engines.
     *
     * @return array<array{id: string, name: string, thrustForward: float}>
     * @throws GUIException
     */
    public function getAllEngines(): array;
}
```

---

### ClassRangeService

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Services`

**Purpose:** Class-wide aggregation service for calculating min/max/median ranges across all ships of a type.

**Uses:** `PhysicsCalculationHelper` trait (since 1.2.0) for shared calculation methods.

```php
class ClassRangeService
{
    /**
     * Constructor with dependency injection.
     * 
     * @param ShipDataService $shipDataService Ship data provider
     * @since 1.2.0
     */
    public function __construct(ShipDataService $shipDataService);
    
    /**
     * Calculates class-wide metric ranges for all ships of a given type.
     *
     * @param ClassRangeRequest $request Class-range calculation parameters
     * @return ClassRangeResponse Aggregated min/max/median metrics with worst/best case ships
     * @throws GUIException
     */
    public function calculateClassRange(ClassRangeRequest $request): ClassRangeResponse;
}
```

---

### ConfigService

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Services`

**Purpose:** Configuration management service for build-config.json.

```php
class ConfigService
{
    /**
     * Gets the current configuration.
     *
     * @return array<string, mixed>
     * @throws GUIException
     */
    public function getConfig(): array;
    
    /**
     * Updates the configuration file.
     *
     * @param array<string, mixed> $config Configuration array
     * @return void
     * @throws GUIException
     */
    public function updateConfig(array $config): void;
    
    /**
     * Validates configuration without saving.
     *
     * @param array<string, mixed> $config Configuration array
     * @return ValidationResult
     */
    public function validateConfig(array $config): ValidationResult;
}
```

---

## Backend DTOs

### PhysicsRequest

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Input contract for physics calculations.

```php
class PhysicsRequest
{
    /**
     * Constructor.
     *
     * @param float $baseMass Ship mass without cargo
     * @param float $originalCargo Original cargo capacity
     * @param float $adjustedCargo Adjusted cargo capacity
     * @param float $cargoMultiplier Cargo multiplier (2x, 4x, 8x, etc.)
     * @param bool $useEffectiveRatioCap Whether to cap effective ratio
     * @param float $dragReductionFactor Drag reduction factor config
     * @param float $inertiaImpactFactor Inertia impact factor config
     * @param float $accelerationResponsiveness Acceleration responsiveness config
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $dragReductionTiers Drag reduction tiers
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $jerkReductionTiers Jerk reduction tiers
     * @param string|null $engineId Optional engine ID for performance calculations
     * @param string|null $shipId Optional ship ID for real per-ship data lookup
     */
    public function __construct(
        public readonly float $baseMass,
        public readonly float $originalCargo,
        public readonly float $adjustedCargo,
        public readonly float $cargoMultiplier,
        public readonly bool $useEffectiveRatioCap,
        public readonly float $dragReductionFactor,
        public readonly float $inertiaImpactFactor,
        public readonly float $accelerationResponsiveness,
        public readonly array $dragReductionTiers,
        public readonly array $jerkReductionTiers,
        public readonly ?string $engineId = null,
        public readonly ?string $shipId = null
    );
    
    /**
     * Create from array (typically from JSON request).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self;
}
```

---

### PhysicsResponse

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Output contract for physics calculations with all calculated values.

```php
class PhysicsResponse
{
    /**
     * Constructor.
     *
     * @param float $massRatio Mass ratio (adjustedFullMass / originalFullMass)
     * @param float $effectiveRatio Effective ratio (capped if enabled)
     * @param float $originalFullMass Original full mass
     * @param float $adjustedFullMass Adjusted full mass
     * @param float $massIncrease Mass increase amount
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragOriginal Original drag values
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragAdjusted Adjusted drag values
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragPercentChange Drag percentage changes
     * @param array{pitch: float, yaw: float, roll: float} $inertiaOriginal Original inertia values
     * @param array{pitch: float, yaw: float, roll: float} $inertiaAdjusted Adjusted inertia values
     * @param array{pitch: float, yaw: float, roll: float} $inertiaPercentChange Inertia percentage changes
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkOriginal Original jerk values
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkAdjusted Adjusted jerk values
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkPercentChange Jerk percentage changes
     * @param EnginePerformance|null $enginePerformance Optional engine performance metrics
     * @param string $activeTier Active tier description
     * @param array{original: float, adjusted: float}|null $topSpeed Optional top speed in m/s (original and adjusted)
     * @param array{original: float, adjusted: float}|null $acceleration Optional acceleration in m/s² (original and adjusted)
     */
    public function __construct(
        public readonly float $massRatio,
        public readonly float $effectiveRatio,
        public readonly float $originalFullMass,
        public readonly float $adjustedFullMass,
        public readonly float $massIncrease,
        public readonly array $dragOriginal,
        public readonly array $dragAdjusted,
        public readonly array $dragPercentChange,
        public readonly array $inertiaOriginal,
        public readonly array $inertiaAdjusted,
        public readonly array $inertiaPercentChange,
        public readonly array $jerkOriginal,
        public readonly array $jerkAdjusted,
        public readonly array $jerkPercentChange,
        public readonly ?EnginePerformance $enginePerformance = null,
        public readonly string $activeTier = '',
        public readonly ?array $topSpeed = null,
        public readonly ?array $acceleration = null
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### EnginePerformance

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Engine performance metrics (TWR, acceleration estimates).

```php
class EnginePerformance
{
    /**
     * Constructor.
     *
     * @param string $engineId Engine identifier
     * @param float $thrustForward Forward thrust in kN
     * @param float $originalTWR Original thrust-to-weight ratio
     * @param float $adjustedTWR Adjusted thrust-to-weight ratio after mass increase
     * @param float $twrReductionPercent Percentage reduction in TWR
     * @param float $originalAcceleration Original estimated acceleration in m/s²
     * @param float $adjustedAcceleration Adjusted estimated acceleration in m/s²
     * @param int $engineCount Number of engines used for calculations
     * @param float|null $topSpeed Top speed in normal flight (m/s)
     * @param float|null $topSpeedAdjusted Adjusted top speed after mass increase
     * @param float|null $topSpeedReverse Top speed in reverse flight (m/s)
     * @param float|null $topSpeedBoost Top speed in boost flight (m/s)
     * @param float|null $topSpeedTravel Top speed in travel flight (m/s)
     */
    public function __construct(
        public readonly string $engineId,
        public readonly float $thrustForward,
        public readonly float $originalTWR,
        public readonly float $adjustedTWR,
        public readonly float $twrReductionPercent,
        public readonly float $originalAcceleration,
        public readonly float $adjustedAcceleration,
        public readonly int $engineCount = 1,
        public readonly ?float $topSpeed = null,
        public readonly ?float $topSpeedAdjusted = null,
        public readonly ?float $topSpeedReverse = null,
        public readonly ?float $topSpeedBoost = null,
        public readonly ?float $topSpeedTravel = null
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### PhysicsData

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Encapsulates original and adjusted physics values for response building. Groups related physics data (drag, inertia, jerk) with their original and adjusted values to reduce parameter count in response builders.

**Since:** 1.3.0

```php
final readonly class PhysicsData
{
    /**
     * Constructor.
     *
     * @param Drag $originalDrag Original drag values from ship definition
     * @param AdjustedDrag $adjustedDrag Adjusted drag values after cargo increase
     * @param Inertia $originalInertia Original inertia values from ship definition
     * @param AdjustedInertia $adjustedInertia Adjusted inertia values after cargo increase
     * @param Jerk $originalJerk Original jerk values from ship definition
     * @param AdjustedJerk $adjustedJerk Adjusted jerk values after cargo increase
     */
    public function __construct(
        public Drag $originalDrag,
        public AdjustedDrag $adjustedDrag,
        public Inertia $originalInertia,
        public AdjustedInertia $adjustedInertia,
        public Jerk $originalJerk,
        public AdjustedJerk $adjustedJerk
    );
}
```

---

### ReductionTiers

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Encapsulates drag and jerk reduction tier configuration. Groups reduction tier values to reduce parameter count in response builders and provide convenient tier-level operations.

**Since:** 1.3.0

```php
final readonly class ReductionTiers
{
    /**
     * Constructor.
     *
     * @param ReductionTier $drag Drag reduction tier configuration
     * @param ReductionTier $jerk Jerk reduction tier configuration
     */
    public function __construct(
        public ReductionTier $drag,
        public ReductionTier $jerk
    );

    /**
     * Get formatted active tier label for display.
     *
     * Generates a human-readable label showing the reduction percentages
     * for both drag and jerk tiers (e.g., "Drag: 25% reduction | Jerk: 33% reduction").
     *
     * @return string Formatted tier label with reduction percentages
     */
    public function getActiveTierLabel(): string;
}
```

---

### PhysicsResponseData

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Parameter object for PhysicsService::buildPhysicsResponse() method. Encapsulates all data needed to construct a PhysicsResponse, reducing method signature from 5 parameters to 1.

Follows Parameter Object pattern to improve code readability and make method signatures more maintainable.

**Since:** 1.3.0

```php
readonly class PhysicsResponseData
{
    /**
     * Constructor.
     *
     * @param PhysicsCalculator $calculator Physics calculator with mass calculations
     * @param PhysicsData $physicsData Original and adjusted physics values (drag, inertia, jerk)
     * @param ReductionTiers $tiers Active reduction tiers for drag and jerk
     * @param PhysicsRequest $request Original request data
     * @param EnginePerformance|null $enginePerformance Engine performance metrics (optional)
     */
    public function __construct(
        public PhysicsCalculator $calculator,
        public PhysicsData $physicsData,
        public ReductionTiers $tiers,
        public PhysicsRequest $request,
        public ?EnginePerformance $enginePerformance
    );
}
```

**Usage Example:**
```php
$responseData = new PhysicsResponseData(
    calculator: $calculator,
    physicsData: $physicsData,
    tiers: $tiers,
    request: $request,
    enginePerformance: $enginePerformance
);

return $this->buildPhysicsResponse($responseData);
```

---

### ShipDetails

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Ship information for API responses.

```php
class ShipDetails
{
    /**
     * Constructor.
     *
     * @param string $id Ship identifier
     * @param string $name Ship name
     * @param string $type Ship type (transport, mining, auxiliary, carrier)
     * @param string $size Ship size (s, m, l, xl)
     * @param float $mass Ship base mass
     * @param float $cargo Ship cargo capacity
     * @param array<string> $engines List of compatible engine IDs
     * @param int $engineCount Number of engine slots
     * @param string $cargoType Cargo connection type
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float}|null $dragOriginal Real drag values from ShipDef
     * @param array{pitch: float, yaw: float, roll: float}|null $inertiaOriginal Real inertia values from ShipDef
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}}|null $jerkOriginal Real jerk values from ShipDef
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $size,
        public readonly float $mass,
        public readonly float $cargo,
        public readonly array $engines = [],
        public readonly int $engineCount = 0,
        public readonly string $cargoType = '',
        public readonly ?array $dragOriginal = null,
        public readonly ?array $inertiaOriginal = null,
        public readonly ?array $jerkOriginal = null
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### ClassRangeRequest

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Input contract for class-wide range calculations.

```php
class ClassRangeRequest
{
    /**
     * Constructor.
     *
     * @param string $shipType Ship type filter (transport, mining, auxiliary, carrier)
     * @param float $cargoMultiplier Cargo multiplier (2x, 4x, 8x, etc.)
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $dragReductionTiers Drag reduction tiers
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $jerkReductionTiers Jerk reduction tiers
     * @param float $inertiaImpactFactor Inertia impact factor config
     * @param bool $useEffectiveRatioCap Whether to cap effective ratio
     * @param float $dragReductionFactor Drag reduction factor config
     * @param float $accelerationResponsiveness Acceleration responsiveness config
     * @param string|null $engineId Optional engine ID for engine-dependent metrics
     */
    public function __construct(
        public readonly string $shipType,
        public readonly float $cargoMultiplier,
        public readonly array $dragReductionTiers,
        public readonly array $jerkReductionTiers,
        public readonly float $inertiaImpactFactor,
        public readonly bool $useEffectiveRatioCap,
        public readonly float $dragReductionFactor,
        public readonly float $accelerationResponsiveness,
        public readonly ?string $engineId = null
    );
    
    /**
     * Create from array (typically from JSON request).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self;
}
```

---

### RangeMetric

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Min/max/median range for a single metric.

```php
class RangeMetric
{
    /**
     * Constructor.
     *
     * @param float $min Minimum value across all ships
     * @param float $max Maximum value across all ships
     * @param float $median Median value across all ships
     * @param string $unit Unit of measurement (m/s, m/s², %, ratio)
     * @param string $label Human-readable label
     */
    public function __construct(
        public readonly float $min,
        public readonly float $max,
        public readonly float $median,
        public readonly string $unit,
        public readonly string $label
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### ShipMetricSummary

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Worst/best case ship identification with metrics.

```php
class ShipMetricSummary
{
    /**
     * Constructor.
     *
     * @param string $shipId Ship identifier
     * @param string $shipName Ship name
     * @param string $size Ship size (s, m, l, xl)
     * @param float $massRatio Mass ratio for this ship
     * @param array{original: float, adjusted: float}|null $topSpeed Top speed metrics (when engine selected)
     * @param array{original: float, adjusted: float}|null $acceleration Acceleration metrics (when engine selected)
     * @param float $dragChangePercent Forward drag percent change (most impactful axis)
     */
    public function __construct(
        public readonly string $shipId,
        public readonly string $shipName,
        public readonly string $size,
        public readonly float $massRatio,
        public readonly ?array $topSpeed,
        public readonly ?array $acceleration,
        public readonly float $dragChangePercent
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### ClassRangeResponse

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Output contract for class-wide range calculations with aggregated metrics.

```php
class ClassRangeResponse
{
    /**
     * Constructor.
     *
     * @param int $shipCount Number of ships included in calculation
     * @param array<string, RangeMetric> $metrics Map of metric name to range (massRatio, dragChange, inertiaChange, jerkChange, topSpeed, acceleration)
     * @param ShipMetricSummary $worstCase Worst-case ship (highest mass ratio)
     * @param ShipMetricSummary $bestCase Best-case ship (lowest mass ratio)
     */
    public function __construct(
        public readonly int $shipCount,
        public readonly array $metrics,
        public readonly ShipMetricSummary $worstCase,
        public readonly ShipMetricSummary $bestCase
    );
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

---

### ValidationResult

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs`

**Purpose:** Configuration validation result.

```php
class ValidationResult
{
    /**
     * Constructor.
     *
     * @param bool $isValid Whether the configuration is valid
     * @param array<string> $errors List of validation error messages
     */
    public function __construct(
        private bool $isValid,
        private array $errors = []
    );
    
    /**
     * Check if configuration is valid.
     *
     * @return bool
     */
    public function isValid(): bool;
    
    /**
     * Get list of validation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array;
    
    /**
     * Convert to array for JSON serialization.
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function toArray(): array;
}
```

---

## Backend Exceptions

### GUIException

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions`

**Extends:** `Mistralys\X4\Mods\CargoSizesMod\CargoSizeException`

**Purpose:** GUI-specific exceptions.

```php
class GUIException extends CargoSizeException
{
    /**
     * Error code constants.
     */
    public const int ERROR_UNHANDLED_SHIP_TYPE = 12001;
    public const int ERROR_INVALID_CONFIGURATION = 12002;
    public const int ERROR_FILE_ACCESS = 12003;
    
    /**
     * Create exception for invalid configuration.
     *
     * @param string $reason Error reason
     * @return self
     */
    public static function invalidConfiguration(string $reason): self;
    
    /**
     * Create exception for file access errors.
     *
     * @param string $path File path
     * @param string $operation Operation attempted (read/write)
     * @return self
     */
    public static function fileAccessError(string $path, string $operation): self;
}
```

---

## Frontend Services (API Client)

### API Client (api.ts)

**Module:** `src/services/api.ts`

**Purpose:** Axios-based API client for backend communication.

```typescript
/**
 * Physics calculation API methods.
 */
export const physicsApi: {
  /**
   * Calculate physics for a single configuration.
   */
  calculate(request: PhysicsConfig): Promise<PhysicsResponse>;
  
  /**
   * Calculate physics for multiple configurations in one request.
   */
  calculateBatch(requests: PhysicsConfig[]): Promise<PhysicsResponse[]>;
};

/**
 * Class-wide range calculation API methods.
 */
export const classRangeApi: {
  /**
   * Calculate class-wide metric ranges for all ships of a type.
   */
  calculate(request: ClassRangeRequest): Promise<ClassRangeResponse>;
};

/**
 * Ship and engine data API methods.
 */
export const shipsApi: {
  /**
   * Get all supported ship types.
   */
  getTypes(): Promise<ShipTypeInfo[]>;
  
  /**
   * Get ships filtered by type.
   */
  getShipsByType(type: string): Promise<ShipInfo[]>;
  
  /**
   * Get detailed information for a specific ship.
   */
  getDetails(shipId: string): Promise<ShipDetails>;
  
  /**
   * Get compatible engines for a specific ship.
   */
  getEnginesForShip(shipId: string): Promise<EngineDef[]>;
  
  /**
   * Get all available engines.
   */
  getAllEngines(): Promise<EngineDef[]>;
};

/**
 * Configuration API methods.
 */
export const configApi: {
  /**
   * Get current build configuration.
   */
  get(): Promise<BuildConfig>;
  
  /**
   * Save updated configuration.
   */
  update(config: BuildConfig): Promise<{ success: boolean; message: string }>;
  
  /**
   * Validate configuration without saving.
   */
  validate(config: BuildConfig): Promise<ValidationResult>;
};
```

---

## Frontend Hooks

### usePhysicsCalculation

**Module:** `src/hooks/usePhysicsCalculation.ts`

**Purpose:** Physics calculation hook with 300ms debouncing.

```typescript
interface UsePhysicsCalculationResult {
  result: PhysicsResponse | null;
  loading: boolean;
  error: string | null;
  calculate: (config: PhysicsConfig) => void;
  reset: () => void;
}

/**
 * Hook for performing physics calculations with 300ms debounce.
 */
export function usePhysicsCalculation(): UsePhysicsCalculationResult;
```

---

### useClassRange

**Module:** `src/hooks/useClassRange.ts`

**Purpose:** Class-wide range calculation hook with 500ms debouncing.

```typescript
interface UseClassRangeResult {
  result: ClassRangeResponse | null;
  loading: boolean;
  error: string | null;
  calculate: (request: ClassRangeRequest) => void;
  reset: () => void;
}

/**
 * Hook for performing class-wide range calculations with 500ms debounce.
 */
export function useClassRange(): UseClassRangeResult;
```

---

### useShipData

**Module:** `src/hooks/useShipData.ts`

**Purpose:** Ship data fetching and caching hook.

```typescript
interface UseShipDataResult {
  shipTypes: ShipTypeInfo[];
  ships: ShipInfo[];
  selectedShip: ShipDetails | null;
  engines: EngineDef[];
  loading: boolean;
  error: string | null;
  fetchShipsByType: (type: string) => Promise<void>;
  fetchShipDetails: (shipId: string) => Promise<void>;
  fetchEngines: (shipId?: string) => Promise<void>;
}

/**
 * Hook for fetching ship and engine data.
 */
export function useShipData(): UseShipDataResult;
```

---

### useConfig

**Module:** `src/hooks/useConfig.ts`

**Purpose:** Configuration management hook.

```typescript
interface UseConfigResult {
  config: BuildConfig | null;
  validation: ValidationResult | null;
  loading: boolean;
  error: string | null;
  loadConfig: () => Promise<void>;
  saveConfig: (config: BuildConfig) => Promise<void>;
  validateConfig: (config: BuildConfig) => Promise<ValidationResult>;
}

/**
 * Hook for managing build configuration.
 */
export function useConfig(): UseConfigResult;
```

---

## Frontend TypeScript Types

### Physics Types (physics.d.ts)

```typescript
/**
 * Tier definition for reductions.
 */
export interface Tier {
  maxMultiplier: number;
  reductionPercent: number;
}

/**
 * Physics configuration parameters (matching PhysicsRequest DTO).
 */
export interface PhysicsConfig {
  baseMass: number;
  originalCargo: number;
  adjustedCargo: number;
  cargoMultiplier: number;
  useEffectiveRatioCap: boolean;
  dragReductionFactor: number;
  inertiaImpactFactor: number;
  accelerationResponsiveness: number;
  dragReductionTiers: Tier[];
  jerkReductionTiers: Tier[];
  engineId?: string | null;
}

/**
 * Adjusted drag values for all axes.
 */
export interface AdjustedDrag {
  forward: number;
  forwardPercent: number;
  reverse: number;
  reversePercent: number;
  horizontal: number;
  horizontalPercent: number;
  vertical: number;
  verticalPercent: number;
  pitch: number;
  pitchPercent: number;
  yaw: number;
  yawPercent: number;
  roll: number;
  rollPercent: number;
}

/**
 * Adjusted inertia values.
 */
export interface AdjustedInertia {
  pitch: number;
  pitchPercent: number;
  yaw: number;
  yawPercent: number;
  roll: number;
  rollPercent: number;
}

/**
 * Adjusted jerk values.
 */
export interface AdjustedJerk {
  forward: {
    accel: number;
    accelPercent: number;
    decel: number;
    decelPercent: number;
  };
  boost: {
    accel: number;
    accelPercent: number;
    decel: number;
    decelPercent: number;
  };
  travel: {
    accel: number;
    accelPercent: number;
    decel: number;
    decelPercent: number;
  };
}

/**
 * Engine performance metrics.
 */
export interface EnginePerformance {
  engineId: string;
  thrustForward: number;
  originalTWR: number;
  adjustedTWR: number;
  twrReductionPercent: number;
  originalAcceleration: number;
  adjustedAcceleration: number;
}

/**
 * Complete physics calculation response (matching PhysicsResponse DTO).
 */
export interface PhysicsResponse {
  massRatio: number;
  effectiveRatio: number;
  originalFullMass: number;
  adjustedFullMass: number;
  massIncrease: number;
  drag: {
    original: Record<string, number>;
    adjusted: AdjustedDrag;
    percentChange: Record<string, number>;
  };
  inertia: {
    original: Record<string, number>;
    adjusted: AdjustedInertia;
    percentChange: Record<string, number>;
  };
  jerk: {
    original: Record<string, any>;
    adjusted: AdjustedJerk;
    percentChange: Record<string, any>;
  };
  enginePerformance?: EnginePerformance | null;
  activeTier: string;
}

/**
 * Engine definition.
 */
export interface EngineDef {
  id: string;
  name: string;
  thrustForward: number;
  thrustReverse?: number;
  thrustBoost?: number;
  thrustTravel?: number;
}
```

---

### Ship Types (ships.d.ts)

```typescript
/**
 * Ship type information.
 */
export interface ShipTypeInfo {
  type: string;
  label: string;
}

/**
 * Ship information (list view).
 */
export interface ShipInfo {
  id: string;
  name: string;
  size: string;
  mass: number;
  cargo: number;
}

/**
 * Detailed ship information (matching ShipDetails DTO).
 */
export interface ShipDetails {
  id: string;
  name: string;
  type: string;
  size: string;
  mass: number;
  cargo: number;
  engines: string[];
  engineCount?: number;
  cargoType?: string;
  dragOriginal?: {
    forward: number;
    reverse: number;
    horizontal: number;
    vertical: number;
    pitch: number;
    yaw: number;
    roll: number;
  };
  inertiaOriginal?: {
    pitch: number;
    yaw: number;
    roll: number;
  };
  jerkOriginal?: {
    forward: { accel: number; decel: number };
    boost: { accel: number; decel: number };
    travel: { accel: number; decel: number };
  };
}
```

---

### Physics Types - Absolute Metrics (physics.d.ts)

```typescript
/**
 * Physics configuration with optional shipId (matching PhysicsRequest DTO).
 */
export interface PhysicsConfig {
  baseMass: number;
  originalCargo: number;
  adjustedCargo: number;
  cargoMultiplier: number;
  useEffectiveRatioCap: boolean;
  dragReductionFactor: number;
  inertiaImpactFactor: number;
  accelerationResponsiveness: number;
  dragReductionTiers: Tier[];
  jerkReductionTiers: Tier[];
  engineId?: string | null;
  shipId?: string;
}

/**
 * Physics response with absolute metrics (matching PhysicsResponse DTO).
 */
export interface PhysicsResponse {
  massRatio: number;
  effectiveRatio: number;
  originalFullMass: number;
  adjustedFullMass: number;
  massIncrease: number;
  dragOriginal: DragValues;
  dragAdjusted: DragValues;
  dragPercentChange: DragValues;
  inertiaOriginal: InertiaValues;
  inertiaAdjusted: InertiaValues;
  inertiaPercentChange: InertiaValues;
  jerkOriginal: JerkValues;
  jerkAdjusted: JerkValues;
  jerkPercentChange: JerkValues;
  enginePerformance?: EnginePerformance | null;
  activeTier: string;
  topSpeed?: { original: number; adjusted: number } | null;
  acceleration?: { original: number; adjusted: number } | null;
}

/**
 * Engine performance with top speeds (matching EnginePerformance DTO).
 */
export interface EnginePerformance {
  engineId: string;
  thrustForward: number;
  originalTWR: number;
  adjustedTWR: number;
  twrReductionPercent: number;
  originalAcceleration: number;
  adjustedAcceleration: number;
  engineCount?: number;
  topSpeed?: number | null;
  topSpeedAdjusted?: number | null;
  topSpeedReverse?: number | null;
  topSpeedBoost?: number | null;
  topSpeedTravel?: number | null;
}
```

---

### Class Range Types (physics.d.ts)

```typescript
/**
 * Class-wide range calculation request (matching ClassRangeRequest DTO).
 */
export interface ClassRangeRequest {
  shipType: string;
  cargoMultiplier: number;
  dragReductionTiers: Tier[];
  jerkReductionTiers: Tier[];
  inertiaImpactFactor: number;
  useEffectiveRatioCap: boolean;
  dragReductionFactor: number;
  accelerationResponsiveness: number;
  engineId?: string | null;
}

/**
 * Min/max/median range for a metric (matching RangeMetric DTO).
 */
export interface RangeMetric {
  min: number;
  max: number;
  median: number;
  unit: string;
  label: string;
}

/**
 * Worst/best case ship summary (matching ShipMetricSummary DTO).
 */
export interface ShipMetricSummary {
  shipId: string;
  shipName: string;
  size: string;
  massRatio: number;
  topSpeed?: { original: number; adjusted: number } | null;
  acceleration?: { original: number; adjusted: number } | null;
  dragChangePercent: number;
}

/**
 * Class-wide range response (matching ClassRangeResponse DTO).
 */
export interface ClassRangeResponse {
  shipCount: number;
  metrics: Record<string, RangeMetric>;
  worstCase: ShipMetricSummary;
  bestCase: ShipMetricSummary;
}
```

---

### Config Types (config.d.ts)

```typescript
/**
 * Flight mechanics configuration (subset of build-config.json).
 */
export interface FlightMechanics {
  dragReductionFactor: number;
  steeringIncreaseFactor: number;
  inertiaIncreaseFactor: number;
  dragReductionTiers: Tier[];
  jerkReductionTiers: Tier[];
  inertiaImpactFactor: number;
  useEffectiveRatioCap: boolean;
  accelerationResponsiveness: number;
}

/**
 * Complete build configuration (matching build-config.json structure).
 */
export interface BuildConfig {
  'cargo-multipliers': number[];
  'flight-mechanics': FlightMechanics;
}

/**
 * Configuration validation result (matching ValidationResult DTO).
 */
export interface ValidationResult {
  valid: boolean;
  errors: string[];
}
```

---

## API Endpoints

### Physics Endpoints

| Method | Endpoint | Handler |
|--------|----------|---------|
| `POST` | `/api/calculate/physics` | `PhysicsEndpoint::calculate()` |
| `POST` | `/api/calculate/batch` | `PhysicsEndpoint::calculateBatch()` |
| `POST` | `/api/calculate/class-range` | `ClassRangeEndpoint::calculate()` |

### Ship Endpoints

| Method | Endpoint | Handler |
|--------|----------|---------|
| `GET` | `/api/ships/types` | `ShipsEndpoint::getTypes()` |
| `GET` | `/api/ships/{type}` | `ShipsEndpoint::getShipsByType()` |
| `GET` | `/api/ships/details/{id}` | `ShipsEndpoint::getDetails()` |
| `GET` | `/api/ships/{id}/engines` | `ShipsEndpoint::getEnginesForShip()` |
| `GET` | `/api/engines` | `ShipsEndpoint::getAllEngines()` |

### Config Endpoints

| Method | Endpoint | Handler |
|--------|----------|---------|
| `GET` | `/api/config` | `ConfigEndpoint::get()` |
| `POST` | `/api/config` | `ConfigEndpoint::update()` |
| `POST` | `/api/config/validate` | `ConfigEndpoint::validate()` |

---

**End of Public API Documentation**

