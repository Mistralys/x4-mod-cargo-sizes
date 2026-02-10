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
            <!-- JERK: Tier-based reduction (PHYSICS-CORRECT FIX) -->
            <!-- Physics: Heavier ships have SLOWER acceleration changes -->
            <!-- OLD CODE WAS BACKWARDS: Incorrectly increased jerk with mass -->
            
            <!-- Forward jerk: General movement -->
            <!-- Original: accel=$FORWARD_ACCEL_ORIG, decel=$FORWARD_DECEL_ORIG → Adjusted: accel=$FORWARD_ACCEL, decel=$FORWARD_DECEL -->
            <forward accel="$FORWARD_ACCEL" decel="$FORWARD_DECEL" ratio="3" />
            
            <!-- Boost jerk: Boost mode -->
            <!-- Original: accel=$BOOST_ACCEL_ORIG → Adjusted: accel=$BOOST_ACCEL -->
            <forward_boost accel="$BOOST_ACCEL" ratio="3" />
            
            <!-- Travel jerk: CRITICAL FOR TRAVEL MODE -->
            <!-- Original: accel=$TRAVEL_ACCEL_ORIG, decel=$TRAVEL_DECEL_ORIG → Adjusted: accel=$TRAVEL_ACCEL, decel=$TRAVEL_DECEL -->
            <!-- FIX: Removed arbitrary 2x penalty - uses same tier-based reduction as other jerk -->
            <forward_travel accel="$TRAVEL_ACCEL" decel="$TRAVEL_DECEL" ratio="4" />
            
            <!-- Strafe jerk: Original=$STRAFE_ORIG → Adjusted=$STRAFE -->
            <strafe value="$STRAFE" />
            
            <!-- Angular jerk: Original=$ANGULAR_ORIG → Adjusted=$ANGULAR -->
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
            // Forward jerk values
            '$FORWARD_ACCEL' => dec2($this->jerk->getForward()->getAcceleration()),
            '$FORWARD_DECEL' => dec2($this->jerk->getForward()->getDeceleration()),
            '$FORWARD_ACCEL_ORIG' => dec2($this->jerk->getForward()->getOriginal()->getAcceleration()),
            '$FORWARD_DECEL_ORIG' => dec2($this->jerk->getForward()->getOriginal()->getDeceleration()),
            
            // Boost jerk values
            '$BOOST_ACCEL' => dec2($this->jerk->getBoost()->getAcceleration()),
            '$BOOST_ACCEL_ORIG' => dec2($this->jerk->getBoost()->getOriginal()->getAcceleration()),
            
            // Travel jerk values
            '$TRAVEL_ACCEL' => dec2($this->jerk->getTravel()->getAcceleration()),
            '$TRAVEL_DECEL' => dec2($this->jerk->getTravel()->getDeceleration()),
            '$TRAVEL_ACCEL_ORIG' => dec2($this->jerk->getTravel()->getOriginal()->getAcceleration()),
            '$TRAVEL_DECEL_ORIG' => dec2($this->jerk->getTravel()->getOriginal()->getDeceleration()),
            
            // Strafe and angular values
            '$STRAFE' => dec2($this->jerk->getStrafe()),
            '$ANGULAR' => dec2($this->jerk->getAngular()),
            '$STRAFE_ORIG' => dec2($this->jerk->getOriginal()->getStrafe()),
            '$ANGULAR_ORIG' => dec2($this->jerk->getOriginal()->getAngular()),
        );
    }
}
