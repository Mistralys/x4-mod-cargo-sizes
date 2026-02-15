<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Engine performance metrics (TWR, acceleration estimates, top speeds).
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class EnginePerformance
{
    /**
     * @param string $engineId Engine identifier
     * @param float $thrustForward Forward thrust in kN
     * @param float $originalTWR Original thrust-to-weight ratio
     * @param float $adjustedTWR Adjusted thrust-to-weight ratio after mass increase
     * @param float $twrReductionPercent Percentage reduction in TWR
     * @param float $originalAcceleration Original estimated acceleration in m/s²
     * @param float $adjustedAcceleration Adjusted estimated acceleration in m/s²
     * @param int $engineCount Number of engines used for calculations
     * @param float|null $topSpeed Top speed with forward thrust (original)
     * @param float|null $topSpeedAdjusted Top speed with forward thrust (adjusted)
     * @param float|null $topSpeedReverse Top speed in reverse (original)
     * @param float|null $topSpeedBoost Top speed with boost (original)
     * @param float|null $topSpeedTravel Top speed in travel mode (original)
     */
    public function __construct(
        public string $engineId,
        public float $thrustForward,
        public float $originalTWR,
        public float $adjustedTWR,
        public float $twrReductionPercent,
        public float $originalAcceleration,
        public float $adjustedAcceleration,
        public readonly int $engineCount = 1,
        public readonly ?float $topSpeed = null,
        public readonly ?float $topSpeedAdjusted = null,
        public readonly ?float $topSpeedReverse = null,
        public readonly ?float $topSpeedBoost = null,
        public readonly ?float $topSpeedTravel = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'engineId' => $this->engineId,
            'thrustForward' => $this->thrustForward,
            'originalTWR' => $this->originalTWR,
            'adjustedTWR' => $this->adjustedTWR,
            'twrReductionPercent' => $this->twrReductionPercent,
            'originalAcceleration' => $this->originalAcceleration,
            'adjustedAcceleration' => $this->adjustedAcceleration,
            'engineCount' => $this->engineCount,
            'topSpeed' => $this->topSpeed,
            'topSpeedAdjusted' => $this->topSpeedAdjusted,
            'topSpeedReverse' => $this->topSpeedReverse,
            'topSpeedBoost' => $this->topSpeedBoost,
            'topSpeedTravel' => $this->topSpeedTravel
        ];
    }
}