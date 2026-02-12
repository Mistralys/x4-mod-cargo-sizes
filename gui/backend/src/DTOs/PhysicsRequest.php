<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Input contract for physics calculations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsRequest
{
    /**
     * @param float $baseMass Ship mass without cargo
     * @param float $originalCargo Original cargo capacity
     * @param float $adjustedCargo Adjusted cargo capacity
     * @param float $cargoMultiplier Cargo multiplier (2x, 4x, 8x, etc.)
     * @param bool $useEffectiveRatioCap Whether to cap effective ratio
     * @param float $dragReductionFactor Drag reduction factor config
     * @param float $inertiaImpactFactor Inertia impact factor config
     * @param float $accelerationResponsiveness Acceleration responsiveness config
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $dragReductionTiers Drag reduction tiers
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $jerkReductionTiers Jerk reduction tiers
     * @param string|null $engineId Optional engine ID for performance calculations
     */
    public function __construct(
        public float $baseMass,
        public float $originalCargo,
        public float $adjustedCargo,
        public float $cargoMultiplier,
        public bool $useEffectiveRatioCap,
        public float $dragReductionFactor,
        public float $inertiaImpactFactor,
        public float $accelerationResponsiveness,
        public array $dragReductionTiers,
        public array $jerkReductionTiers,
        public ?string $engineId = null
    ) {}

    /**
     * Create from array (typically from JSON request).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (float)($data['baseMass'] ?? 0.0),
            (float)($data['originalCargo'] ?? 0.0),
            (float)($data['adjustedCargo'] ?? 0.0),
            (float)($data['cargoMultiplier'] ?? 1.0),
            (bool)($data['useEffectiveRatioCap'] ?? true),
            (float)($data['dragReductionFactor'] ?? 1.0),
            (float)($data['inertiaImpactFactor'] ?? 0.5),
            (float)($data['accelerationResponsiveness'] ?? 1.0),
            $data['dragReductionTiers'] ?? [],
            $data['jerkReductionTiers'] ?? [],
            $data['engineId'] ?? null
        );
    }
}