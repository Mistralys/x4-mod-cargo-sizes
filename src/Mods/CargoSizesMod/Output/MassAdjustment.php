<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use testsuites\Traits\RenderableTests;

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

    public function getMass(): float
    {
        return $this->mass;
    }

    /**
     * The multiplier used to get from the original full load mass to the adjusted full load mass.
     * Used as base for additional calculations such as acceleration and drag.
     *
     * @return float
     */
    public function getMultiplier(): float
    {
        return $this->getOriginalFullLoadMass() / $this->getAdjustedFullLoadMass();
    }

    public function formatMultiplier() : string
    {
        return number_format($this->getMultiplier(), 2, '.', '');
    }

    /**
     * The total mass of the ship when its cargo is full, original unmodified value.
     * @return float
     */
    public function getOriginalFullLoadMass() : float
    {
        return $this->mass + $this->cargo;
    }

    /**
     * The total mass of the ship when its cargo is full, modified value.
     * @return float
     */
    public function getAdjustedFullLoadMass() : float
    {
        return $this->mass + $this->adjustedCargo;
    }
}
