<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

abstract class BaseJerkMovement
{
    private float $acceleration;
    private float $deceleration;
    private float $ratio;

    public function __construct(float $acceleration, float $deceleration, float $ratio)
    {
        $this->acceleration = $acceleration;
        $this->deceleration = $deceleration;
        $this->ratio = $ratio;
    }

    public function getAcceleration(): float
    {
        return $this->acceleration;
    }

    public function getDeceleration(): float
    {
        return $this->deceleration;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }

    abstract public function getTagName() : string;
}

