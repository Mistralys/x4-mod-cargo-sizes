<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\BaseXMLFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeBuildTools;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedAccelerationFactors;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\BaseJerkMovement;
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

        $this->overridePhysics();
        $this->overrideJerk();
        $this->overrideSteeringCurve();

    }

    public function getName(): string
    {
        return $this->ship->getShipFileName();
    }

    /**
     * Using a custom override for the physics section,
     * because we replace the whole section instead of
     * replacing individual tags or attributes.
     *
     * This is done after a lot of trials and errors because
     * of how X4 handles overriding values. Switching between
     * `<add>` and `<replace>` tags depending on whether the
     * attribute exists or not is not reliable. Especially
     * since other mods may also add tags and attributes.
     *
     * In the end, it is more reliable and easier to just
     * replace the whole section.
     */
    private function overridePhysics() : void
    {
        $this->addCustomOverride(new PhysicsOverrideDef(
            $this->getXMLFile()->getMacroName(),
            $this->ship->getShipXMLFile()->getMass(),
            $this->resolveInertiaValues(),
            $this->resolveDragValues(),
            $this->resolveAccelerationValues()
        ));
    }

    private function resolveInertiaValues() : AdjustedInertia
    {
        return new AdjustedInertia(
            $this->ship->getShipXMLFile()->getInertia(),
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

    private function resolveAccelerationValues() : AdjustedAccelerationFactors
    {
        return new AdjustedAccelerationFactors(
            $this->ship->getShipXMLFile()->getAccelerationFactors(),
            $this->mass->getMultiplier()
        );
    }

    private function calcIncrease(float $value, float $multiplier) : float
    {
        return $value + ($value * $multiplier);
    }

    private function resolveDragValues() : AdjustedDrag
    {
        return new AdjustedDrag(
            $this->ship->getShipXMLFile()->getDrag(),
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
