<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedAccelerationFactors;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use function Mistralys\X4\dec2;
use function Mistralys\X4\dec3;

class PhysicsOverrideDef extends TagOverrideDef
{
    private AdjustedInertia $inertia;
    private AdjustedDrag $drag;
    private AdjustedAccelerationFactors $accelerationFactors;
    private float $mass;

    public function __construct(string $macroName, float $mass, AdjustedInertia $inertia, AdjustedDrag $drag, AdjustedAccelerationFactors $accelerationFactors)
    {
        parent::__construct($macroName);

        $this
            ->setMacroPath('properties/physics')
            ->setTagName('physics')
            ->setComment('NOTE: Overriding the whole physics section for reliability.')
            ->addComments($inertia->getComments())
            ->addComments($drag->getComments())
            ->addComments($accelerationFactors->getComments());

        $this->mass = $mass;
        $this->inertia = $inertia;
        $this->drag = $drag;
        $this->accelerationFactors = $accelerationFactors;
    }

    private const TAG_TEMPLATE = <<<'XML'
        <physics mass="$MASS">
            <inertia pitch="$PITCH" yaw="$YAW" roll="$ROLL" />
            <drag forward="$DRAG_FORWARD" reverse="$DRAG_REVERSE" horizontal="$DRAG_HORIZONTAL" vertical="$DRAG_VERTICAL" pitch="$DRAG_PITCH" yaw="$DRAG_YAW" roll="$DRAG_ROLL" />
            <accfactors forward="$ACC_FORWARD" reverse="$ACC_REVERSE" horizontal="$ACC_HORIZONTAL" vertical="$ACC_VERTICAL" />
        </physics>
XML;


    protected function renderTag(): string
    {
        return str_replace(
            array_keys($this->getValues()),
            array_values($this->getValues()),
            self::TAG_TEMPLATE
        );
    }

    private function getValues() : array
    {
        return array(
            '$MASS' => dec3($this->mass),
            '$PITCH' => dec3($this->inertia->getPitch()),
            '$YAW' => dec3($this->inertia->getYaw()),
            '$ROLL' => dec3($this->inertia->getRoll()),
            '$DRAG_FORWARD' => dec3($this->drag->getForward()),
            '$DRAG_REVERSE' => dec3($this->drag->getReverse()),
            '$DRAG_HORIZONTAL' => dec3($this->drag->getHorizontal()),
            '$DRAG_VERTICAL' => dec3($this->drag->getVertical()),
            '$DRAG_PITCH' => dec3($this->drag->getPitch()),
            '$DRAG_YAW' => dec3($this->drag->getYaw()),
            '$DRAG_ROLL' => dec3($this->drag->getRoll()),
            '$ACC_FORWARD' => dec2($this->accelerationFactors->getForward()),
            '$ACC_REVERSE' => dec2($this->accelerationFactors->getReverse()),
            '$ACC_HORIZONTAL' => dec2($this->accelerationFactors->getHorizontal()),
            '$ACC_VERTICAL' => dec2($this->accelerationFactors->getVertical())
        );
    }
}
