<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class Drag
{
    private float $forward;
    private float $reverse;
    private int $horizontal;
    private int $vertical;
    private int $pitch;
    private int $yaw;
    private float $roll;

    public function __construct(float $forward, float $reverse, int $horizontal, int $vertical, int $pitch, int $yaw, float $roll)
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

    public function getHorizontal(): int
    {
        return $this->horizontal;
    }

    public function getVertical(): int
    {
        return $this->vertical;
    }

    public function getPitch(): int
    {
        return $this->pitch;
    }

    public function getYaw(): int
    {
        return $this->yaw;
    }

    public function getRoll(): float
    {
        return $this->roll;
    }
}
