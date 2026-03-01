<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use Mistralys\X4\X4Exception;

class CargoSizeException extends X4Exception
{
    public const ERROR_UNHANDLED_SHIP_TYPE = 178001;
    public const ERROR_MISSING_XML_TAG = 178002;
    public const ERROR_UNRECOGNIZED_SHIP_SIZE = 178003;
    public const ERROR_MISSING_RELATIVE_PATH = 178004;
    public const ERROR_MISSING_CHANGELOG = 178005;
    public const ERROR_CHANGELOG_PARSE = 178006;
    public const ERROR_FILE_WRITE = 178007;
    public const ERROR_INVALID_CONFIG = 178008;
    public const ERROR_INVALID_CALCULATOR_PARAMS = 178009;
}
