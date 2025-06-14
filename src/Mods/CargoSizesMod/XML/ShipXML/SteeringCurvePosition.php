<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class SteeringCurvePosition
{
    private string $position;
    private float $value;

    public function __construct(string $position, float $value)
    {
        $this->position = $position;
        $this->value = $value;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}
