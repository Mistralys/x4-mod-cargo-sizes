<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\AccelerationFactors;
use function Mistralys\X4\calcIncrease;

class AdjustedAccelerationFactors extends AccelerationFactors implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private ?AccelerationFactors $original;
    private float $massMultiplier;

    public function __construct(AccelerationFactors $original, float $massMultiplier)
    {
        $this->original = $original;
        $this->setMultiplier($massMultiplier);

        parent::__construct(
            calcIncrease($original->getForward(), $massMultiplier),
            calcIncrease($original->getReverse(), $massMultiplier),
            calcIncrease($original->getHorizontal(), $massMultiplier),
            calcIncrease($original->getVertical(), $massMultiplier),
        );

        $this->addValue('Acceleration Forward', $original->getForward(), $this->getForward());
        $this->addValue('Acceleration Reverse', $original->getReverse(), $this->getReverse());
        $this->addValue('Acceleration Horizontal', $original->getHorizontal(), $this->getHorizontal());
        $this->addValue('Acceleration Vertical', $original->getVertical(), $this->getVertical());
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
