<?php
/**
 * @package Output
 * @subpackage Physics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use function Mistralys\X4\calcIncrease;

/**
 * Adjusted inertia values for ships with modified cargo capacity.
 *
 * Heavier ships have higher inertia (turn more slowly).
 * Uses dampening factor to balance realism vs playability.
 *
 * **Formula:** newInertia = original × (1.0 + (massRatio - 1.0) × impactFactor)
 *
 * **Example:**
 * - Mass ratio: 2.82x (ship 182% heavier)
 * - Impact factor: 0.5 (dampening)
 * - Mass increase: 2.82 - 1.0 = 1.82
 * - Dampened increase: 1.82 × 0.5 = 0.91
 * - New inertia: original × 1.91 (91% increase, not 182%)
 *
 * @package Output
 * @subpackage Physics
 */
class AdjustedInertia extends Inertia implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Inertia $original;

    /**
     * Creates adjusted inertia with dampened mass-based increase.
     *
     * @param Inertia $original Original inertia values from ship XML
     * @param float $dampenedIncrease Pre-calculated dampened increase: (massRatio - 1.0) × impactFactor
     */
    public function __construct(Inertia $original, float $dampenedIncrease)
    {
        $this->original = $original;
        $this->setMultiplier($dampenedIncrease);

        // Apply dampened increase to all inertia components
        // calcIncrease(value, factor) = value × (1 + factor)
        parent::__construct(
            calcIncrease($original->getPitch(), $dampenedIncrease),
            calcIncrease($original->getYaw(), $dampenedIncrease),
            calcIncrease($original->getRoll(), $dampenedIncrease)
        );

        $this->addValue('Inertia Pitch', $original->getPitch(), $this->getPitch());
        $this->addValue('Inertia Yaw', $original->getYaw(), $this->getYaw());
        $this->addValue('Inertia Roll', $original->getRoll(), $this->getRoll());
    }

    /**
     * Gets the original inertia values before adjustment.
     *
     * @return Inertia Original inertia
     */
    public function getOriginal(): Inertia
    {
        return $this->original;
    }

    /**
     * Gets the dampened increase factor applied.
     *
     * @return float Dampened increase factor
     */
    public function getDampenedIncrease(): float
    {
        return $this->getMultiplier();
    }

    public function isIncrease(): bool
    {
        return true; // Inertia increases with mass
    }

    public function getPrecision(): int
    {
        return 3;
    }
}
