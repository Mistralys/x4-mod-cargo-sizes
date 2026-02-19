<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Internal DTO representing a single ship's computed metrics within a class-range calculation.
 *
 * Replaces the raw associative array previously returned by
 * {@see \Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ClassRangeService::calculateShipMetrics()},
 * providing type safety and removing verbose docblock type annotations from all
 * methods that pass the data downstream.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ShipMetricsRow
{
    /**
     * @param string $shipId Ship identifier
     * @param string $shipName Ship display name
     * @param string $size Ship size class (xs, s, m, l, xl)
     * @param float $massRatio Computed mass ratio (adjustedFullMass / originalFullMass)
     * @param array{original: float, adjusted: float}|null $topSpeed Top speed in m/s, or null when no engine selected
     * @param array{original: float, adjusted: float}|null $acceleration Acceleration in m/s², or null when no engine selected
     */
    public function __construct(
        public readonly string $shipId,
        public readonly string $shipName,
        public readonly string $size,
        public readonly float $massRatio,
        public readonly ?array $topSpeed,
        public readonly ?array $acceleration
    ) {}
}
