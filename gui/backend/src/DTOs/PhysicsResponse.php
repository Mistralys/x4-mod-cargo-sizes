<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Output contract for physics calculations with all calculated values.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsResponse
{
    /**
     * @param float $massRatio Mass ratio (adjustedFullMass / originalFullMass)
     * @param float $effectiveRatio Effective ratio (capped if enabled)
     * @param float $originalFullMass Original full mass
     * @param float $adjustedFullMass Adjusted full mass
     * @param float $massIncrease Mass increase amount
     * @param float $originalCargo Original cargo capacity
     * @param float $adjustedCargo Adjusted cargo capacity
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragOriginal Original drag values
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragAdjusted Adjusted drag values
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragPercentChange Drag percentage changes
     * @param array{pitch: float, yaw: float, roll: float} $inertiaOriginal Original inertia values
     * @param array{pitch: float, yaw: float, roll: float} $inertiaAdjusted Adjusted inertia values
     * @param array{pitch: float, yaw: float, roll: float} $inertiaPercentChange Inertia percentage changes
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkOriginal Original jerk values
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkAdjusted Adjusted jerk values
     * @param array{forward: array{accel: float, decel: float}, boost: array{accel: float, decel: float}, travel: array{accel: float, decel: float}} $jerkPercentChange Jerk percentage changes
     * @param EnginePerformance|null $enginePerformance Optional engine performance metrics
     * @param string $activeTier Active tier description
     */
    public function __construct(
        public float $massRatio,
        public float $effectiveRatio,
        public float $originalFullMass,
        public float $adjustedFullMass,
        public float $massIncrease,
        public float $originalCargo,
        public float $adjustedCargo,
        public array $dragOriginal,
        public array $dragAdjusted,
        public array $dragPercentChange,
        public array $inertiaOriginal,
        public array $inertiaAdjusted,
        public array $inertiaPercentChange,
        public array $jerkOriginal,
        public array $jerkAdjusted,
        public array $jerkPercentChange,
        public ?EnginePerformance $enginePerformance = null,
        public string $activeTier = ''
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'massRatio' => $this->massRatio,
            'effectiveRatio' => $this->effectiveRatio,
            'originalFullMass' => $this->originalFullMass,
            'adjustedFullMass' => $this->adjustedFullMass,
            'massIncrease' => $this->massIncrease,
            'originalCargo' => $this->originalCargo,
            'adjustedCargo' => $this->adjustedCargo,
            'drag' => [
                'original' => $this->dragOriginal,
                'adjusted' => $this->dragAdjusted,
                'percentChange' => $this->dragPercentChange
            ],
            'inertia' => [
                'original' => $this->inertiaOriginal,
                'adjusted' => $this->inertiaAdjusted,
                'percentChange' => $this->inertiaPercentChange
            ],
            'jerk' => [
                'original' => $this->jerkOriginal,
                'adjusted' => $this->jerkAdjusted,
                'percentChange' => $this->jerkPercentChange
            ],
            'activeTier' => $this->activeTier
        ];

        if ($this->enginePerformance !== null) {
            $data['enginePerformance'] = $this->enginePerformance->toArray();
        }

        return $data;
    }
}