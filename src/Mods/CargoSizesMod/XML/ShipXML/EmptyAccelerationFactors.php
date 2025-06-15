<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class EmptyAccelerationFactors extends AccelerationFactors
{
    public function __construct()
    {
        parent::__construct(1.0, 1.0, 1.0, 1.0);
    }
}
