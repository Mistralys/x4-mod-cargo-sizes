<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ClassRangeRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ClassRangeResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\RangeMetric;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipMetricSummary;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipMetricsRow;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Database\Ships\ShipDefs;
use Mistralys\X4\Database\Engines\EngineDefs;

/**
 * Class-range calculation service.
 *
 * Computes physics impact ranges (min/max/median) across all ships of a given type.
 * Only acceleration is modified by the mod; drag, inertia, and jerk are unchanged.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ClassRangeService
{
    public function __construct(
        private readonly ShipDataService $shipDataService
    ) {}

    /**
     * Calculates class-wide physics ranges for all ships of a given type.
     *
     * @param ClassRangeRequest $request
     * @return ClassRangeResponse
     * @throws GUIException
     */
    public function calculateClassRange(ClassRangeRequest $request): ClassRangeResponse
    {
        try {
            $ships = $this->shipDataService->getShipsByType($request->shipType);

            if (empty($ships)) {
                throw new GUIException(
                    sprintf('No ships found for type: %s', $request->shipType),
                    '',
                    GUIException::ERROR_UNHANDLED_SHIP_TYPE
                );
            }

            // Load engine definition if engine selected
            $engineDef = null;
            if ($request->engineId !== null) {
                $engineDef = EngineDefs::getInstance()->getByID($request->engineId);
            }

            // Collect metrics for all ships
            /** @var ShipMetricsRow[] $shipMetrics */
            $shipMetrics = [];
            foreach ($ships as $ship) {
                $metrics = $this->calculateShipMetrics($ship, $request, $engineDef);

                if ($metrics !== null) {
                    $shipMetrics[] = $metrics;
                }
            }

            if (empty($shipMetrics)) {
                throw new GUIException(
                    'No valid ship metrics calculated',
                    '',
                    GUIException::ERROR_UNHANDLED_SHIP_TYPE
                );
            }

            $ranges = $this->computeRanges($shipMetrics, $request->engineId !== null);
            $worstCase = $this->findWorstCase($shipMetrics);
            $bestCase = $this->findBestCase($shipMetrics);

            return new ClassRangeResponse(
                shipCount: count($shipMetrics),
                metrics: $ranges,
                worstCase: $worstCase,
                bestCase: $bestCase
            );
        } catch (\Exception $e) {
            throw new GUIException(
                'Class-range calculation failed: ' . $e->getMessage(),
                '',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Calculates metrics for a single ship.
     *
     * @param array{id: string, name: string, size: string, mass: float, cargo: float} $ship
     * @param ClassRangeRequest $request
     * @param \Mistralys\X4\Database\Engines\EngineDef|null $engineDef
     * @return ShipMetricsRow|null
     */
    private function calculateShipMetrics(
        array $ship,
        ClassRangeRequest $request,
        ?\Mistralys\X4\Database\Engines\EngineDef $engineDef
    ): ?ShipMetricsRow {
        try {
            $shipDef = ShipDefs::getInstance()->getByID($ship['id']);

            // Skip ships with zero cargo (avoid division by zero)
            $originalCargo = $shipDef->getCargoCapacity();
            if ($originalCargo <= 0) {
                $originalCargo = (float)$ship['cargo'];
                if ($originalCargo <= 0) {
                    return null;
                }
            }

            $adjustedCargo = $originalCargo * $request->cargoMultiplier;
            $baseMass = $shipDef->getMass();

            $calculator = new PhysicsCalculator(
                $baseMass,
                $originalCargo,
                $adjustedCargo,
                $request->cargoMultiplier
            );

            // Calculate engine-dependent metrics if engine selected
            $topSpeed = null;
            $acceleration = null;

            if ($engineDef !== null) {
                $engineCount = $shipDef->countEngines();
                $thrustForward = $engineDef->getThrustForward();
                $totalThrust = $thrustForward * $engineCount;
                $dragForward = $shipDef->getDragForward();

                if ($dragForward > 0) {
                    // Drag is unchanged; top speed = thrust / drag (same original and adjusted)
                    $topSpeedValue = $totalThrust / $dragForward;
                    $topSpeed = [
                        'original' => $topSpeedValue,
                        'adjusted' => $topSpeedValue,
                    ];
                }

                $thrustNewtons = $totalThrust * 1000.0;
                $originalAccel = $thrustNewtons / $calculator->getOriginalFullMass();
                // AccelFactor override: adjusted = original × responsiveness
                $adjustedAccel = $originalAccel * $request->accelerationResponsiveness;

                $acceleration = [
                    'original' => $originalAccel,
                    'adjusted' => $adjustedAccel,
                ];
            }

            return new ShipMetricsRow(
                shipId: $ship['id'],
                shipName: $ship['name'],
                size: $ship['size'],
                massRatio: $calculator->getMassRatio(),
                topSpeed: $topSpeed,
                acceleration: $acceleration,
            );
        } catch (\Exception) {
            // Skip ships that fail to calculate (e.g., missing data)
            return null;
        }
    }

    /**
     * Computes min/max/median ranges for all metrics.
     *
     * @param ShipMetricsRow[] $shipMetrics
     * @param bool $hasEngine
     * @return array<string, RangeMetric>
     */
    private function computeRanges(array $shipMetrics, bool $hasEngine): array
    {
        $ranges = [];

        $massRatios = array_map(static fn(ShipMetricsRow $row) => $row->massRatio, $shipMetrics);
        $topSpeedsOriginal = [];
        $accelerationsOriginal = [];

        foreach ($shipMetrics as $metric) {
            if ($hasEngine && $metric->topSpeed !== null) {
                $topSpeedsOriginal[] = $metric->topSpeed['original'];
            }

            if ($hasEngine && $metric->acceleration !== null) {
                $accelerationsOriginal[] = $metric->acceleration['original'];
            }
        }

        $ranges['massRatio'] = new RangeMetric(
            min: min($massRatios),
            max: max($massRatios),
            median: $this->computeMedian($massRatios),
            unit: 'ratio',
            label: 'Mass Ratio'
        );

        if ($hasEngine && !empty($topSpeedsOriginal)) {
            $ranges['topSpeed'] = new RangeMetric(
                min: min($topSpeedsOriginal),
                max: max($topSpeedsOriginal),
                median: $this->computeMedian($topSpeedsOriginal),
                unit: 'm/s',
                label: 'Top Speed'
            );
        }

        if ($hasEngine && !empty($accelerationsOriginal)) {
            $ranges['acceleration'] = new RangeMetric(
                min: min($accelerationsOriginal),
                max: max($accelerationsOriginal),
                median: $this->computeMedian($accelerationsOriginal),
                unit: 'm/s²',
                label: 'Acceleration'
            );
        }

        return $ranges;
    }

    /**
     * Finds the worst-case ship (highest mass ratio).
     *
     * @param ShipMetricsRow[] $shipMetrics
     * @return ShipMetricSummary
     */
    private function findWorstCase(array $shipMetrics): ShipMetricSummary
    {
        $worstShip = null;
        $maxMassRatio = 0.0;

        foreach ($shipMetrics as $metric) {
            if ($metric->massRatio > $maxMassRatio) {
                $maxMassRatio = $metric->massRatio;
                $worstShip = $metric;
            }
        }

        return new ShipMetricSummary(
            shipId: $worstShip->shipId,
            shipName: $worstShip->shipName,
            size: $worstShip->size,
            massRatio: $worstShip->massRatio,
            topSpeed: $worstShip->topSpeed,
            acceleration: $worstShip->acceleration,
        );
    }

    /**
     * Finds the best-case ship (lowest mass ratio).
     *
     * @param ShipMetricsRow[] $shipMetrics
     * @return ShipMetricSummary
     */
    private function findBestCase(array $shipMetrics): ShipMetricSummary
    {
        $bestShip = null;
        $minMassRatio = PHP_FLOAT_MAX;

        foreach ($shipMetrics as $metric) {
            if ($metric->massRatio < $minMassRatio) {
                $minMassRatio = $metric->massRatio;
                $bestShip = $metric;
            }
        }

        return new ShipMetricSummary(
            shipId: $bestShip->shipId,
            shipName: $bestShip->shipName,
            size: $bestShip->size,
            massRatio: $bestShip->massRatio,
            topSpeed: $bestShip->topSpeed,
            acceleration: $bestShip->acceleration,
        );
    }

    /**
     * Computes the median of an array of values.
     *
     * @param array<float> $values
     * @return float
     */
    private function computeMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = (int)floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2.0;
        } else {
            return $values[$middle];
        }
    }
}

