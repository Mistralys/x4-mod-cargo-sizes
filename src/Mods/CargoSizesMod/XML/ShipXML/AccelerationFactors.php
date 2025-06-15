<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class AccelerationFactors
{
    private float $forward;
    private float $reverse;
    private float $horizontal;
    private float $vertical;

    public function __construct(float $forward, float $reverse, float $horizontal, float $vertical)
    {
        $this->forward = $forward;
        $this->reverse = $reverse;
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
    }

    public function getForward(): float
    {
        return $this->forward;
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
}