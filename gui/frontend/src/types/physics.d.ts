/**
 * Physics calculation type definitions matching backend DTOs.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

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
  shipId?: string | null;
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
 * Jerk values for thrust modes that support both acceleration and deceleration (forward, travel).
 */
export interface JerkValues {
  accel: number;
  decel: number;
}

/**
 * Jerk values for boost mode (acceleration only, no deceleration).
 */
export interface JerkBoostValues {
  accel: number;
}

/**
 * Adjusted jerk values for all thrust modes.
 * Note: boost only has acceleration, forward and travel have both accel and decel.
 */
export interface AdjustedJerk {
  forward: JerkValues;
  boost: JerkBoostValues;
  travel: JerkValues;
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
 */
export interface PhysicsResponse {
  massRatio: number;
  effectiveRatio: number;
  originalFullMass: number;
  adjustedFullMass: number;
  massIncrease: number;
  originalCargo: number;
  adjustedCargo: number;
  drag: {
    original: Record<string, number>;
    adjusted: Record<string, number>;
    percentChange: Record<string, number>;
  };
  inertia: {
    original: Record<string, number>;
    adjusted: Record<string, number>;
    percentChange: Record<string, number>;
  };
  jerk: {
    original: Record<string, Record<string, number>>;
    adjusted: Record<string, Record<string, number>>;
    percentChange: Record<string, Record<string, number>>;
  };
  enginePerformance?: EnginePerformance | null;
  activeTier: string;
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
  dragChangePercent: number;
}

/**
 * Class-range calculation request.
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
 * Class-range calculation response.
 */
export interface ClassRangeResponse {
  shipCount: number;
  metrics: Record<string, RangeMetric>;
  worstCase: ShipMetricSummary;
  bestCase: ShipMetricSummary;
}
export interface EngineDef {
  id: string;
  name: string;
  thrustForward: number;
  thrustReverse?: number;
  thrustBoost?: number;
  thrustTravel?: number;
}
