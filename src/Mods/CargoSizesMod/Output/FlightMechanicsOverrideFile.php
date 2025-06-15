<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\FileHelper;
use Mistralys\X4\Database\Ships\ShipSizes;
use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\BaseJerkMovement;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use function Mistralys\X4\dec;
use function Mistralys\X4\dec2;

class FlightMechanicsOverrideFile extends BaseOverrideFile
{
    /**
     * @var array<string, float>
     */
    private array $dragReduction = array(
        ShipSizes::SIZE_XS => 0.10,
        ShipSizes::SIZE_S => 0.14,
        ShipSizes::SIZE_M => 0.18,
        ShipSizes::SIZE_L => 0.22,
        ShipSizes::SIZE_XL => 0.26
    );

    /**
     * @var array<string, float>
     */
    private array $steeringIncrease = array(
        ShipSizes::SIZE_XS => 0.05,
        ShipSizes::SIZE_S => 0.10,
        ShipSizes::SIZE_M => 0.13,
        ShipSizes::SIZE_L => 0.17,
        ShipSizes::SIZE_XL => 0.21
    );

    private MassAdjustment $mass;

    protected function preRender() : void
    {
        $this->addComment('Ship size: %s', strtoupper($this->ship->getSize()));
        $this->addComment('Steering increase: x%s', number_format($this->steeringIncrease[$this->ship->getSize()], 2));
        $this->addComment('Drag reduction: x%s', number_format($this->dragReduction[$this->ship->getSize()], 2));

        $this->calculateMassAdjustment();
        $this->overrideDrag();
        $this->overrideAcceleration();
        $this->overrideJerk();
        $this->overrideSteeringCurve();
    }

    public function getName(): string
    {
        return $this->ship->getShipFileName();
    }

    private function overrideSteeringCurve() : void
    {
        $curve = $this->ship->getShipXMLFile()->getSteeringCurve();

        foreach($curve->getPositions() as $position) {
            $this->multiplierIncreaseFloat(
                sprintf("properties/steeringcurve/point[@position='%s']/@value", $position->getPosition()),
                $position->getValue(),
                2,
                $this->steeringIncrease[$this->ship->getSize()]
            );
        }
    }

    private function overrideAcceleration() : void
    {
        $this->multiplierIncreaseFloat(
            'properties/physics/accfactors/@forward',
            $this->ship->getShipXMLFile()->getAccelerationFactor(),
            2
        );
    }

    private function overrideDrag() : void
    {
        $this->multiplierDecreaseFloat(
            'properties/physics/drag/@forward',
            $this->ship->getShipXMLFile()->getDrag()->getForward(),
            3,
            $this->dragReduction[$this->ship->getSize()]
        );
    }

    private function overrideJerk() : void
    {
        $jerk = $this->ship->getShipXMLFile()->getJerk();

        $this->overrideJerkMovement($jerk->getForward());
        $this->overrideJerkMovement($jerk->getTravel());
        $this->overrideJerkBoost($jerk->getBoost());
        $this->overrideJerkStrafe($jerk);
    }

    private function overrideJerkStrafe(Jerk $jerk) : void
    {
        $this->multiplierIncreaseFloat(
            'properties/jerk/strafe/@value',
            $jerk->getStrafe(),
            2
        );
    }

    private function multiplierIncreaseFloat(string $path, float $value, int $precision, ?float $multiplier=null) : void
    {
        if($multiplier === null) {
            $multiplier = $this->mass->getMultiplier();
        }

        $increase = $value * $multiplier;

        $this->addOverride()
            ->setMacroPath($path)
            ->setFloat($value + $increase, 2)
            ->setComment(
                '= %s + %s (increase x%s)',
                dec($value, $precision),
                dec($increase, $precision),
                dec2($multiplier)
            );
    }

    private function multiplierDecreaseFloat(string $path, float $value, int $precision, ?float $multiplier=null) : void
    {
        if($multiplier === null) {
            $multiplier = $this->mass->getMultiplier();
        }

        $decrease = $value * $multiplier;

        $this->addOverride()
            ->setMacroPath($path)
            ->setFloat($value - $decrease, 2)
            ->setComment(
                '= %s - %s (decrease x%s)',
                dec($value, $precision),
                dec($decrease, $precision),
                dec2($multiplier)
            );
    }

    private function overrideJerkMovement(BaseJerkMovement $movement) : void
    {
        $this->multiplierIncreaseFloat(
            'properties/jerk/'.$movement->getTagName().'/@accel',
            $movement->getAcceleration(),
            2
        );

        $this->multiplierIncreaseFloat(
            'properties/jerk/'.$movement->getTagName().'/@decel',
            $movement->getDeceleration(),
            2
        );
    }

    private function overrideJerkBoost(JerkBoost $boost) : void
    {
        $this->multiplierIncreaseFloat(
            'properties/jerk/forward_boost/@accel',
            $boost->getAcceleration(),
            2
        );
    }

    public function getMacroID() : string
    {
        return FileHelper::removeExtension($this->ship->getShipFileName());
    }

    private function calculateMassAdjustment() : void
    {
        $this->mass = new MassAdjustment(
            $this->ship->getShipXMLFile()->getMass(),
            $this->getCargo(),
            $this->getAdjustedCargo()
        );

        $this->addComment(
            'Mass multiplier: x%s (= original full load mass / new full load mass = %s / %s)',
            $this->mass->formatMultiplier(),
            number_format($this->mass->getOriginalFullLoadMass(), 0),
            number_format($this->mass->getAdjustedFullLoadMass(), 0)
        );
    }
}
