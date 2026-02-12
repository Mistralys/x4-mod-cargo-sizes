/**
 * Build configuration type definitions matching backend DTOs.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { Tier } from './physics';

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
