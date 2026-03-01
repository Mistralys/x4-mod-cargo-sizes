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
     * @param float $accelerationResponsiveness Responsiveness factor (1.0 = preserve original feel)
     * @param string|null $engineId Optional engine ID for engine-dependent metrics (topSpeed, acceleration)
     */
    public function __construct(
        public readonly string $shipType,
        public readonly float $cargoMultiplier,
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
            (float)($data['accelerationResponsiveness'] ?? 1.0),
            $data['engineId'] ?? null
        );
    }
}
