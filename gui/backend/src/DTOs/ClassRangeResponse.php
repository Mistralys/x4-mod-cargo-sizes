<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Output contract for class-range calculations.
 * Contains aggregated min/max/median metrics for all ships in a class.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ClassRangeResponse
{
    /**
     * @param int $shipCount Number of ships analyzed
     * @param array<string, RangeMetric> $metrics Map of metric name to RangeMetric object
     * @param ShipMetricSummary $worstCase Ship with highest mass ratio (worst impact)
     * @param ShipMetricSummary $bestCase Ship with lowest mass ratio (best case)
     */
    public function __construct(
        public readonly int $shipCount,
        public readonly array $metrics,
        public readonly ShipMetricSummary $worstCase,
        public readonly ShipMetricSummary $bestCase
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metricsArray = [];
        foreach ($this->metrics as $key => $metric) {
            $metricsArray[$key] = $metric->toArray();
        }

        return [
            'shipCount' => $this->shipCount,
            'metrics' => $metricsArray,
            'worstCase' => $this->worstCase->toArray(),
            'bestCase' => $this->bestCase->toArray()
        ];
    }
}
