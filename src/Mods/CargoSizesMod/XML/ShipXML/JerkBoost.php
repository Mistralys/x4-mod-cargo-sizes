<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class JerkBoost
{
    private float $acceleration;
    private float $ratio;

    public function __construct(float $acceleration, float $ratio)
    {
        $this->acceleration = $acceleration;
        $this->ratio = $ratio;
    }

    public function getAcceleration(): float
    {
        return $this->acceleration;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }
}

