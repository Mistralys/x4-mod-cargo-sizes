<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class SteeringCurve
{
    private array $positions = array();

    public function addPosition(string $position, float $value): void
    {
        $this->positions[] = new SteeringCurvePosition($position, $value);
    }

    /**
     * Returns the positions of the steering curve.
     * @return SteeringCurvePosition[]
     */
    public function getPositions(): array
    {
        return $this->positions;
    }
}
