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
use function Mistralys\X4\calcDecrease;

/**
 * Adjusted boost jerk values for ships with modified cargo capacity.
 *
 * CRITICAL FIX: Reduces jerk (physics-correct) instead of increasing it.
 *
 * @package Output
 * @subpackage Jerk
 */
class AdjustedJerkBoost extends JerkBoost implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkBoost $original;

    /**
     * Creates adjusted boost jerk values with tier-based reduction.
     *
     * @param JerkBoost $original Original boost jerk values
     * @param float $reductionPercent Percentage to reduce jerk (0.0-1.0)
     */
    public function __construct(JerkBoost $original, float $reductionPercent)
    {
        $this->original = $original;
        $this->setMultiplier($reductionPercent);

        parent::__construct(
            calcDecrease($original->getAcceleration(), $reductionPercent),
            $original->getRatio()
        );

        $this->addValue('Boost Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Boost Ratio', $original->getRatio(), $this->getRatio());
    }

    /**
     * Gets the original boost jerk values before adjustment.
     *
     * @return JerkBoost Original boost jerk
     */
    public function getOriginal(): JerkBoost
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
