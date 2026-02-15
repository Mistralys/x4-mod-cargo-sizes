<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Input contract for class-range calculations.
 * Requests physics range calculations for all ships of a given type.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ClassRangeRequest
{
    /**
     * @param string $shipType Ship type (transport, mining, auxiliary, carrier)
     * @param float $cargoMultiplier Cargo multiplier (2x, 4x, 8x, etc.)
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $dragReductionTiers Drag reduction tiers
     * @param array<array{maxMultiplier: float, reductionPercent: float}> $jerkReductionTiers Jerk reduction tiers
     * @param float $inertiaImpactFactor Inertia impact factor
     * @param bool $useEffectiveRatioCap Whether to cap effective ratio
     * @param float $dragReductionFactor Drag reduction factor config
     * @param float $accelerationResponsiveness Acceleration responsiveness config
     * @param string|null $engineId Optional engine ID for engine-dependent metrics (topSpeed, acceleration)
     */
    public function __construct(
        public readonly string $shipType,
        public readonly float $cargoMultiplier,
        public readonly array $dragReductionTiers,
        public readonly array $jerkReductionTiers,
        public readonly float $inertiaImpactFactor,
        public readonly bool $useEffectiveRatioCap,
        public readonly float $dragReductionFactor,
        public readonly float $accelerationResponsiveness,
        public readonly ?string $engineId = null
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
            (string)($data['shipType'] ?? 'transport'),
            (float)($data['cargoMultiplier'] ?? 1.0),
            $data['dragReductionTiers'] ?? [],
            $data['jerkReductionTiers'] ?? [],
            (float)($data['inertiaImpactFactor'] ?? 0.5),
            (bool)($data['useEffectiveRatioCap'] ?? true),
            (float)($data['dragReductionFactor'] ?? 1.0),
            (float)($data['accelerationResponsiveness'] ?? 1.0),
            $data['engineId'] ?? null
        );
    }
}
