<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponseData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ReductionTiers;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipDetails;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Database\Engines\EngineDefs;
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
            $shipDetails = $shipData['shipDetails'];

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
                if ($shipDetails !== null) {
                    $engineCount = $shipDetails->engineCount;
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

            $responseData = new PhysicsResponseData(
                $calculator,
                $physicsData,
                $tiers,
                $request,
                $enginePerformance
            );

            return $this->buildPhysicsResponse($responseData);
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
            $engineDef = $this->engineDefs->getByID($engineId);
            
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
     * Uses Parameter Object pattern to reduce parameter count from 5 to 1,
     * improving maintainability and readability.
     *
     * @param PhysicsResponseData $data All data needed to construct the response
     * @return PhysicsResponse Complete physics response DTO
     * @since 1.2.0 Method introduced
     * @since 1.3.0 Refactored to use parameter objects (PhysicsData, ReductionTiers, PhysicsResponseData)
     */
    private function buildPhysicsResponse(PhysicsResponseData $data): PhysicsResponse
    {
        // Extract engine performance metrics if available
        $topSpeedOriginal = null;
        $topSpeedAdjusted = null;
        $accelerationOriginal = null;
        $accelerationAdjusted = null;
        
        if ($data->enginePerformance !== null) {
            $topSpeedOriginal = $data->enginePerformance->topSpeed;
            $topSpeedAdjusted = $data->enginePerformance->topSpeedAdjusted;
            $accelerationOriginal = $data->enginePerformance->originalAcceleration;
            $accelerationAdjusted = $data->enginePerformance->adjustedAcceleration;
        }

        return new PhysicsResponse(
            massRatio: $data->calculator->getMassRatio(),
            effectiveRatio: $data->calculator->getEffectiveRatio(),
            originalFullMass: $data->calculator->getOriginalFullMass(),
            adjustedFullMass: $data->calculator->getAdjustedFullMass(),
            massIncrease: $data->calculator->getMassIncrease(),
            originalCargo: $data->request->originalCargo,
            adjustedCargo: $data->request->adjustedCargo,
            dragOriginal: [
                'forward' => $data->physicsData->originalDrag->getForward(),
                'reverse' => $data->physicsData->originalDrag->getReverse(),
                'horizontal' => $data->physicsData->originalDrag->getHorizontal(),
                'vertical' => $data->physicsData->originalDrag->getVertical(),
                'pitch' => $data->physicsData->originalDrag->getPitch(),
                'yaw' => $data->physicsData->originalDrag->getYaw(),
                'roll' => $data->physicsData->originalDrag->getRoll()
            ],
            dragAdjusted: [
                'forward' => $data->physicsData->adjustedDrag->getForward(),
                'reverse' => $data->physicsData->adjustedDrag->getReverse(),
                'horizontal' => $data->physicsData->adjustedDrag->getHorizontal(),
                'vertical' => $data->physicsData->adjustedDrag->getVertical(),
                'pitch' => $data->physicsData->adjustedDrag->getPitch(),
                'yaw' => $data->physicsData->adjustedDrag->getYaw(),
                'roll' => $data->physicsData->adjustedDrag->getRoll()
            ],
            dragPercentChange: [
                'forward' => $this->calculatePercentChange($data->physicsData->originalDrag->getForward(), $data->physicsData->adjustedDrag->getForward()),
                'reverse' => $this->calculatePercentChange($data->physicsData->originalDrag->getReverse(), $data->physicsData->adjustedDrag->getReverse()),
                'horizontal' => $this->calculatePercentChange($data->physicsData->originalDrag->getHorizontal(), $data->physicsData->adjustedDrag->getHorizontal()),
                'vertical' => $this->calculatePercentChange($data->physicsData->originalDrag->getVertical(), $data->physicsData->adjustedDrag->getVertical()),
                'pitch' => $this->calculatePercentChange($data->physicsData->originalDrag->getPitch(), $data->physicsData->adjustedDrag->getPitch()),
                'yaw' => $this->calculatePercentChange($data->physicsData->originalDrag->getYaw(), $data->physicsData->adjustedDrag->getYaw()),
                'roll' => $this->calculatePercentChange($data->physicsData->originalDrag->getRoll(), $data->physicsData->adjustedDrag->getRoll())
            ],
            inertiaOriginal: [
                'pitch' => $data->physicsData->originalInertia->getPitch(),
                'yaw' => $data->physicsData->originalInertia->getYaw(),
                'roll' => $data->physicsData->originalInertia->getRoll()
            ],
            inertiaAdjusted: [
                'pitch' => $data->physicsData->adjustedInertia->getPitch(),
                'yaw' => $data->physicsData->adjustedInertia->getYaw(),
                'roll' => $data->physicsData->adjustedInertia->getRoll()
            ],
            inertiaPercentChange: [
                'pitch' => $this->calculatePercentChange($data->physicsData->originalInertia->getPitch(), $data->physicsData->adjustedInertia->getPitch()),
                'yaw' => $this->calculatePercentChange($data->physicsData->originalInertia->getYaw(), $data->physicsData->adjustedInertia->getYaw()),
                'roll' => $this->calculatePercentChange($data->physicsData->originalInertia->getRoll(), $data->physicsData->adjustedInertia->getRoll())
            ],
            jerkOriginal: [
                'forward' => ['accel' => $data->physicsData->originalJerk->getForward()->getAcceleration(), 'decel' => $data->physicsData->originalJerk->getForward()->getDeceleration()],
                'boost' => ['accel' => $data->physicsData->originalJerk->getBoost()->getAcceleration()],
                'travel' => ['accel' => $data->physicsData->originalJerk->getTravel()->getAcceleration(), 'decel' => $data->physicsData->originalJerk->getTravel()->getDeceleration()]
            ],
            jerkAdjusted: [
                'forward' => ['accel' => $data->physicsData->adjustedJerk->getForward()->getAcceleration(), 'decel' => $data->physicsData->adjustedJerk->getForward()->getDeceleration()],
                'boost' => ['accel' => $data->physicsData->adjustedJerk->getBoost()->getAcceleration()],
                'travel' => ['accel' => $data->physicsData->adjustedJerk->getTravel()->getAcceleration(), 'decel' => $data->physicsData->adjustedJerk->getTravel()->getDeceleration()]
            ],
            jerkPercentChange: [
                'forward' => [
                    'accel' => $this->calculatePercentChange($data->physicsData->originalJerk->getForward()->getAcceleration(), $data->physicsData->adjustedJerk->getForward()->getAcceleration()),
                    'decel' => $this->calculatePercentChange($data->physicsData->originalJerk->getForward()->getDeceleration(), $data->physicsData->adjustedJerk->getForward()->getDeceleration())
                ],
                'boost' => [
                    'accel' => $this->calculatePercentChange($data->physicsData->originalJerk->getBoost()->getAcceleration(), $data->physicsData->adjustedJerk->getBoost()->getAcceleration())
                ],
                'travel' => [
                    'accel' => $this->calculatePercentChange($data->physicsData->originalJerk->getTravel()->getAcceleration(), $data->physicsData->adjustedJerk->getTravel()->getAcceleration()),
                    'decel' => $this->calculatePercentChange($data->physicsData->originalJerk->getTravel()->getDeceleration(), $data->physicsData->adjustedJerk->getTravel()->getDeceleration())
                ]
            ],
            enginePerformance: $data->enginePerformance,
            activeTier: $data->tiers->getActiveTierLabel(),
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
     *     shipDetails: \Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipDetails|null,
     *     originalDrag: Drag,
     *     originalInertia: Inertia,
     *     originalJerk: Jerk
     * }
     * @throws GUIException If ship not found or data invalid
     * @since 1.2.0
     */
    private function loadShipPhysicsData(?string $shipId): array
    {
        $shipDetails = null;

        // Load real ship data if shipId provided, otherwise use hardcoded defaults
        if ($shipId !== null) {
            $shipDetails = $this->shipDataService->getShipDetails($shipId);

            // Create drag from real ship data
            $originalDrag = new Drag(
                $shipDetails->dragOriginal['forward'],
                $shipDetails->dragOriginal['reverse'],
                $shipDetails->dragOriginal['horizontal'],
                $shipDetails->dragOriginal['vertical'],
                $shipDetails->dragOriginal['pitch'],
                $shipDetails->dragOriginal['yaw'],
                $shipDetails->dragOriginal['roll']
            );

            // Create inertia from real ship data
            $originalInertia = new Inertia(
                $shipDetails->inertiaOriginal['pitch'],
                $shipDetails->inertiaOriginal['yaw'],
                $shipDetails->inertiaOriginal['roll']
            );

            // Create jerk from real ship data
            $jerk = $shipDetails->jerkOriginal;
            $originalJerk = new Jerk(
                $jerk['strafe'],
                $jerk['angular'],
                new JerkForward(
                    $jerk['forwardAccel'],
                    $jerk['forwardDecel'],
                    $jerk['forwardRatio']
                ),
                new JerkBoost(
                    $jerk['boostAccel'],
                    $jerk['boostRatio']
                ),
                new JerkTravel(
                    $jerk['travelAccel'],
                    $jerk['travelDecel'],
                    $jerk['travelRatio']
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
            'shipDetails' => $shipDetails,
            'originalDrag' => $originalDrag,
            'originalInertia' => $originalInertia,
            'originalJerk' => $originalJerk
        ];
    }
}
