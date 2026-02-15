<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Represents a min/max/median range for a metric across a ship class.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class RangeMetric
{
    /**
     * @param float $min Minimum value in the class
     * @param float $max Maximum value in the class
     * @param float $median Median value in the class
     * @param string $unit Unit of measurement (e.g., "m/s", "m/s²", "%")
     * @param string $label Human-readable label (e.g., "Top Speed", "Acceleration")
     */
    public function __construct(
        public readonly float $min,
        public readonly float $max,
        public readonly float $median,
        public readonly string $unit,
        public readonly string $label
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
            'median' => $this->median,
            'unit' => $this->unit,
            'label' => $this->label
        ];
    }
}
