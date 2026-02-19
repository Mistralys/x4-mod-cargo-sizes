<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Output contract for physics calculations.
 *
 * Contains mass ratio metrics, acceleration scaling factor, and optional
 * engine performance data. Drag, inertia, and jerk are no longer included
 * because only acceleration is overridden in the new mod design.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsResponse
{
    /**
     * @param float $massRatio Mass ratio (adjustedFullMass / originalFullMass)
     * @param float $originalFullMass Original full mass
     * @param float $adjustedFullMass Adjusted full mass
     * @param float $massIncrease Mass increase amount
     * @param float $originalCargo Original cargo capacity
     * @param float $adjustedCargo Adjusted cargo capacity
     * @param float $accelerationScalingFactor Computed scaling: massRatio × responsiveness
     * @param float $accelerationResponsiveness Responsiveness input (1.0 = preserve feel)
     * @param EnginePerformance|null $enginePerformance Optional engine performance metrics
     * @param float|null $topSpeedOriginal Original top speed in m/s (null if no engine)
     * @param float|null $topSpeedAdjusted Adjusted top speed in m/s (null if no engine)
     * @param float|null $accelerationOriginal Original acceleration in m/s² (null if no engine)
     * @param float|null $accelerationAdjusted Adjusted acceleration in m/s² (null if no engine)
     */
    public function __construct(
        public readonly float $massRatio,
        public readonly float $originalFullMass,
        public readonly float $adjustedFullMass,
        public readonly float $massIncrease,
        public readonly float $originalCargo,
        public readonly float $adjustedCargo,
        public readonly float $accelerationScalingFactor,
        public readonly float $accelerationResponsiveness,
        public readonly ?EnginePerformance $enginePerformance = null,
        public readonly ?float $topSpeedOriginal = null,
        public readonly ?float $topSpeedAdjusted = null,
        public readonly ?float $accelerationOriginal = null,
        public readonly ?float $accelerationAdjusted = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'massRatio' => $this->massRatio,
            'originalFullMass' => $this->originalFullMass,
            'adjustedFullMass' => $this->adjustedFullMass,
            'massIncrease' => $this->massIncrease,
            'originalCargo' => $this->originalCargo,
            'adjustedCargo' => $this->adjustedCargo,
            'accelerationScalingFactor' => $this->accelerationScalingFactor,
            'accelerationResponsiveness' => $this->accelerationResponsiveness,
        ];

        if ($this->enginePerformance !== null) {
            $data['enginePerformance'] = $this->enginePerformance->toArray();
        }

        if ($this->topSpeedOriginal !== null) {
            $data['topSpeed'] = [
                'original' => $this->topSpeedOriginal,
                'adjusted' => $this->topSpeedAdjusted
            ];
        }

        if ($this->accelerationOriginal !== null) {
            $data['acceleration'] = [
                'original' => $this->accelerationOriginal,
                'adjusted' => $this->accelerationAdjusted
            ];
        }

        return $data;
    }
}