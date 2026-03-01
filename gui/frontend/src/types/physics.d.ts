/**
 * Physics calculation type definitions matching backend DTOs.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

/**
 * Physics configuration parameters (matching PhysicsRequest DTO).
 * Only acceleration-based parameters remain — drag/inertia/jerk/tier params removed.
 */
export interface PhysicsConfig {
  baseMass: number;
  originalCargo: number;
  adjustedCargo: number;
  cargoMultiplier: number;
  accelerationResponsiveness: number;
  engineId?: string | null;
  shipId?: string | null;
}

/**
 * Engine performance metrics.
 * Matches EnginePerformance DTO toArray() output.
 */
export interface EnginePerformance {
  engineId: string;
  thrustForward: number;
  originalTWR: number;
  adjustedTWR: number;
  twrReductionPercent: number;
  originalAcceleration: number;
  adjustedAcceleration: number;
  engineCount: number;
  topSpeed: number | null;
  topSpeedAdjusted: number | null;
  topSpeedReverse: number | null;
  topSpeedBoost: number | null;
  topSpeedTravel: number | null;
}

/**
 * Complete physics response (matching PhysicsResponse DTO).
 * Only acceleration-based metrics — drag/inertia/jerk removed.
 */
export interface PhysicsResponse {
  massRatio: number;
  originalFullMass: number;
  adjustedFullMass: number;
  massIncrease: number;
  originalCargo: number;
  adjustedCargo: number;
  accelerationScalingFactor: number;
  accelerationResponsiveness: number;
  enginePerformance?: EnginePerformance | null;
  topSpeed?: {
    original: number;
    adjusted: number;
  } | null;
  acceleration?: {
    original: number;
    adjusted: number;
  } | null;
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

/**
 * Range metric with min/max/median values.
 */
export interface RangeMetric {
  min: number;
  max: number;
  median: number;
  unit: string;
  label: string;
}

/**
 * Summary of a single ship's metrics for worst/best case identification.
 */
export interface ShipMetricSummary {
  shipId: string;
  shipName: string;
  size: string;
  massRatio: number;
  topSpeed?: {
    original: number;
    adjusted: number;
  } | null;
  acceleration?: {
    original: number;
    adjusted: number;
  } | null;
}

/**
 * Class-range calculation request (matching ClassRangeRequest DTO).
 * Only acceleration-based parameters — tier/drag/inertia params removed.
 */
export interface ClassRangeRequest {
  shipType: string;
  cargoMultiplier: number;
  accelerationResponsiveness: number;
  engineId?: string | null;
}

/**
 * Class-range calculation response.
 */
export interface ClassRangeResponse {
  shipCount: number;
  metrics: Record<string, RangeMetric>;
  worstCase: ShipMetricSummary;
  bestCase: ShipMetricSummary;
}