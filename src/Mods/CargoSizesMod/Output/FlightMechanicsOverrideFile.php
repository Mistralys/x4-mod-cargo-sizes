<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\BaseXMLFile;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeBuildTools;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedAccelerationFactors;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
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
    private float $jerkReductionPercent;
    private float $steeringIncreaseMultiplier;
    private float $inertiaIncreaseMultiplier;
    private float $accelerationScalingFactor;
    private static ?DiagnosticsLogger $diagnosticsLogger = null;

    /**
     * Sets the diagnostics logger for physics calculations.
     *
     * @param DiagnosticsLogger $logger Diagnostics logger
     * @return void
     */
    public static function setDiagnosticsLogger(DiagnosticsLogger $logger): void
    {
        self::$diagnosticsLogger = $logger;
    }

    /**
     * Clears the diagnostics logger.
     *
     * @return void
     */
    public static function clearDiagnosticsLogger(): void
    {
        self::$diagnosticsLogger = null;
    }

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
            $this->accelerationScalingFactor
        );
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

        $this->addCustomOverride(new JerkOverrideDef(
            $this->getXMLFile()->getMacroName(),
            new AdjustedJerk(
                $jerk,
                $this->jerkReductionPercent
            )
        ));
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
            'Mass ratio: x%s (= adjusted full mass / original full mass = %s / %s)',
            $this->mass->formatMassRatio(),
            dec($this->mass->getAdjustedFullLoadMass(), 0),
            dec($this->mass->getOriginalFullLoadMass(), 0)
        );

        $config = CargoSizeBuildTools::getConfig();

        // Use tier-based configuration if available
        if ($config->hasTierBasedConfiguration()) {
            $cargoMultiplier = $this->getMultiplier();
            
            // Drag reduction: tier-based
            $dragTier = $config->findDragTierForMultiplier($cargoMultiplier);
            $this->dragReductionMultiplier = $dragTier->getReductionPercent();
            
            $this->addComment(
                'Drag reduction: %d%% (cargo tier: <= %.1fx)',
                (int)($this->dragReductionMultiplier * 100),
                $dragTier->getMaxMultiplier()
            );
            
            // Jerk reduction: tier-based (CRITICAL FIX - was backwards!)
            $jerkTier = $config->findJerkTierForMultiplier($cargoMultiplier);
            $this->jerkReductionPercent = $jerkTier->getReductionPercent();
            
            $this->addComment(
                'Jerk reduction: %d%% (cargo tier: <= %.1fx) - PHYSICS-CORRECT',
                (int)($this->jerkReductionPercent * 100),
                $jerkTier->getMaxMultiplier()
            );
            
            // Steering: use legacy factor
            $this->steeringIncreaseMultiplier = (float)($this->mass->getMultiplier() * $config->getSteeringIncreaseFactor());
            $this->addComment(
                'Steering increase: x%s (= inverse mass ratio * %s)',
                dec3($this->steeringIncreaseMultiplier),
                dec2($config->getSteeringIncreaseFactor())
            );
            
            // Inertia: dampened scaling
            $inertiaImpactFactor = $config->getInertiaImpactFactor();
            $massIncrease = $this->mass->getMassRatio() - 1.0;
            $dampenedIncrease = $massIncrease * $inertiaImpactFactor;
            $this->inertiaIncreaseMultiplier = $dampenedIncrease;
            
            $this->addComment(
                'Inertia increase: x%s (= (mass ratio - 1.0) * impact factor = %.2f * %.2f)',
                dec3($this->inertiaIncreaseMultiplier),
                $massIncrease,
                $inertiaImpactFactor
            );
            
            // Acceleration: scale proportionally with mass to maintain responsiveness
            $responsiveness = $config->getAccelerationResponsiveness();
            $this->accelerationScalingFactor = $this->mass->getMassRatio() * $responsiveness;
            
            $this->addComment(
                'Acceleration scaling: x%s (= mass ratio * responsiveness = %.2f * %.2f)',
                dec3($this->accelerationScalingFactor),
                $this->mass->getMassRatio(),
                $responsiveness
            );
            $this->addComment('Physics: AccelFactor/Mass ratio maintained (preserves time-to-speed)');
            
            // Log to diagnostics if logger is set
            $this->logToDiagnostics($config, $dragTier, $jerkTier);
            
        } else {
            // Legacy factor-based calculation
            $massMultiplier = $this->mass->getMultiplier();
            
            $this->dragReductionMultiplier = (float)($massMultiplier * $config->getDragReductionFactor());
            $this->jerkReductionPercent = $massMultiplier; // Legacy: use backwards multiplier
            $this->steeringIncreaseMultiplier = (float)($massMultiplier * $config->getSteeringIncreaseFactor());
            $this->inertiaIncreaseMultiplier = (float)($massMultiplier * $config->getInertiaIncreaseFactor());
            $this->accelerationScalingFactor = (float)($massMultiplier * $config->getSteeringIncreaseFactor()); // Legacy uses same factor

            $this->addComment('Legacy factor-based calculation');
            $this->addComment('Steering increase: x%s (= mass multiplier * %s)', dec3($this->steeringIncreaseMultiplier), dec2($config->getSteeringIncreaseFactor()));
            $this->addComment('Drag reduction: x%s (= mass multiplier * %s)', dec3($this->dragReductionMultiplier), dec2($config->getDragReductionFactor()));
            $this->addComment('Inertia increase: x%s (= mass multiplier * %s)', dec3($this->inertiaIncreaseMultiplier), dec2($config->getInertiaIncreaseFactor()));
        }
    }

    /**
     * Logs calculations to diagnostics logger if available.
     *
     * @param \Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig $config Build configuration
     * @param ReductionTier $dragTier Drag reduction tier applied
     * @param ReductionTier $jerkTier Jerk reduction tier applied
     * @return void
     */
    private function logToDiagnostics(
        \Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig $config,
        ReductionTier $dragTier,
        ReductionTier $jerkTier
    ): void
    {
        if (self::$diagnosticsLogger === null) {
            return;
        }

        // Create PhysicsCalculator with the same data
        $physics = new PhysicsCalculator(
            $this->mass->getMass(),
            $this->getCargo(),
            $this->getAdjustedCargo(),
            $this->getMultiplier(),
            $config->getUseEffectiveRatioCap()
        );

        // Log ship calculations
        self::$diagnosticsLogger->logShip(
            $this->ship,
            $physics,
            $dragTier,
            $jerkTier,
            $config
        );
    }
}
