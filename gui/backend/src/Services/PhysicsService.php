<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ReductionTiers;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Database\Engines\EngineDefs;
use Mistralys\X4\Database\Ships\ShipDefs;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkForward;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkTravel;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Utils\PhysicsCalculationHelper;

/**
 * Physics calculation service wrapping PhysicsCalculator.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsService
{
    use PhysicsCalculationHelper;
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
            // Create physics calculator
            $calculator = new PhysicsCalculator(
                $request->baseMass,
                $request->originalCargo,
                $request->adjustedCargo,
                $request->cargoMultiplier,
                $request->useEffectiveRatioCap
            );

            // Find appropriate tiers for this multiplier
            $dragTier = $this->findTierForMultiplier($request->dragReductionTiers, $request->cargoMultiplier);
            $jerkTier = $this->findTierForMultiplier($request->jerkReductionTiers, $request->cargoMultiplier);

            // Load ship physics data (drag, inertia, jerk)
            $shipData = $this->loadShipPhysicsData($request->shipId);
            $originalDrag = $shipData['originalDrag'];
            $originalInertia = $shipData['originalInertia'];
            $originalJerk = $shipData['originalJerk'];
            $shipDef = $shipData['shipDef'];

            // Apply drag reduction
            $adjustedDrag = new AdjustedDrag($originalDrag, $dragTier->getReductionPercent());

            // Apply inertia adjustment (increases with mass)
            $inertiaMultiplier = 1.0 + (($calculator->getMassRatio() - 1.0) * $request->inertiaImpactFactor);
            $adjustedInertia = new AdjustedInertia($originalInertia, $inertiaMultiplier);

            // Apply jerk reduction
            $jerkMultiplier = $calculator->getInverseMassRatio() * (1.0 - $jerkTier->getReductionPercent());
            $adjustedJerk = new AdjustedJerk($originalJerk, $jerkMultiplier);

            // Calculate engine performance if engine ID provided
            $enginePerformance = null;
            if ($request->engineId !== null) {
                // Get engine count (default to 1 for backward compatibility)
                $engineCount = 1;
                if ($shipDef !== null) {
                    $engineCount = $shipDef->countEngines();
                }
                
                $enginePerformance = $this->calculateEnginePerformance(
                    $request->engineId,
                    $calculator->getOriginalFullMass(),
                    $calculator->getAdjustedFullMass(),
                    $originalDrag->getForward(),
                    $engineCount
                );
            }

            // Build and return response
            $physicsData = new PhysicsData(
                $originalDrag,
                $adjustedDrag,
                $originalInertia,
                $adjustedInertia,
                $originalJerk,
                $adjustedJerk
            );
            $tiers = new ReductionTiers($dragTier, $jerkTier);

            return $this->buildPhysicsResponse(
                $calculator,
                $physicsData,
                $tiers,
                $request,
                $enginePerformance
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
     * Finds the appropriate tier for a cargo multiplier.
     *
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $tiers
     * @param float $multiplier
     * @return ReductionTier
     * @throws GUIException
     */
    private function findTierForMultiplier(array $tiers, float $multiplier): ReductionTier
    {
        foreach ($tiers as $tierData) {
            $tier = ReductionTier::fromArray($tierData);
            if ($tier->appliesToMultiplier($multiplier)) {
                return $tier;
            }
        }

        throw new GUIException(
            sprintf('No tier found for cargo multiplier %.1fx', $multiplier),
            '',
            GUIException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }

    /**
     * Calculates engine performance metrics including top speeds.
     *
     * @param string $engineId Engine identifier
     * @param float $originalMass Original ship mass
     * @param float $adjustedMass Adjusted ship mass
     * @param float $dragForward Forward drag coefficient
     * @param int $engineCount Number of engines (default: 1)
     * @return EnginePerformance
     * @throws GUIException
     */
    private function calculateEnginePerformance(
        string $engineId,
        float $originalMass,
        float $adjustedMass,
        float $dragForward,
        int $engineCount = 1
    ): EnginePerformance
    {
        try {
            $engineDef = EngineDefs::getInstance()->getByID($engineId);
            
            // Get real thrust values from EngineDef
            $thrustForward = $engineDef->getThrustForward();
            $thrustReverse = $engineDef->getThrustReverse();
            $thrustBoost = $engineDef->getBoostThrust();
            $thrustTravel = $engineDef->getTravelThrust();
            
            // Calculate total thrust (per engine * engine count)
            $totalThrustForward = $thrustForward * $engineCount;
            $totalThrustReverse = $thrustReverse * $engineCount;
            $totalThrustBoost = $thrustBoost * $engineCount;
            $totalThrustTravel = $thrustTravel * $engineCount;

            // Convert thrust from kN to N (1 kN = 1000 N)
            $thrustNewtonsForward = $totalThrustForward * 1000.0;

            // Calculate TWR (Thrust-to-Weight Ratio)
            // Using Earth gravity (g = 9.81 m/s²) as reference
            $g = 9.81;
            $originalWeight = $originalMass * $g;
            $adjustedWeight = $adjustedMass * $g;

            $originalTWR = $thrustNewtonsForward / $originalWeight;
            $adjustedTWR = $thrustNewtonsForward / $adjustedWeight;

            $twrReductionPercent = (($originalTWR - $adjustedTWR) / $originalTWR) * 100.0;

            // Calculate acceleration (F = ma, so a = F/m)
            $originalAcceleration = $thrustNewtonsForward / $originalMass;
            $adjustedAcceleration = $thrustNewtonsForward / $adjustedMass;
            
            // Calculate top speeds (topSpeed = totalThrust * 1000 / drag)
            // Only calculate if drag > 0 to avoid division by zero
            $topSpeed = null;
            $topSpeedAdjusted = null;
            $topSpeedReverse = null;
            $topSpeedBoost = null;
            $topSpeedTravel = null;
            
            if ($dragForward > 0) {
                // Top speed formula: v = (thrust_kN * 1000) / drag
                $topSpeed = ($totalThrustForward * 1000.0) / $dragForward;
                $topSpeedAdjusted = $topSpeed; // Top speed doesn't change with mass, only acceleration does
                $topSpeedReverse = ($totalThrustReverse * 1000.0) / $dragForward;
                $topSpeedBoost = ($totalThrustBoost * 1000.0) / $dragForward;
                $topSpeedTravel = ($totalThrustTravel * 1000.0) / $dragForward;
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
                topSpeedAdjusted: $topSpeedAdjusted,
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

    /**
     * Build physics response DTO from calculated values.
     *
     * Constructs a complete PhysicsResponse DTO from all calculated physics
     * values, including drag, inertia, jerk, and optional engine performance.
     *
     * Uses parameter objects to reduce parameter count from 11 to 5, improving
     * maintainability and readability.
     *
     * @param PhysicsCalculator $calculator Physics calculator with mass calculations
     * @param PhysicsData $physicsData Original and adjusted physics values (drag, inertia, jerk)
     * @param ReductionTiers $tiers Active reduction tiers for drag and jerk
     * @param PhysicsRequest $request Original request data
     * @param EnginePerformance|null $enginePerformance Engine performance metrics (optional)
     * @return PhysicsResponse Complete physics response DTO
     * @since 1.2.0 Method introduced
     * @since 1.3.0 Refactored to use parameter objects (PhysicsData, ReductionTiers)
     */
    private function buildPhysicsResponse(
        PhysicsCalculator $calculator,
        PhysicsData $physicsData,
        ReductionTiers $tiers,
        PhysicsRequest $request,
        ?EnginePerformance $enginePerformance
    ): PhysicsResponse
    {
        // Extract engine performance metrics if available
        $topSpeedOriginal = null;
        $topSpeedAdjusted = null;
        $accelerationOriginal = null;
        $accelerationAdjusted = null;
        
        if ($enginePerformance !== null) {
            $topSpeedOriginal = $enginePerformance->topSpeed;
            $topSpeedAdjusted = $enginePerformance->topSpeedAdjusted;
            $accelerationOriginal = $enginePerformance->originalAcceleration;
            $accelerationAdjusted = $enginePerformance->adjustedAcceleration;
        }

        return new PhysicsResponse(
            massRatio: $calculator->getMassRatio(),
            effectiveRatio: $calculator->getEffectiveRatio(),
            originalFullMass: $calculator->getOriginalFullMass(),
            adjustedFullMass: $calculator->getAdjustedFullMass(),
            massIncrease: $calculator->getMassIncrease(),
            originalCargo: $request->originalCargo,
            adjustedCargo: $request->adjustedCargo,
            dragOriginal: [
                'forward' => $physicsData->originalDrag->getForward(),
                'reverse' => $physicsData->originalDrag->getReverse(),
                'horizontal' => $physicsData->originalDrag->getHorizontal(),
                'vertical' => $physicsData->originalDrag->getVertical(),
                'pitch' => $physicsData->originalDrag->getPitch(),
                'yaw' => $physicsData->originalDrag->getYaw(),
                'roll' => $physicsData->originalDrag->getRoll()
            ],
            dragAdjusted: [
                'forward' => $physicsData->adjustedDrag->getForward(),
                'reverse' => $physicsData->adjustedDrag->getReverse(),
                'horizontal' => $physicsData->adjustedDrag->getHorizontal(),
                'vertical' => $physicsData->adjustedDrag->getVertical(),
                'pitch' => $physicsData->adjustedDrag->getPitch(),
                'yaw' => $physicsData->adjustedDrag->getYaw(),
                'roll' => $physicsData->adjustedDrag->getRoll()
            ],
            dragPercentChange: [
                'forward' => $this->calculatePercentChange($physicsData->originalDrag->getForward(), $physicsData->adjustedDrag->getForward()),
                'reverse' => $this->calculatePercentChange($physicsData->originalDrag->getReverse(), $physicsData->adjustedDrag->getReverse()),
                'horizontal' => $this->calculatePercentChange($physicsData->originalDrag->getHorizontal(), $physicsData->adjustedDrag->getHorizontal()),
                'vertical' => $this->calculatePercentChange($physicsData->originalDrag->getVertical(), $physicsData->adjustedDrag->getVertical()),
                'pitch' => $this->calculatePercentChange($physicsData->originalDrag->getPitch(), $physicsData->adjustedDrag->getPitch()),
                'yaw' => $this->calculatePercentChange($physicsData->originalDrag->getYaw(), $physicsData->adjustedDrag->getYaw()),
                'roll' => $this->calculatePercentChange($physicsData->originalDrag->getRoll(), $physicsData->adjustedDrag->getRoll())
            ],
            inertiaOriginal: [
                'pitch' => $physicsData->originalInertia->getPitch(),
                'yaw' => $physicsData->originalInertia->getYaw(),
                'roll' => $physicsData->originalInertia->getRoll()
            ],
            inertiaAdjusted: [
                'pitch' => $physicsData->adjustedInertia->getPitch(),
                'yaw' => $physicsData->adjustedInertia->getYaw(),
                'roll' => $physicsData->adjustedInertia->getRoll()
            ],
            inertiaPercentChange: [
                'pitch' => $this->calculatePercentChange($physicsData->originalInertia->getPitch(), $physicsData->adjustedInertia->getPitch()),
                'yaw' => $this->calculatePercentChange($physicsData->originalInertia->getYaw(), $physicsData->adjustedInertia->getYaw()),
                'roll' => $this->calculatePercentChange($physicsData->originalInertia->getRoll(), $physicsData->adjustedInertia->getRoll())
            ],
            jerkOriginal: [
                'forward' => ['accel' => $physicsData->originalJerk->getForward()->getAcceleration(), 'decel' => $physicsData->originalJerk->getForward()->getDeceleration()],
                'boost' => ['accel' => $physicsData->originalJerk->getBoost()->getAcceleration()],
                'travel' => ['accel' => $physicsData->originalJerk->getTravel()->getAcceleration(), 'decel' => $physicsData->originalJerk->getTravel()->getDeceleration()]
            ],
            jerkAdjusted: [
                'forward' => ['accel' => $physicsData->adjustedJerk->getForward()->getAcceleration(), 'decel' => $physicsData->adjustedJerk->getForward()->getDeceleration()],
                'boost' => ['accel' => $physicsData->adjustedJerk->getBoost()->getAcceleration()],
                'travel' => ['accel' => $physicsData->adjustedJerk->getTravel()->getAcceleration(), 'decel' => $physicsData->adjustedJerk->getTravel()->getDeceleration()]
            ],
            jerkPercentChange: [
                'forward' => [
                    'accel' => $this->calculatePercentChange($physicsData->originalJerk->getForward()->getAcceleration(), $physicsData->adjustedJerk->getForward()->getAcceleration()),
                    'decel' => $this->calculatePercentChange($physicsData->originalJerk->getForward()->getDeceleration(), $physicsData->adjustedJerk->getForward()->getDeceleration())
                ],
                'boost' => [
                    'accel' => $this->calculatePercentChange($physicsData->originalJerk->getBoost()->getAcceleration(), $physicsData->adjustedJerk->getBoost()->getAcceleration())
                ],
                'travel' => [
                    'accel' => $this->calculatePercentChange($physicsData->originalJerk->getTravel()->getAcceleration(), $physicsData->adjustedJerk->getTravel()->getAcceleration()),
                    'decel' => $this->calculatePercentChange($physicsData->originalJerk->getTravel()->getDeceleration(), $physicsData->adjustedJerk->getTravel()->getDeceleration())
                ]
            ],
            enginePerformance: $enginePerformance,
            activeTier: $tiers->getActiveTierLabel(),
            topSpeedOriginal: $topSpeedOriginal,
            topSpeedAdjusted: $topSpeedAdjusted,
            accelerationOriginal: $accelerationOriginal,
            accelerationAdjusted: $accelerationAdjusted
        );
    }

    /**
     * Load ship physics data from X4 Core database.
     *
     * Creates original Drag, Inertia, and Jerk objects either from real ship data
     * (if shipId provided) or from hardcoded defaults (for backward compatibility).
     *
     * @param string|null $shipId Ship identifier (e.g., 'ship_arg_s_fighter_01_a_macro')
     * @return array{
     *     shipDef: \Mistralys\X4\Database\Ships\ShipDef|null,
     *     originalDrag: Drag,
     *     originalInertia: Inertia,
     *     originalJerk: Jerk
     * }
     * @throws GUIException If ship not found or data invalid
     * @since 1.2.0
     */
    private function loadShipPhysicsData(?string $shipId): array
    {
        $shipDef = null;

        // Load real ship data if shipId provided, otherwise use hardcoded defaults
        if ($shipId !== null) {
            $shipDef = ShipDefs::getInstance()->getByID($shipId);
            
            // Create drag from real ship data
            $originalDrag = new Drag(
                $shipDef->getDragForward(),
                $shipDef->getDragReverse(),
                $shipDef->getDragHorizontal(),
                $shipDef->getDragVertical(),
                $shipDef->getDragPitch(),
                $shipDef->getDragYaw(),
                $shipDef->getDragRoll()
            );
            
            // Create inertia from real ship data
            $originalInertia = new Inertia(
                $shipDef->getInertiaPitch(),
                $shipDef->getInertiaYaw(),
                $shipDef->getInertiaRoll()
            );
            
            // Create jerk from real ship data
            $originalJerk = new Jerk(
                $shipDef->getJerkStrafe(),
                $shipDef->getJerkAngular(),
                new JerkForward(
                    $shipDef->getJerkForwardAccel(),
                    $shipDef->getJerkForwardDecel(),
                    $shipDef->getJerkForwardRatio()
                ),
                new JerkBoost(
                    $shipDef->getJerkBoostAccel(),
                    $shipDef->getJerkBoostRatio()
                ),
                new JerkTravel(
                    $shipDef->getJerkTravelAccel(),
                    $shipDef->getJerkTravelDecel(),
                    $shipDef->getJerkTravelRatio()
                )
            );
        } else {
            // Backward compatibility: use hardcoded defaults when no shipId provided
            $originalDrag = new Drag(
                100.0,  // forward
                100.0,  // reverse
                100.0,  // horizontal
                100.0,  // vertical
                100.0,  // pitch
                100.0,  // yaw
                100.0   // roll
            );
            
            $originalInertia = new Inertia(10.0, 10.0, 10.0);  // pitch, yaw, roll
            
            $originalJerk = new Jerk(
                50.0,  // strafe
                50.0,  // angular
                new JerkForward(50.0, 50.0, 1.0),  // forward (accel, decel, ratio)
                new JerkBoost(100.0, 1.0),  // boost (accel, ratio)
                new JerkTravel(200.0, 200.0, 1.0)  // travel (accel, decel, ratio)
            );
        }

        return [
            'shipDef' => $shipDef,
            'originalDrag' => $originalDrag,
            'originalInertia' => $originalInertia,
            'originalJerk' => $originalJerk
        ];
    }
}
