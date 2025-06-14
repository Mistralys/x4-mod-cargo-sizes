<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class Inertia
{
    // generate constructor, fields and getters for:
    // pitch, yaw and roll.
    private float $pitch;
    private float $yaw;
    private float $roll;

    public function __construct(float $pitch, float $yaw, float $roll)
    {
        $this->pitch = $pitch;
        $this->yaw = $yaw;
        $this->roll = $roll;
    }
    public function getPitch(): float
    {
        return $this->pitch;
    }

    public function getYaw(): float
    {
        return $this->yaw;
    }

    public function getRoll(): float
    {
        return $this->roll;
    }

}