<?php

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkForward;
use function Mistralys\X4\calcIncrease;

class AdjustedJerkForward extends JerkForward implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkForward $original;

    public function __construct(JerkForward $original, float $multiplier)
    {
        $this->original = $original;
        $this->setMultiplier($multiplier);

        parent::__construct(
            calcIncrease($original->getAcceleration(), $multiplier),
            calcIncrease($original->getDeceleration(), $multiplier),
            $original->getRatio()
        );

        $this->addValue('Forward Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Forward Deceleration', $original->getDeceleration(), $this->getDeceleration());
        $this->addValue('Forward Ratio', $original->getRatio(), $this->getRatio());
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
