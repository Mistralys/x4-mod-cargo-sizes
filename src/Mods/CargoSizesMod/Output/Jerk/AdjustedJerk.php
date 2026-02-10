<?php
/**
 * @package Output
 * @subpackage Jerk
 */

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use function Mistralys\X4\calcDecrease;

/**
 * Adjusted jerk values for ships with modified cargo capacity.
 *
 * CRITICAL FIX: Heavier ships have LOWER jerk (physics-correct).
 * Previous implementation incorrectly INCREASED jerk with mass.
 *
 * @method AdjustedJerkBoost getBoost()
 * @method AdjustedJerkForward getForward()
 * @method AdjustedJerkTravel getTravel()
 *
 * @package Output
 * @subpackage Jerk
 */
class AdjustedJerk extends Jerk implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Jerk $original;

    /**
     * Creates adjusted jerk values with tier-based reduction.
     *
     * @param Jerk $original Original jerk values from ship XML
     * @param float $reductionPercent Percentage to reduce jerk (0.0-1.0, e.g., 0.15 = 15% reduction)
     */
    public function __construct(Jerk $original, float $reductionPercent)
    {
        $this->original = $original;
        $this->setMultiplier($reductionPercent);

        // Apply reduction to strafe and angular jerk
        // calcDecrease(value, percent) = value - (value * percent)
        // Example: calcDecrease(100, 0.15) = 85 (15% reduction)
        parent::__construct(
            calcDecrease($original->getStrafe(), $reductionPercent),
            calcDecrease($original->getAngular(), $reductionPercent),
            new AdjustedJerkForward($original->getForward(), $reductionPercent),
            new AdjustedJerkBoost($original->getBoost(), $reductionPercent),
            new AdjustedJerkTravel($original->getTravel(), $reductionPercent)
        );

        $this->addValue('Strafe', $original->getStrafe(), $this->getStrafe());
        $this->addValue('Angular', $original->getAngular(), $this->getAngular());
    }

    /**
     * Gets the original jerk values before adjustment.
     *
     * @return Jerk Original jerk
     */
    public function getOriginal(): Jerk
    {
        return $this->original;
    }

    /**
     * Gets the reduction percentage applied.
     *
     * @return float Reduction percentage (0.0-1.0)
     */
    public function getReductionPercent(): float
    {
        return $this->getMultiplier();
    }

    public function isIncrease(): bool
    {
        return false; // Jerk DECREASES with mass (physics-correct)
    }

    public function getPrecision(): int
    {
        return 2;
    }
}
