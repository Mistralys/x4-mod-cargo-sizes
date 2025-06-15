<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class Drag
{
    private float $forward;
    private float $reverse;
    private float $horizontal;
    private float $vertical;
    private float $pitch;
    private float $yaw;
    private float $roll;

    public function __construct(float $forward, float $reverse, float $horizontal, float $vertical, float $pitch, float $yaw, float $roll)
    {
        $this->forward = $forward;
        $this->reverse = $reverse;
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
        $this->pitch = $pitch;
        $this->yaw = $yaw;
        $this->roll = $roll;
    }

    public function getForward(): float
    {
        return $this->forward;
    }

    public function formatForward() : string
    {
        return number_format($this->getForward(), 3, '.', '');
    }

    public function getReverse(): float
    {
        return $this->reverse;
    }

    public function getHorizontal(): float
    {
        return $this->horizontal;
    }

    public function getVertical(): float
    {
        return $this->vertical;
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
