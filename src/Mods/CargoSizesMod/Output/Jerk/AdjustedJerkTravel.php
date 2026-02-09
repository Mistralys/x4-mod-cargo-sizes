<?php

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkTravel;
use function Mistralys\X4\calcIncrease;

class AdjustedJerkTravel extends JerkTravel implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkTravel $original;

    public function __construct(JerkTravel $original, float $multiplier)
    {
        $this->original = $original;
        $this->setMultiplier($multiplier);

        parent::__construct(
            calcIncrease($original->getAcceleration(), $multiplier),
            calcIncrease($original->getDeceleration(), $multiplier),
            $original->getRatio()
        );

        $this->addValue('Travel Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Travel Deceleration', $original->getDeceleration(), $this->getDeceleration());
        $this->addValue('Travel Ratio', $original->getRatio(), $this->getRatio());
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
