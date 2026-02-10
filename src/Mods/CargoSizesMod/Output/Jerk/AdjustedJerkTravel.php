<?php
/**
 * @package Output
 * @subpackage Jerk
 */

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkTravel;
use function Mistralys\X4\calcDecrease;

/**
 * Adjusted travel mode jerk values for ships with modified cargo capacity.
 *
 * CRITICAL FIX: Reduces jerk (physics-correct) instead of increasing it.
 * Removed arbitrary 2x travel mode penalty - uses same reduction as regular jerk.
 *
 * @package Output
 * @subpackage Jerk
 */
class AdjustedJerkTravel extends JerkTravel implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkTravel $original;

    /**
     * Creates adjusted travel jerk values with tier-based reduction.
     *
     * @param JerkTravel $original Original travel jerk values
     * @param float $reductionPercent Percentage to reduce jerk (0.0-1.0)
     */
    public function __construct(JerkTravel $original, float $reductionPercent)
    {
        $this->original = $original;
        $this->setMultiplier($reductionPercent);

        parent::__construct(
            calcDecrease($original->getAcceleration(), $reductionPercent),
            calcDecrease($original->getDeceleration(), $reductionPercent),
            $original->getRatio()
        );

        $this->addValue('Travel Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Travel Deceleration', $original->getDeceleration(), $this->getDeceleration());
        $this->addValue('Travel Ratio', $original->getRatio(), $this->getRatio());
    }

    /**
     * Gets the original travel jerk values before adjustment.
     *
     * @return JerkTravel Original travel jerk
     */
    public function getOriginal(): JerkTravel
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

