<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use function Mistralys\X4\calcIncrease;

class AdjustedInertia extends Inertia implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Inertia $original;

    public function __construct(Inertia $original, float $multiplier)
    {
        $this->original = $original;
        $this->setMultiplier($multiplier);

        parent::__construct(
            calcIncrease($original->getPitch(), $multiplier),
            calcIncrease($original->getYaw(), $multiplier),
            calcIncrease($original->getRoll(), $multiplier)
        );

        $this->addValue('Inertia Pitch', $original->getPitch(), $this->getPitch());
        $this->addValue('Inertia Yaw', $original->getYaw(), $this->getYaw());
        $this->addValue('Inertia Roll', $original->getRoll(), $this->getRoll());
    }

    public function isIncrease(): bool
    {
        return true;
    }

    public function getPrecision(): int
    {
        return 3;
    }
}
