<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use testsuites\Traits\RenderableTests;

/**
 * Mass adjustment calculator for ships with modified cargo capacity.
 *
 * Provides both legacy (backwards ratio) and physics-correct (forward ratio) calculations.
 */
class MassAdjustment
{
    private float $mass;
    private int $cargo;
    private int $adjustedCargo;

    public function __construct(float $mass, int $cargo, int $adjustedCargo)
    {
        $this->mass = $mass;
        $this->cargo = $cargo;
        $this->adjustedCargo = $adjustedCargo;
    }

    /**
     * Gets the base ship mass (without cargo).
     *
     * @return float Base mass in kg
     */
    public function getMass(): float
    {
        return $this->mass;
    }

    /**
     * Gets the mass ratio (adjustedFullMass / originalFullMass).
     *
     * This is the PHYSICS-CORRECT ratio where values > 1.0 indicate increased mass.
     * Example: 2.0 means the ship is twice as heavy when fully loaded.
     *
     * @return float Mass ratio (>= 1.0)
     */
    public function getMassRatio(): float
    {
        $originalMass = $this->getOriginalFullLoadMass();
        if ($originalMass <= 0) {
            return 1.0;
        }
        return $this->getAdjustedFullLoadMass() / $originalMass;
    }

    /**
     * Gets the inverse mass ratio (1.0 / massRatio).
     *
     * This is the same as the legacy getMultiplier() method.
     * Used for calculations where higher mass should reduce a value (e.g., jerk).
     *
     * @return float Inverse mass ratio (<= 1.0)
     */
    public function getInverseMassRatio(): float
    {
        return 1.0 / $this->getMassRatio();
    }

    /**
     * Gets the squared mass ratio (massRatio²).
     *
     * Used for squared drag reduction calculations.
     *
     * @return float Mass ratio squared
     */
    public function getMassRatioSquared(): float
    {
        $ratio = $this->getMassRatio();
        return $ratio * $ratio;
    }

    /**
     * Gets the absolute mass increase (adjustedFullMass - originalFullMass).
     *
     * @return float Mass increase in kg
     */
    public function getMassIncrease(): float
    {
        return $this->getAdjustedFullLoadMass() - $this->getOriginalFullLoadMass();
    }

    /**
     * Gets the mass increase as a percentage.
     *
     * @return float Mass increase percentage (0.0 = no change, 100.0 = doubled)
     */
    public function getMassIncreasePercent(): float
    {
        return ($this->getMassRatio() - 1.0) * 100.0;
    }

    /**
     * The multiplier used to get from the original full load mass to the adjusted full load mass.
     * Used as base for additional calculations such as acceleration and drag.
     *
     * @deprecated Use getMassRatio() for physics-correct calculations (>1.0 when mass increases)
     *             or getInverseMassRatio() for explicit inverse calculations (<1.0 when mass increases)
     * @return float Backwards ratio (< 1.0 when mass increases)
     */
    public function getMultiplier(): float
    {
        return $this->getOriginalFullLoadMass() / $this->getAdjustedFullLoadMass();
    }

    /**
     * Formats the multiplier for display.
     *
     * @deprecated Use formatMassRatio() instead
     * @return string Formatted backwards ratio
     */
    public function formatMultiplier() : string
    {
        return number_format($this->getMultiplier(), 2, '.', '');
    }

    /**
     * Formats the mass ratio for display.
     *
     * @param int $decimals Number of decimal places
     * @return string Formatted ratio (e.g., "2.45")
     */
    public function formatMassRatio(int $decimals = 2): string
    {
        return number_format($this->getMassRatio(), $decimals, '.', '');
    }

    /**
     * The total mass of the ship when its cargo is full, original unmodified value.
     * @return float Original full load mass in kg
     */
    public function getOriginalFullLoadMass() : float
    {
        return $this->mass + $this->cargo;
    }

    /**
     * The total mass of the ship when its cargo is full, modified value.
     * @return float Adjusted full load mass in kg
     */
    public function getAdjustedFullLoadMass() : float
    {
        return $this->mass + $this->adjustedCargo;
    }
}
