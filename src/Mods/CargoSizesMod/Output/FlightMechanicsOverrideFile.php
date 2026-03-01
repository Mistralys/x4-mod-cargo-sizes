<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\BaseXMLFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeBuildTools;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AccelerationOverrideDef;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedAccelerationFactors;
use function Mistralys\X4\dec;
use function Mistralys\X4\dec3;

class FlightMechanicsOverrideFile extends BaseOverrideFile
{
    private MassAdjustment $mass;
    private float $accelerationScalingFactor;
    private ?DiagnosticsLogger $diagnosticsLogger = null;

    /**
     * Sets the diagnostics logger for physics calculations.
     *
     * @param DiagnosticsLogger $logger Diagnostics logger
     * @return void
     */
    public function setDiagnosticsLogger(DiagnosticsLogger $logger): void
    {
        $this->diagnosticsLogger = $logger;
    }

    protected function preRender() : void
    {
        $this->addComment('Ship size: %s', strtoupper($this->ship->getSize()));

        $this->calculateMassAdjustment();
        $this->logToDiagnostics(CargoSizeBuildTools::getConfig()->getAccelerationResponsiveness());

        $this->addCustomOverride(new AccelerationOverrideDef(
            $this->getXMLFile()->getMacroName(),
            $this->resolveAccelerationValues()
        ));
    }

    public function getName(): string
    {
        return $this->ship->getShipFileName();
    }

    public function getXMLFile() : BaseXMLFile
    {
        return $this->ship->getShipXMLFile();
    }

    private function resolveAccelerationValues() : AdjustedAccelerationFactors
    {
        return new AdjustedAccelerationFactors(
            $this->ship->getShipXMLFile()->getAccelerationFactors(),
            $this->accelerationScalingFactor
        );
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
        $responsiveness = $config->getAccelerationResponsiveness();
        $this->accelerationScalingFactor = $this->mass->getMassRatio() * $responsiveness;

        $this->addComment(
            'Acceleration scaling: x%s (= mass ratio * responsiveness = %.2f * %.2f)',
            dec3($this->accelerationScalingFactor),
            $this->mass->getMassRatio(),
            $responsiveness
        );
        $this->addComment('Physics: AccelFactor/Mass ratio maintained (preserves time-to-speed)');
    }

    /**
     * Logs calculations to diagnostics logger if available.
     *
     * @param float $responsiveness Acceleration responsiveness factor
     * @return void
     */
    private function logToDiagnostics(float $responsiveness): void
    {
        if ($this->diagnosticsLogger === null) {
            return;
        }

        $this->diagnosticsLogger->logShip(
            $this->ship,
            $this->mass->getMassRatio(),
            $this->accelerationScalingFactor,
            $responsiveness
        );
    }
}
