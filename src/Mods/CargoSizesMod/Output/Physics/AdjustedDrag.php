<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use function Mistralys\X4\calcDecrease;

class AdjustedDrag extends Drag implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;

    private Drag $original;

    public function __construct(Drag $drag, float $reductionMultiplier)
    {
        $this->original = $drag;
        $this->setMultiplier($reductionMultiplier);

        parent::__construct(
            calcDecrease($drag->getForward(), $reductionMultiplier),
            calcDecrease($drag->getReverse(), $reductionMultiplier),
            calcDecrease($drag->getHorizontal(), $reductionMultiplier),
            calcDecrease($drag->getVertical(), $reductionMultiplier),
            calcDecrease($drag->getPitch(), $reductionMultiplier),
            calcDecrease($drag->getYaw(), $reductionMultiplier),
            calcDecrease($drag->getRoll(), $reductionMultiplier),
        );

        $this->addValue('Drag Forward', $drag->getForward(), $this->getForward());
        $this->addValue('Drag Reverse', $drag->getReverse(), $this->getReverse());
        $this->addValue('Drag Horizontal', $drag->getHorizontal(), $this->getHorizontal());
        $this->addValue('Drag Vertical', $drag->getVertical(), $this->getVertical());
        $this->addValue('Drag Pitch', $drag->getPitch(), $this->getPitch());
        $this->addValue('Drag Yaw', $drag->getYaw(), $this->getYaw());
        $this->addValue('Drag Roll', $drag->getRoll(), $this->getRoll());
    }

    public function isIncrease(): bool
    {
        return false;
    }

    public function getPrecision(): int
    {
        return 3;
    }
}
