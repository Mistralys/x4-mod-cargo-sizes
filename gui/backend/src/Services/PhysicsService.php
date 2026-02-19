<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Database\Engines\EngineDefs;

/**
 * Physics calculation service wrapping PhysicsCalculator.
 *
 * Acceleration-only implementation: only the thruster acceleration factors
 * are overridden. Drag, inertia, and jerk are no longer modified.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsService
{
    private readonly ShipDataService $shipDataService;
    private readonly EngineDefs $engineDefs;

    public function __construct(
        ShipDataService $shipDataService,
        ?EngineDefs $engineDefs = null
    ) {
        $this->shipDataService = $shipDataService;
        $this->engineDefs = $engineDefs ?? EngineDefs::getInstance();
    }

    /**
     * Calculates adjusted physics values for a ship.
     *
     * @param PhysicsRequest $request Physics calculation parameters
     * @return PhysicsResponse Complete physics calculation results
     * @throws GUIException
     */
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
    {
        try {
            $calculator = new PhysicsCalculator(
                $request->baseMass,
                $request->originalCargo,
                $request->adjustedCargo,
                $request->cargoMultiplier
            );

            // Acceleration scaling: massRatio × responsiveness
            $accelerationScalingFactor = $calculator->getMassRatio() * $request->accelerationResponsiveness;

            // Calculate engine performance if engine ID provided
            $enginePerformance = null;
            if ($request->engineId !== null) {
                $dragForward = $this->loadShipForwardDrag($request->shipId);
                $engineCount = 1;
                if ($request->shipId !== null) {
                    $shipDetails = $this->shipDataService->getShipDetails($request->shipId);
                    $engineCount = $shipDetails->engineCount;
                }

                $enginePerformance = $this->calculateEnginePerformance(
                    $request->engineId,
                    $calculator->getOriginalFullMass(),
                    $dragForward,
                    $request->accelerationResponsiveness,
                    $engineCount
                );
            }

            return new PhysicsResponse(
                massRatio: $calculator->getMassRatio(),
                originalFullMass: $calculator->getOriginalFullMass(),
                adjustedFullMass: $calculator->getAdjustedFullMass(),
                massIncrease: $calculator->getMassIncrease(),
                originalCargo: $request->originalCargo,
                adjustedCargo: $request->adjustedCargo,
                accelerationScalingFactor: $accelerationScalingFactor,
                accelerationResponsiveness: $request->accelerationResponsiveness,
                enginePerformance: $enginePerformance,
                topSpeedOriginal: $enginePerformance?->topSpeed,
                topSpeedAdjusted: $enginePerformance?->topSpeedAdjusted,
                accelerationOriginal: $enginePerformance?->originalAcceleration,
                accelerationAdjusted: $enginePerformance?->adjustedAcceleration,
            );
        } catch (\Exception $e) {
            throw new GUIException(
                'Physics calculation failed: ' . $e->getMessage(),
                '',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Returns the forward drag coefficient for a ship.
     *
     * Uses real ship data if shipId is provided, otherwise falls back to a
     * neutral default for backward compatibility with requests without a shipId.
     *
     * @param string|null $shipId
     * @return float
     * @throws GUIException
     */
    private function loadShipForwardDrag(?string $shipId): float
    {
        if ($shipId !== null) {
            $shipDetails = $this->shipDataService->getShipDetails($shipId);
            return $shipDetails->dragOriginal['forward'];
        }

        return 100.0; // Neutral default when no shipId provided
    }

    /**
     * Calculates engine performance metrics.
     *
     * Drag is no longer modified by the mod, so topSpeedAdjusted = topSpeedOriginal.
     * Adjusted acceleration = original × responsiveness (AccelFactor override effect).
     *
     * @param string $engineId Engine identifier
     * @param float $originalMass Original full ship mass
     * @param float $dragForward Forward drag coefficient (original, unmodified)
     * @param float $accelerationResponsiveness Responsiveness factor (1.0 = same feel as original)
     * @param int $engineCount Number of engines
     * @return EnginePerformance
     * @throws GUIException
     */
    private function calculateEnginePerformance(
        string $engineId,
        float $originalMass,
        float $dragForward,
        float $accelerationResponsiveness,
        int $engineCount = 1
    ): EnginePerformance {
        try {
            $engineDef = $this->engineDefs->getByID($engineId);

            $thrustForward = $engineDef->getThrustForward();
            $thrustReverse = $engineDef->getThrustReverse();
            $thrustBoost = $engineDef->getBoostThrust();
            $thrustTravel = $engineDef->getTravelThrust();

            $totalThrustForward = $thrustForward * $engineCount;
            $totalThrustReverse = $thrustReverse * $engineCount;
            $thrustNewtonsForward = $totalThrustForward * 1000.0;

            $g = 9.81;
            $originalTWR = $thrustNewtonsForward / ($originalMass * $g);
            // AccelFactor override scales effective TWR by responsiveness fraction
            $adjustedTWR = $originalTWR * $accelerationResponsiveness;
            $twrReductionPercent = (($originalTWR - $adjustedTWR) / $originalTWR) * 100.0;

            $originalAcceleration = $thrustNewtonsForward / $originalMass;
            // AccelFactor × massRatio × responsiveness applied; effective accel = original × responsiveness
            $adjustedAcceleration = $originalAcceleration * $accelerationResponsiveness;

            // Top speed: drag is no longer modified, so adjusted = original
            $topSpeed = null;
            $topSpeedReverse = null;
            $topSpeedBoost = null;
            $topSpeedTravel = null;

            if ($dragForward > 0) {
                $topSpeed = $totalThrustForward / $dragForward;
                $topSpeedReverse = $totalThrustReverse / $dragForward;
                $topSpeedBoost = $topSpeed * $thrustBoost;
                $topSpeedTravel = $topSpeed * $thrustTravel;
            }

            return new EnginePerformance(
                engineId: $engineId,
                thrustForward: $thrustForward,
                originalTWR: $originalTWR,
                adjustedTWR: $adjustedTWR,
                twrReductionPercent: $twrReductionPercent,
                originalAcceleration: $originalAcceleration,
                adjustedAcceleration: $adjustedAcceleration,
                engineCount: $engineCount,
                topSpeed: $topSpeed,
                topSpeedAdjusted: $topSpeed, // Drag unchanged, so adjusted = original
                topSpeedReverse: $topSpeedReverse,
                topSpeedBoost: $topSpeedBoost,
                topSpeedTravel: $topSpeedTravel
            );
        } catch (\Exception $e) {
            throw new GUIException(
                sprintf('Engine performance calculation failed for engine %s: %s', $engineId, $e->getMessage()),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE,
                $e
            );
        }
    }
}

