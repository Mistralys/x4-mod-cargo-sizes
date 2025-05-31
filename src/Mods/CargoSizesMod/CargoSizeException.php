<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use Mistralys\X4\X4Exception;

class CargoSizeException extends X4Exception
{
    public const ERROR_UNHANDLED_SHIP_TYPE = 178001;
}
