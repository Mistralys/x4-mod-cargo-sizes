<?php
/**
 * Physics calculation helper utilities.
 *
 * Shared trait providing common physics calculation methods
 * used across multiple services (PhysicsService, ClassRangeService).
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Utils
 * @since 1.2.0
 */
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Utils;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;

/**
 * Trait PhysicsCalculationHelper
 *
 * Provides utility methods for common physics calculations including
 * percentage change calculations and average change computations across
 * multiple axes (drag, inertia).
 *
 * This trait eliminates code duplication between PhysicsService and
 * ClassRangeService by centralizing shared calculation logic.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Utils
 * @since 1.2.0
 */
trait PhysicsCalculationHelper
{
    /**
     * Finds the appropriate tier for a cargo multiplier.
     *
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $tiers
     * @param float $multiplier
     * @return ReductionTier
     * @throws GUIException
     */
    protected function findTierForMultiplier(array $tiers, float $multiplier): ReductionTier
    {
        foreach ($tiers as $tierData) {
            $tier = ReductionTier::fromArray($tierData);
            if ($tier->appliesToMultiplier($multiplier)) {
                return $tier;
            }
        }

        throw new GUIException(
            sprintf('No tier found for cargo multiplier %.1fx', $multiplier),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }

    /**
     * Calculate percentage change between original and modified values.
     *
     * Returns the percentage difference as a positive (increase) or negative (decrease) value.
     * If the original value is zero, returns 0.0 to avoid division by zero.
     *
     * Formula: ((modified - original) / original) * 100
     *
     * @param float $original Original value
     * @param float $modified Modified value
     * @return float Percentage change (positive = increase, negative = decrease)
     * @since 1.2.0
     */
    private function calculatePercentChange(float $original, float $modified): float
    {
        if ($original === 0.0) {
            return 0.0;
        }
        return (($modified - $original) / $original) * 100.0;
    }

    /**
     * Calculate average drag change percentage across all axes.
     *
     * Computes percentage change for each drag axis (forward, reverse, horizontal,
     * vertical, pitch, yaw, roll) and returns the arithmetic mean.
     *
     * @param Drag $original Original drag values
     * @param AdjustedDrag $adjusted Adjusted drag values after modifications
     * @return float Average percentage change across all drag axes
     * @since 1.2.0
     */
    private function calculateAverageDragChange(Drag $original, AdjustedDrag $adjusted): float
    {
        $changes = [
            $this->calculatePercentChange($original->getForward(), $adjusted->getForward()),
            $this->calculatePercentChange($original->getReverse(), $adjusted->getReverse()),
            $this->calculatePercentChange($original->getHorizontal(), $adjusted->getHorizontal()),
            $this->calculatePercentChange($original->getVertical(), $adjusted->getVertical()),
            $this->calculatePercentChange($original->getPitch(), $adjusted->getPitch()),
            $this->calculatePercentChange($original->getYaw(), $adjusted->getYaw()),
            $this->calculatePercentChange($original->getRoll(), $adjusted->getRoll())
        ];

        return array_sum($changes) / count($changes);
    }

    /**
     * Calculate average inertia change percentage across all axes.
     *
     * Computes percentage change for each inertia axis (pitch, yaw, roll)
     * and returns the arithmetic mean.
     *
     * @param Inertia $original Original inertia values
     * @param AdjustedInertia $adjusted Adjusted inertia values after modifications
     * @return float Average percentage change across all inertia axes
     * @since 1.2.0
     */
    private function calculateAverageInertiaChange(Inertia $original, AdjustedInertia $adjusted): float
    {
        $changes = [
            $this->calculatePercentChange($original->getPitch(), $adjusted->getPitch()),
            $this->calculatePercentChange($original->getYaw(), $adjusted->getYaw()),
            $this->calculatePercentChange($original->getRoll(), $adjusted->getRoll())
        ];

        return array_sum($changes) / count($changes);
    }
}
