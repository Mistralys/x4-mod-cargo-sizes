<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use function Mistralys\X4\dec2;

class JerkOverrideDef extends TagOverrideDef
{
    private AdjustedJerk $jerk;

    public function __construct(string $macroName, AdjustedJerk $jerk)
    {
        parent::__construct($macroName);

        $this
            ->setMacroPath('properties/jerk')
            ->setTagName('jerk')
            ->setComment('NOTE: Overriding the whole jerk section for reliability.')
            ->addComments($jerk->getComments())
            ->addComments($jerk->getBoost()->getComments())
            ->addComments($jerk->getForward()->getComments())
            ->addComments($jerk->getTravel()->getComments());

        $this->jerk = $jerk;
    }

    private const TAG_TEMPLATE = <<<'XML'
        <jerk>
            <forward accel="$FORWARD_ACCEL" decel="$FORWARD_DECEL" ratio="3" />
            <forward_boost accel="$BOOST_ACCEL" ratio="3" />
            <forward_travel accel="$TRAVEL_ACCEL" decel="$TRAVEL_DECEL" ratio="4" />
            <strafe value="$STRAFE" />
            <angular value="$ANGULAR" />
        </jerk>
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
            '$FORWARD_ACCEL' => dec2($this->jerk->getForward()->getAcceleration()),
            '$FORWARD_DECEL' => dec2($this->jerk->getForward()->getDeceleration()),
            '$BOOST_ACCEL' => dec2($this->jerk->getBoost()->getAcceleration()),
            '$TRAVEL_ACCEL' => dec2($this->jerk->getTravel()->getAcceleration()),
            '$TRAVEL_DECEL' => dec2($this->jerk->getTravel()->getDeceleration()),
            '$STRAFE' => dec2($this->jerk->getStrafe()),
            '$ANGULAR' => dec2($this->jerk->getAngular()),
        );
    }
}
