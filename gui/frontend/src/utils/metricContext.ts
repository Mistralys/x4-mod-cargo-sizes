/**
 * Metric Context Utility - Provides contextual phrases for physics metrics.
 *
 * Returns brief, intuitive descriptions that place absolute metric values
 * within the game's performance spectrum (e.g., "typical freighter speed").
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

// ============================================================================
// Speed Thresholds (m/s)
// ============================================================================
const SPEED_VERY_SLOW = 100;
const SPEED_TYPICAL_FREIGHTER = 200;
const SPEED_CORVETTE = 350;
const SPEED_FAST_FIGHTER = 500;

// ============================================================================
// Acceleration Thresholds (m/s²)
// ============================================================================
const ACCEL_SLUGGISH = 5;
const ACCEL_STANDARD = 20;
const ACCEL_NIMBLE = 50;

// ============================================================================
// Mass Ratio Thresholds
// ============================================================================
const MASS_RATIO_MINIMAL = 1.5;
const MASS_RATIO_NOTICEABLE = 3.0;
const MASS_RATIO_SIGNIFICANT = 10.0;

// ============================================================================
// Context Functions
// ============================================================================

/**
 * Returns contextual phrase for top speed value.
 *
 * @param speedMs - Top speed in m/s
 * @param _shipSize - Ship size class (S/M/L/XL) - currently unused but available for future refinement
 * @returns Brief descriptive phrase
 */
export function getSpeedContext(speedMs: number, _shipSize: string): string {
  if (speedMs < SPEED_VERY_SLOW) {
    return 'very slow — heavy hauler';
  }
  if (speedMs < SPEED_TYPICAL_FREIGHTER) {
    return 'typical freighter speed';
  }
  if (speedMs < SPEED_CORVETTE) {
    return 'corvette range';
  }
  if (speedMs < SPEED_FAST_FIGHTER) {
    return 'fast fighter territory';
  }
  return 'extremely fast — possibly unbalanced';
}

/**
 * Returns contextual phrase for acceleration value.
 *
 * @param accelMs2 - Acceleration in m/s²
 * @param _shipSize - Ship size class (S/M/L/XL) - currently unused but available for future refinement
 * @returns Brief descriptive phrase
 */
export function getAccelerationContext(accelMs2: number, _shipSize: string): string {
  if (accelMs2 < ACCEL_SLUGGISH) {
    return 'sluggish response';
  }
  if (accelMs2 < ACCEL_STANDARD) {
    return 'standard acceleration';
  }
  if (accelMs2 < ACCEL_NIMBLE) {
    return 'nimble';
  }
  return 'fighter-like responsiveness';
}

/**
 * Returns contextual phrase for mass ratio value.
 *
 * @param ratio - Mass ratio (adjusted mass / original mass)
 * @returns Brief descriptive phrase
 */
export function getMassRatioContext(ratio: number): string {
  if (ratio < MASS_RATIO_MINIMAL) {
    return 'minimal mass increase';
  }
  if (ratio < MASS_RATIO_NOTICEABLE) {
    return 'noticeable but manageable';
  }
  if (ratio < MASS_RATIO_SIGNIFICANT) {
    return 'significant — will affect handling';
  }
  return 'extreme — may feel unresponsive';
}
