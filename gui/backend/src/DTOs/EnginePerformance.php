<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Engine performance metrics (TWR, acceleration estimates).
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
     */
    public function __construct(
        public string $engineId,
        public float $thrustForward,
        public float $originalTWR,
        public float $adjustedTWR,
        public float $twrReductionPercent,
        public float $originalAcceleration,
        public float $adjustedAcceleration
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
            'adjustedAcceleration' => $this->adjustedAcceleration
        ];
    }
}