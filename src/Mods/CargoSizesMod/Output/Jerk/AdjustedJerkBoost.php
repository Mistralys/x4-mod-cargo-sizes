<?php

declare(strict_types=1);

namespace  Mistralys\X4\Mods\CargoSizesMod\Output\Jerk;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesInterface;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedValuesTrait;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use function Mistralys\X4\calcIncrease;

class AdjustedJerkBoost extends JerkBoost implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private JerkBoost $original;

    public function __construct(JerkBoost $original, float $multiplier)
    {
        $this->original = $original;
        $this->setMultiplier($multiplier);

        parent::__construct(
            calcIncrease($original->getAcceleration(), $multiplier),
            $original->getRatio()
        );

        $this->addValue('Boost Acceleration', $original->getAcceleration(), $this->getAcceleration());
        $this->addValue('Boost Ratio', $original->getRatio(), $this->getRatio());
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
