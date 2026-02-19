<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Summary of a single ship's metrics for worst/best case identification.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ShipMetricSummary
{
    /**
     * @param string $shipId Ship identifier
     * @param string $shipName Ship name
     * @param string $size Ship size (xs, s, m, l, xl)
     * @param float $massRatio Mass ratio (adjustedFullMass / originalFullMass)
     * @param array{original: float, adjusted: float}|null $topSpeed Top speed values (null if no engine)
     * @param array{original: float, adjusted: float}|null $acceleration Acceleration values (null if no engine)
     */
    public function __construct(
        public readonly string $shipId,
        public readonly string $shipName,
        public readonly string $size,
        public readonly float $massRatio,
        public readonly ?array $topSpeed,
        public readonly ?array $acceleration
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'shipId' => $this->shipId,
            'shipName' => $this->shipName,
            'size' => $this->size,
            'massRatio' => $this->massRatio
        ];

        if ($this->topSpeed !== null) {
            $data['topSpeed'] = $this->topSpeed;
        }

        if ($this->acceleration !== null) {
            $data['acceleration'] = $this->acceleration;
        }

        return $data;
    }
}
