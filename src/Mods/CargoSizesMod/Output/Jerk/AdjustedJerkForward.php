<?php
/**
 * @package Output
 * @subpackage Jerk
 */

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkForward;
use function Mistralys\X4\calcDecrease;

/**
 * Adjusted forward jerk values for ships with modified cargo capacity.
 *
 * CRITICAL FIX: Reduces jerk (physics-correct) instead of increasing it.
 *
 * @package Output
 * @subpackage Jerk
 */
class AdjustedJerkForward extends JerkForward implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkForward $original;

    /**
     * Creates adjusted forward jerk values with tier-based reduction.
     *
     * @param JerkForward $original Original forward jerk values
     * @param float $reductionPercent Percentage to reduce jerk (0.0-1.0)
     */
    public function __construct(JerkForward $original, float $reductionPercent)
    {
        $this->original = $original;
        $this->setMultiplier($reductionPercent);

        parent::__construct(
            calcDecrease($original->getAcceleration(), $reductionPercent),
            calcDecrease($original->getDeceleration(), $reductionPercent),
            $original->getRatio()
        );

        $this->addValue('Forward Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Forward Deceleration', $original->getDeceleration(), $this->getDeceleration());
        $this->addValue('Forward Ratio', $original->getRatio(), $this->getRatio());
    }

    /**
     * Gets the original forward jerk values before adjustment.
     *
     * @return JerkForward Original forward jerk
     */
    public function getOriginal(): JerkForward
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
