<?php
/**
 * @package Output
 * @subpackage Physics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use function Mistralys\X4\calcDecrease;

/**
 * Adjusted drag values for ships with modified cargo capacity.
 *
 * Applies tier-based drag reduction to compensate for fixed engine thrust.
 * Reducing drag increases top speed: v_max = Thrust / DragCoefficient
 *
 * @package Output
 * @subpackage Physics
 */
class AdjustedDrag extends Drag implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Drag $original;

    /**
     * Creates adjusted drag values with tier-based reduction.
     *
     * @param Drag $drag Original drag values from ship XML
     * @param float $reductionPercent Percentage to reduce drag (0.0-1.0, e.g., 0.30 = 30% reduction)
     */
    public function __construct(Drag $drag, float $reductionPercent)
    {
        $this->original = $drag;
        $this->setMultiplier($reductionPercent);

        // Apply reduction to all 7 drag components
        // calcDecrease(value, percent) = value - (value * percent)
        // Example: calcDecrease(100, 0.30) = 70 (30% reduction)
        parent::__construct(
            calcDecrease($drag->getForward(), $reductionPercent),
            calcDecrease($drag->getReverse(), $reductionPercent),
            calcDecrease($drag->getHorizontal(), $reductionPercent),
            calcDecrease($drag->getVertical(), $reductionPercent),
            calcDecrease($drag->getPitch(), $reductionPercent),
            calcDecrease($drag->getYaw(), $reductionPercent),
            calcDecrease($drag->getRoll(), $reductionPercent),
        );

        // Track changes for XML comments
        $this->addValue('Drag Forward', $drag->getForward(), $this->getForward());
        $this->addValue('Drag Reverse', $drag->getReverse(), $this->getReverse());
        $this->addValue('Drag Horizontal', $drag->getHorizontal(), $this->getHorizontal());
        $this->addValue('Drag Vertical', $drag->getVertical(), $this->getVertical());
        $this->addValue('Drag Pitch', $drag->getPitch(), $this->getPitch());
        $this->addValue('Drag Yaw', $drag->getYaw(), $this->getYaw());
        $this->addValue('Drag Roll', $drag->getRoll(), $this->getRoll());
    }

    /**
     * Gets the original drag values before adjustment.
     *
     * @return Drag Original drag
     */
    public function getOriginal(): Drag
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
        return false;
    }

    public function getPrecision(): int
    {
        return 3;
    }
}

