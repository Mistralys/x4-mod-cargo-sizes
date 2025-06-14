<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class JerkTravel extends BaseJerkMovement
{
    public function getTagName(): string
    {
        return 'forward_travel';
    }
}

