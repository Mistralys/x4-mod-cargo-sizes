<?php

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use function Mistralys\X4\calcIncrease;

/**
 * @method AdjustedJerkBoost getBoost()
 * @method AdjustedJerkForward getForward()
 * @method AdjustedJerkTravel getTravel()
 */
class AdjustedJerk extends Jerk implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Jerk $original;

    public function __construct(Jerk $original, float $multiplier)
    {
        $this->original = $original;
        $this->setMultiplier($multiplier);

        parent::__construct(
            calcIncrease($original->getStrafe(), $multiplier),
            calcIncrease($original->getAngular(), $multiplier),
            new AdjustedJerkForward($original->getForward(), $multiplier),
            new AdjustedJerkBoost($original->getBoost(), $multiplier),
            new AdjustedJerkTravel($original->getTravel(), $multiplier * 2)
        );

        $this->addValue('Strafe', $original->getStrafe(), $this->getStrafe());
        $this->addValue('Angular', $original->getAngular(), $this->getAngular());
    }

    public function isIncrease(): bool
    {
        return true;
    }

    public function getPrecision(): int
    {
        return 2;
    }
}
