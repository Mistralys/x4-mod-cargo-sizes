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
     * @param float $accelerationResponsiveness Responsiveness factor (1.0 = preserve original feel, 0.7 = heavier)
     * @param string|null $engineId Optional engine ID for performance calculations
     * @param string|null $shipId Optional ship ID to load real physics data from x4-core
     */
    public function __construct(
        public readonly float $baseMass,
        public readonly float $originalCargo,
        public readonly float $adjustedCargo,
        public readonly float $cargoMultiplier,
        public readonly float $accelerationResponsiveness,
        public readonly ?string $engineId = null,
        public readonly ?string $shipId = null
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
            (float)($data['accelerationResponsiveness'] ?? 1.0),
            $data['engineId'] ?? null,
            $data['shipId'] ?? null
        );
    }
}