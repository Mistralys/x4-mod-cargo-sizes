# Public API - Signatures Only

> **Version:** 1.0  
> **Last Updated:** February 12, 2026  
> **Purpose:** Public method signatures and contracts (NO implementations)

---

## Overview

This document lists **all public properties, methods, and constructors** for the GUI codebase. **Implementation details are NOT included** - only signatures and contracts.

Use this as a quick reference to understand what methods exist without reading source code.

---

## Table of Contents

1. [Backend Services](#backend-services)
2. [Backend DTOs](#backend-dtos)
3. [Backend Exceptions](#backend-exceptions)
4. [Frontend Services (API Client)](#frontend-services-api-client)
5. [Frontend Hooks](#frontend-hooks)
6. [Frontend TypeScript Types](#frontend-typescript-types)

---

## Backend Services

### PhysicsService

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\GUI\Services`

**Purpose:** Physics calculation service wrapping PhysicsCalculator.

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
     */
    public function __construct(
        public float $baseMass,
        public float $originalCargo,
        public float $adjustedCargo,
        public float $cargoMultiplier,
        public bool $useEffectiveRatioCap,
        public float $dragReductionFactor,
        public float $inertiaImpactFactor,
        public float $accelerationResponsiveness,
        public array $dragReductionTiers,
        public array $jerkReductionTiers,
        public ?string $engineId = null
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
     */
    public function __construct(
        public float $massRatio,
        public float $effectiveRatio,
        public float $originalFullMass,
        public float $adjustedFullMass,
        public float $massIncrease,
        public array $dragOriginal,
        public array $dragAdjusted,
        public array $dragPercentChange,
        public array $inertiaOriginal,
        public array $inertiaAdjusted,
        public array $inertiaPercentChange,
        public array $jerkOriginal,
        public array $jerkAdjusted,
        public array $jerkPercentChange,
        public ?EnginePerformance $enginePerformance = null,
        public string $activeTier = ''
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
     */
    public function __construct(
        public string $engineId,
        public float $thrustForward,
        public float $originalTWR,
        public float $adjustedTWR,
        public float $twrReductionPercent,
        public float $originalAcceleration,
        public float $adjustedAcceleration
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
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $size,
        public float $mass,
        public float $cargo,
        public array $engines = []
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

