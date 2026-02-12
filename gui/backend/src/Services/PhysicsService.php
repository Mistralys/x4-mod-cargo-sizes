<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
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

/**
 * Physics calculation service wrapping PhysicsCalculator.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsService
{
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

            // Create sample original drag values (API user will provide real ones later)
            $originalDrag = new Drag(
                100.0,  // forward
                100.0,  // reverse
                100.0,  // horizontal
                100.0,  // vertical
                100.0,  // pitch
                100.0,  // yaw
                100.0   // roll
            );

            // Apply drag reduction
            $adjustedDrag = new AdjustedDrag($originalDrag, $dragTier->getReductionPercent());

            // Create sample original inertia values
            $originalInertia = new Inertia(10.0, 10.0, 10.0);  // pitch, yaw, roll

            // Apply inertia adjustment (increases with mass)
            $inertiaMultiplier = 1.0 + (($calculator->getMassRatio() - 1.0) * $request->inertiaImpactFactor);
            $adjustedInertia = new AdjustedInertia($originalInertia, $inertiaMultiplier);

            // Create sample original jerk values
            $originalJerk = new Jerk(
                50.0,  // strafe
                50.0,  // angular
                new JerkForward(50.0, 50.0, 1.0),  // forward (accel, decel, ratio)
                new JerkBoost(100.0, 1.0),  // boost (accel, ratio)
                new JerkTravel(200.0, 200.0, 1.0)  // travel (accel, decel, ratio)
            );

            // Apply jerk reduction
            $jerkMultiplier = $calculator->getInverseMassRatio() * (1.0 - $jerkTier->getReductionPercent());
            $adjustedJerk = new AdjustedJerk($originalJerk, $jerkMultiplier);

            // Calculate engine performance if engine ID provided
            $enginePerformance = null;
            if ($request->engineId !== null) {
                $enginePerformance = $this->calculateEnginePerformance(
                    $request->engineId,
                    $calculator->getOriginalFullMass(),
                    $calculator->getAdjustedFullMass()
                );
            }

            // Build response
            return new PhysicsResponse(
                massRatio: $calculator->getMassRatio(),
                effectiveRatio: $calculator->getEffectiveRatio(),
                originalFullMass: $calculator->getOriginalFullMass(),
                adjustedFullMass: $calculator->getAdjustedFullMass(),
                massIncrease: $calculator->getMassIncrease(),
                dragOriginal: [
                    'forward' => $originalDrag->getForward(),
                    'reverse' => $originalDrag->getReverse(),
                    'horizontal' => $originalDrag->getHorizontal(),
                    'vertical' => $originalDrag->getVertical(),
                    'pitch' => $originalDrag->getPitch(),
                    'yaw' => $originalDrag->getYaw(),
                    'roll' => $originalDrag->getRoll()
                ],
                dragAdjusted: [
                    'forward' => $adjustedDrag->getForward(),
                    'reverse' => $adjustedDrag->getReverse(),
                    'horizontal' => $adjustedDrag->getHorizontal(),
                    'vertical' => $adjustedDrag->getVertical(),
                    'pitch' => $adjustedDrag->getPitch(),
                    'yaw' => $adjustedDrag->getYaw(),
                    'roll' => $adjustedDrag->getRoll()
                ],
                dragPercentChange: [
                    'forward' => $this->calculatePercentChange($originalDrag->getForward(), $adjustedDrag->getForward()),
                    'reverse' => $this->calculatePercentChange($originalDrag->getReverse(), $adjustedDrag->getReverse()),
                    'horizontal' => $this->calculatePercentChange($originalDrag->getHorizontal(), $adjustedDrag->getHorizontal()),
                    'vertical' => $this->calculatePercentChange($originalDrag->getVertical(), $adjustedDrag->getVertical()),
                    'pitch' => $this->calculatePercentChange($originalDrag->getPitch(), $adjustedDrag->getPitch()),
                    'yaw' => $this->calculatePercentChange($originalDrag->getYaw(), $adjustedDrag->getYaw()),
                    'roll' => $this->calculatePercentChange($originalDrag->getRoll(), $adjustedDrag->getRoll())
                ],
                inertiaOriginal: [
                    'pitch' => $originalInertia->getPitch(),
                    'yaw' => $originalInertia->getYaw(),
                    'roll' => $originalInertia->getRoll()
                ],
                inertiaAdjusted: [
                    'pitch' => $adjustedInertia->getPitch(),
                    'yaw' => $adjustedInertia->getYaw(),
                    'roll' => $adjustedInertia->getRoll()
                ],
                inertiaPercentChange: [
                    'pitch' => $this->calculatePercentChange($originalInertia->getPitch(), $adjustedInertia->getPitch()),
                    'yaw' => $this->calculatePercentChange($originalInertia->getYaw(), $adjustedInertia->getYaw()),
                    'roll' => $this->calculatePercentChange($originalInertia->getRoll(), $adjustedInertia->getRoll())
                ],
                jerkOriginal: [
                    'forward' => ['accel' => $originalJerk->getForward()->getAcceleration(), 'decel' => $originalJerk->getForward()->getDeceleration()],
                    'boost' => ['accel' => $originalJerk->getBoost()->getAcceleration()],  // Note: boost has no deceleration
                    'travel' => ['accel' => $originalJerk->getTravel()->getAcceleration(), 'decel' => $originalJerk->getTravel()->getDeceleration()]
                ],
                jerkAdjusted: [
                    'forward' => ['accel' => $adjustedJerk->getForward()->getAcceleration(), 'decel' => $adjustedJerk->getForward()->getDeceleration()],
                    'boost' => ['accel' => $adjustedJerk->getBoost()->getAcceleration()],  // Note: boost has no deceleration
                    'travel' => ['accel' => $adjustedJerk->getTravel()->getAcceleration(), 'decel' => $adjustedJerk->getTravel()->getDeceleration()]
                ],
                jerkPercentChange: [
                    'forward' => [
                        'accel' => $this->calculatePercentChange($originalJerk->getForward()->getAcceleration(), $adjustedJerk->getForward()->getAcceleration()),
                        'decel' => $this->calculatePercentChange($originalJerk->getForward()->getDeceleration(), $adjustedJerk->getForward()->getDeceleration())
                    ],
                    'boost' => [
                        'accel' => $this->calculatePercentChange($originalJerk->getBoost()->getAcceleration(), $adjustedJerk->getBoost()->getAcceleration())
                    ],
                    'travel' => [
                        'accel' => $this->calculatePercentChange($originalJerk->getTravel()->getAcceleration(), $adjustedJerk->getTravel()->getAcceleration()),
                        'decel' => $this->calculatePercentChange($originalJerk->getTravel()->getDeceleration(), $adjustedJerk->getTravel()->getDeceleration())
                    ]
                ],
                enginePerformance: $enginePerformance,
                activeTier: sprintf(
                    'Drag: %.0f%% reduction | Jerk: %.0f%% reduction',
                    $dragTier->getReductionPercent() * 100,
                    $jerkTier->getReductionPercent() * 100
                )
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
     * Calculates engine performance metrics.
     *
     * @param string $engineId Engine identifier
     * @param float $originalMass Original ship mass
     * @param float $adjustedMass Adjusted ship mass
     * @return EnginePerformance
     * @throws GUIException
     */
    private function calculateEnginePerformance(
        string $engineId,
        float $originalMass,
        float $adjustedMass
    ): EnginePerformance
    {
        try {
            $engineDef = EngineDefs::getInstance()->getByID($engineId);
            $thrustForward = $engineDef->getThrustForward();

            // Convert thrust from kN to N (1 kN = 1000 N)
            $thrustNewtons = $thrustForward * 1000.0;

            // Calculate TWR (Thrust-to-Weight Ratio)
            // Using Earth gravity (g = 9.81 m/s²) as reference
            $g = 9.81;
            $originalWeight = $originalMass * $g;
            $adjustedWeight = $adjustedMass * $g;

            $originalTWR = $thrustNewtons / $originalWeight;
            $adjustedTWR = $thrustNewtons / $adjustedWeight;

            $twrReductionPercent = (($originalTWR - $adjustedTWR) / $originalTWR) * 100.0;

            // Estimate acceleration (F = ma, so a = F/m)
            $originalAcceleration = $thrustNewtons / $originalMass;
            $adjustedAcceleration = $thrustNewtons / $adjustedMass;

            return new EnginePerformance(
                engineId: $engineId,
                thrustForward: $thrustForward,
                originalTWR: $originalTWR,
                adjustedTWR: $adjustedTWR,
                twrReductionPercent: $twrReductionPercent,
                originalAcceleration: $originalAcceleration,
                adjustedAcceleration: $adjustedAcceleration
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
     * Calculates percentage change between two values.
     *
     * @param float $original
     * @param float $adjusted
     * @return float Percentage change (negative for decrease, positive for increase)
     */
    private function calculatePercentChange(float $original, float $adjusted): float
    {
        if ($original == 0) {
            return 0.0;
        }
        return (($adjusted - $original) / $original) * 100.0;
    }
}
