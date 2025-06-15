<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\HTMLTag;
use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\BaseXMLFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeBuildTools;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\BaseJerkMovement;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\EmptyAccelerationFactors;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use function Mistralys\X4\dec;
use function Mistralys\X4\dec2;
use function Mistralys\X4\dec3;

class FlightMechanicsOverrideFile extends BaseOverrideFile
{
    private MassAdjustment $mass;
    private float $dragReductionMultiplier;
    private float $steeringIncreaseMultiplier;
    private float $inertiaIncreaseMultiplier;

    protected function preRender() : void
    {
        $this->addComment('Ship size: %s', strtoupper($this->ship->getSize()));

        $this->calculateMassAdjustment();

        $this->overrideDrag();
        $this->overrideAcceleration();
        $this->overrideJerk();
        $this->overrideSteeringCurve();
        $this->overrideInertia();
    }

    public function getName(): string
    {
        return $this->ship->getShipFileName();
    }

    private function overrideInertia() : void
    {
        $inertia = $this->ship->getShipXMLFile()->getInertia();

        $this->multiplierIncreaseFloat(
            'properties/physics/inertia/@pitch',
            $inertia->getPitch(),
            3,
            $this->inertiaIncreaseMultiplier
        );

        $this->multiplierIncreaseFloat(
            'properties/physics/inertia/@yaw',
            $inertia->getYaw(),
            3,
            $this->inertiaIncreaseMultiplier
        );

        $this->multiplierIncreaseFloat(
            'properties/physics/inertia/@roll',
            $inertia->getRoll(),
            3,
            $this->inertiaIncreaseMultiplier
        );
    }

    private function overrideSteeringCurve() : void
    {
        $curve = $this->ship->getShipXMLFile()->getSteeringCurve();

        foreach($curve->getPositions() as $position) {
            $this->multiplierIncreaseFloat(
                sprintf("properties/steeringcurve/point[@position='%s']/@value", $position->getPosition()),
                $position->getValue(),
                2,
                $this->steeringIncreaseMultiplier
            );
        }
    }

    private function overrideAcceleration() : void
    {
        $factors = $this->ship->getShipXMLFile()->getAccelerationFactors();

        $override = $this->addTagOverride('accfactors')
            ->setMacroPath('properties/physics')
            ->enableAddMode($factors instanceof EmptyAccelerationFactors)
            ->setComment('Overriding the whole tag, because not all attributes are present in the original files.');

        $this->overrideAccelerationAttribute($override, 'forward', $factors->getForward());
        $this->overrideAccelerationAttribute($override, 'reverse', $factors->getReverse());
        $this->overrideAccelerationAttribute($override, 'horizontal', $factors->getHorizontal());
        $this->overrideAccelerationAttribute($override, 'vertical', $factors->getVertical());
    }

    private function overrideAccelerationAttribute(TagOverrideDef $override, string $name, float $value) : void
    {
        $multiplier = $this->mass->getMultiplier();
        $increase = $value * $multiplier;
        $newValue = $value + $increase;

        $override->addComment(
            '@%s: %s = %s + %s (increase x%s)',
            $name,
            dec2($newValue),
            dec2($value),
            dec2($increase),
            dec2($multiplier)
        );

        $override->setAttribute($name, dec2($newValue));
    }

    private function overrideDrag() : void
    {
        $this->multiplierDecreaseFloat(
            'properties/physics/drag/@forward',
            $this->ship->getShipXMLFile()->getDrag()->getForward(),
            3,
            $this->dragReductionMultiplier
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
            2,
            $this->mass->getMultiplier()
        );
    }

    private function overrideJerkMovement(BaseJerkMovement $movement) : void
    {
        $multiplier = $this->mass->getMultiplier();

        $this->multiplierIncreaseFloat(
            'properties/jerk/'.$movement->getTagName().'/@accel',
            $movement->getAcceleration(),
            2,
            $multiplier
        );

        $this->multiplierIncreaseFloat(
            'properties/jerk/'.$movement->getTagName().'/@decel',
            $movement->getDeceleration(),
            2,
            $multiplier
        );
    }

    private function overrideJerkBoost(JerkBoost $boost) : void
    {
        $this->multiplierIncreaseFloat(
            'properties/jerk/forward_boost/@accel',
            $boost->getAcceleration(),
            2,
            $this->mass->getMultiplier()
        );
    }

    public function getXMLFile() : BaseXMLFile
    {
        return $this->ship->getShipXMLFile();
    }

    private function calculateMassAdjustment() : void
    {
        $this->mass = new MassAdjustment(
            $this->ship->getShipXMLFile()->getMass(),
            $this->getCargo(),
            $this->getAdjustedCargo()
        );

        $this->addComment('Ship base mass: %s', dec($this->mass->getMass(), 0));
        $this->addComment('Ship base cargo: %s', dec($this->getCargo(), 0));
        $this->addComment('Ship adjusted cargo: %s', dec($this->getAdjustedCargo(), 0));

        $this->addComment(
            'Mass multiplier: x%s (= original full load mass / new full load mass = %s / %s)',
            $this->mass->formatMultiplier(),
            dec($this->mass->getOriginalFullLoadMass(), 0),
            dec($this->mass->getAdjustedFullLoadMass(), 0)
        );

        $massMultiplier = $this->mass->getMultiplier();
        $config = CargoSizeBuildTools::getConfig();

        $this->dragReductionMultiplier = (float)($massMultiplier * $config->getDragReductionFactor());
        $this->steeringIncreaseMultiplier = (float)($massMultiplier * $config->getSteeringIncreaseFactor());
        $this->inertiaIncreaseMultiplier = (float)($massMultiplier * $config->getInertiaIncreaseFactor());

        $this->addComment('Steering increase: x%s (= mass multiplier * %s)', dec3($this->steeringIncreaseMultiplier), dec2($config->getSteeringIncreaseFactor()));
        $this->addComment('Drag reduction: x%s (= mass multiplier * %s)', dec3($this->dragReductionMultiplier), dec2($config->getDragReductionFactor()));
        $this->addComment('Inertia increase: x%s (= mass multiplier * %s)', dec3($this->inertiaIncreaseMultiplier), dec2($config->getInertiaIncreaseFactor()));
    }
}
