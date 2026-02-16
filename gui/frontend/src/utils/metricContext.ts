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
 * @param shipSize - Ship size class (XS/S/M/L/XL) - reserved for future ship-size-specific thresholds
 * @returns Brief descriptive phrase
 * 
 * TODO: Implement ship-size-specific thresholds when product requirements are clarified.
 * Example: "fast for L-class" (>150 m/s) vs "fast for S-class" (>400 m/s)
 * Large ships should feel "fast" at lower absolute speeds than small ships.
 */
export function getSpeedContext(speedMs: number, shipSize: string): string {
  // Reserved for future ship-size-specific thresholds
  void shipSize;
  
  // TODO: Use shipSize parameter for size-specific context phrases
  /*
  switch (shipSize.toUpperCase()) {
    case 'XS':
    case 'S':
      // Small ships: Higher speed thresholds (fighters, scouts)
      // if (speedMs < 200) return 'very slow — heavy hauler';
      // if (speedMs < 400) return 'typical freighter speed';
      // if (speedMs < 600) return 'corvette range';
      // if (speedMs < 800) return 'fast fighter territory';
      // return 'extremely fast — possibly unbalanced';
      break;
    case 'M':
      // Medium ships: Moderate thresholds (frigates, corvettes)
      break;
    case 'L':
    case 'XL':
      // Large ships: Lower speed thresholds (carriers, stations)
      // if (speedMs < 50) return 'very slow — heavy hauler';
      // if (speedMs < 100) return 'typical freighter speed';
      // if (speedMs < 200) return 'corvette range';
      // if (speedMs < 300) return 'fast fighter territory';
      // return 'extremely fast — possibly unbalanced';
      break;
  }
  */
  
  // Current: Generic size-agnostic thresholds
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
 * @param shipSize - Ship size class (XS/S/M/L/XL) - reserved for future ship-size-specific thresholds
 * @returns Brief descriptive phrase
 * 
 * TODO: Implement ship-size-specific thresholds when product requirements are clarified.
 * Example: "responsive for XL-class" (>15 m/s²) vs "responsive for S-class" (>60 m/s²)
 * Large ships should feel "responsive" at lower absolute acceleration than small ships.
 */
export function getAccelerationContext(accelMs2: number, shipSize: string): string {
  // Reserved for future ship-size-specific thresholds
  void shipSize;
  
  // TODO: Use shipSize parameter for size-specific context phrases
  /*
  switch (shipSize.toUpperCase()) {
    case 'XS':
    case 'S':
      // Small ships: Higher acceleration thresholds (fighters, scouts)
      // if (accelMs2 < 20) return 'sluggish response';
      // if (accelMs2 < 60) return 'standard acceleration';
      // if (accelMs2 < 100) return 'nimble';
      // return 'fighter-like responsiveness';
      break;
    case 'M':
      // Medium ships: Moderate thresholds (frigates, corvettes)
      break;
    case 'L':
    case 'XL':
      // Large ships: Lower acceleration thresholds (carriers, stations)
      // if (accelMs2 < 2) return 'sluggish response';
      // if (accelMs2 < 10) return 'standard acceleration';
      // if (accelMs2 < 25) return 'nimble';
      // return 'fighter-like responsiveness';
      break;
  }
  */
  
  // Current: Generic size-agnostic thresholds
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
