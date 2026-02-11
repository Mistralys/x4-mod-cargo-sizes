<?php
/**
 * @package Output
 * @subpackage Physics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\AccelerationFactors;

/**
 * Adjusted acceleration factors for ships with modified cargo capacity.
 *
 * RESEARCH-BACKED FORMULA: Scales acceleration factors proportionally with mass
 * to maintain ship responsiveness (time-to-speed).
 *
 * **Physics:**
 * - Velocity change: Δv ∝ (AccelerationFactor / Mass)
 * - When mass increases 4x, AccelerationFactor must increase 4x to maintain ratio
 * - Result: Same time-to-speed (responsiveness preserved)
 *
 * **Formula:** newAccel = original × massRatio × responsiveness
 *
 * **Example with responsiveness=1.0:**
 * - Original accel: 2.0, Mass: 1x → Ratio: 2.0/1.0 = 2.0
 * - New accel: 8.0, Mass: 4x → Ratio: 8.0/4.0 = 2.0 ✓ (maintained!)
 *
 * **Example with responsiveness=0.7 (heavier feel):**
 * - New accel: 5.6 (= 2.0 × 4.0 × 0.7)
 * - Ratio: 5.6/4.0 = 1.4 = 0.7 × original (30% less responsive)
 *
 * @package Output
 * @subpackage Physics
 */
class AdjustedAccelerationFactors extends AccelerationFactors implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private AccelerationFactors $original;
    private float $scalingFactor;

    /**
     * Creates adjusted acceleration factors with mass-proportional scaling.
     *
     * @param AccelerationFactors $original Original acceleration factors from ship XML
     * @param float $scalingFactor Pre-calculated scaling: massRatio × responsiveness
     */
    public function __construct(AccelerationFactors $original, float $scalingFactor)
    {
        $this->original = $original;
        $this->scalingFactor = $scalingFactor;
        $this->setMultiplier($scalingFactor);

        // Scale all acceleration components proportionally
        // This maintains the AccelFactor/Mass ratio → preserves responsiveness
        parent::__construct(
            $original->getForward() * $scalingFactor,
            $original->getReverse() * $scalingFactor,
            $original->getHorizontal() * $scalingFactor,
            $original->getVertical() * $scalingFactor,
        );

        $this->addValue('Acceleration Forward', $original->getForward(), $this->getForward());
        $this->addValue('Acceleration Reverse', $original->getReverse(), $this->getReverse());
        $this->addValue('Acceleration Horizontal', $original->getHorizontal(), $this->getHorizontal());
        $this->addValue('Acceleration Vertical', $original->getVertical(), $this->getVertical());
    }

    /**
     * Gets the original acceleration factors before adjustment.
     *
     * @return AccelerationFactors Original acceleration factors
     */
    public function getOriginal(): AccelerationFactors
    {
        return $this->original;
    }

    /**
     * Gets the scaling factor applied (massRatio × responsiveness).
     *
     * @return float Scaling factor
     */
    public function getScalingFactor(): float
    {
        return $this->scalingFactor;
    }

    public function isIncrease(): bool
    {
        return true; // Acceleration factors increase with mass
    }

    public function getPrecision(): int
    {
        return 2;
    }
}
