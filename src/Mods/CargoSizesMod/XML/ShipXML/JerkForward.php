<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class JerkForward extends BaseJerkMovement
{
    public function getTagName(): string
    {
        return 'forward';
    }
}

