/**
 * Build configuration type definitions matching backend DTOs.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

/**
 * Flight mechanics configuration (subset of build-config.json).
 * Only accelerationResponsiveness remains — drag/inertia/jerk/tier params removed.
 */
export interface FlightMechanics {
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
