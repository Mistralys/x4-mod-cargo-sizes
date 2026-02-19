<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ClassRangeRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ClassRangeResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\RangeMetric;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipMetricSummary;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Database\Ships\ShipDefs;
use Mistralys\X4\Database\Engines\EngineDefs;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Utils\PhysicsCalculationHelper;

/**
 * Class-range calculation service.
 * Computes physics impact ranges (min/max/median) across all ships of a given type.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ClassRangeService
{
    use PhysicsCalculationHelper;
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
            // Get all ships of the requested type
            $ships = $this->shipDataService->getShipsByType($request->shipType);
            
            if (empty($ships)) {
                throw new GUIException(
                    sprintf('No ships found for type: %s', $request->shipType),
                    '',
                    GUIException::ERROR_UNHANDLED_SHIP_TYPE
                );
            }

            // Find applicable tiers
            $dragTier = $this->findTierForMultiplier($request->dragReductionTiers, $request->cargoMultiplier);
            $jerkTier = $this->findTierForMultiplier($request->jerkReductionTiers, $request->cargoMultiplier);

            // Load engine data if engine selected
            $engineDef = null;
            if ($request->engineId !== null) {
                $engineDef = EngineDefs::getInstance()->getByID($request->engineId);
            }

            // Collect metrics for all ships
            $shipMetrics = [];
            foreach ($ships as $ship) {
                $metrics = $this->calculateShipMetrics(
                    $ship,
                    $request,
                    $dragTier,
                    $jerkTier,
                    $engineDef
                );
                
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

            // Compute ranges
            $ranges = $this->computeRanges($shipMetrics, $request->engineId !== null);

            // Identify worst-case (highest mass ratio) and best-case (lowest mass ratio)
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
     * @param ReductionTier $dragTier
     * @param ReductionTier $jerkTier
     * @param \Mistralys\X4\Database\Engines\EngineDef|null $engineDef
     * @return array{shipId: string, shipName: string, size: string, massRatio: float, topSpeed: ?array, acceleration: ?array, dragChangePercent: float, metrics: array}|null
     */
    private function calculateShipMetrics(
        array $ship,
        ClassRangeRequest $request,
        ReductionTier $dragTier,
        ReductionTier $jerkTier,
        ?\Mistralys\X4\Database\Engines\EngineDef $engineDef
    ): ?array
    {
        try {
            $shipDef = ShipDefs::getInstance()->getByID($ship['id']);
            
            // Skip ships with zero cargo (avoid division by zero)
            $originalCargo = $shipDef->getCargoCapacity();
            if ($originalCargo <= 0) {
                $originalCargo = (float)$ship['cargo']; // Use fallback from ShipDataService
                if ($originalCargo <= 0) {
                    return null; // Skip this ship
                }
            }
            
            $adjustedCargo = $originalCargo * $request->cargoMultiplier;
            $baseMass = $shipDef->getMass();

            // Create physics calculator
            $calculator = new PhysicsCalculator(
                $baseMass,
                $originalCargo,
                $adjustedCargo,
                $request->cargoMultiplier,
                $request->useEffectiveRatioCap
            );

            // Get real drag values
            $originalDrag = new Drag(
                $shipDef->getDragForward(),
                $shipDef->getDragReverse(),
                $shipDef->getDragHorizontal(),
                $shipDef->getDragVertical(),
                $shipDef->getDragPitch(),
                $shipDef->getDragYaw(),
                $shipDef->getDragRoll()
            );

            // Apply drag reduction
            $adjustedDrag = new AdjustedDrag($originalDrag, $dragTier->getReductionPercent());

            // Calculate average drag change percent
            $dragChangePercent = $this->calculateAverageDragChange($originalDrag, $adjustedDrag);

            // Get real inertia values
            $originalInertia = new Inertia(
                $shipDef->getInertiaPitch(),
                $shipDef->getInertiaYaw(),
                $shipDef->getInertiaRoll()
            );

            // Apply inertia adjustment
            $inertiaMultiplier = 1.0 + (($calculator->getMassRatio() - 1.0) * $request->inertiaImpactFactor);
            $adjustedInertia = new AdjustedInertia($originalInertia, $inertiaMultiplier);

            // Calculate average inertia change percent
            $inertiaChangePercent = $this->calculateAverageInertiaChange($originalInertia, $adjustedInertia);

            // Calculate engine-dependent metrics if engine selected
            $topSpeed = null;
            $acceleration = null;
            
            if ($engineDef !== null) {
                $engineCount = $shipDef->countEngines();
                $thrustForward = $engineDef->getThrustForward();
                $totalThrust = $thrustForward * $engineCount;
                $dragForward = $shipDef->getDragForward();
                
                if ($dragForward > 0) {
                    // X4 top speed formula: v_max = thrust_kN / drag_coefficient
                    $topSpeedOriginal = $totalThrust / $dragForward;
                    // Adjusted top speed uses reduced drag (mod reduces drag to compensate mass)
                    $adjustedDragFwd = $adjustedDrag->getForward();
                    $topSpeedAdjustedValue = ($adjustedDragFwd > 0)
                        ? $totalThrust / $adjustedDragFwd
                        : $topSpeedOriginal;
                    $topSpeed = [
                        'original' => $topSpeedOriginal,
                        'adjusted' => $topSpeedAdjustedValue
                    ];
                }
                
                $thrustNewtons = $totalThrust * 1000.0;
                $originalAccel = $thrustNewtons / $calculator->getOriginalFullMass();
                $rawAdjustedAccel = $thrustNewtons / $calculator->getAdjustedFullMass();

                // Apply acceleration compensation factor (0.0 = no help, 1.0 = fully restore original)
                $accelFactor = $request->accelerationResponsiveness;
                $compensatedAccel = $rawAdjustedAccel + $accelFactor * ($originalAccel - $rawAdjustedAccel);

                $acceleration = [
                    'original' => $originalAccel,
                    'adjusted' => $compensatedAccel
                ];
            }

            return [
                'shipId' => $ship['id'],
                'shipName' => $ship['name'],
                'size' => $ship['size'],
                'massRatio' => $calculator->getMassRatio(),
                'topSpeed' => $topSpeed,
                'acceleration' => $acceleration,
                'dragChangePercent' => $dragChangePercent,
                'metrics' => [
                    'massRatio' => $calculator->getMassRatio(),
                    'dragChangePercent' => $dragChangePercent,
                    'inertiaChangePercent' => $inertiaChangePercent
                ]
            ];
        } catch (\Exception $e) {
            // Skip ships that fail to calculate (e.g., missing engine data)
            return null;
        }
    }

    /**
     * Computes min/max/median ranges for all metrics.
     *
     * @param array<array{shipId: string, shipName: string, size: string, massRatio: float, topSpeed: ?array, acceleration: ?array, dragChangePercent: float, metrics: array}> $shipMetrics
     * @param bool $hasEngine
     * @return array<string, RangeMetric>
     */
    private function computeRanges(array $shipMetrics, bool $hasEngine): array
    {
        $ranges = [];

        // Extract value arrays for each metric
        $massRatios = array_column($shipMetrics, 'massRatio');
        $dragChanges = [];
        $inertiaChanges = [];
        $topSpeedsOriginal = [];
        $topSpeedsAdjusted = [];
        $accelerationsOriginal = [];
        $accelerationsAdjusted = [];

        foreach ($shipMetrics as $metric) {
            $dragChanges[] = $metric['metrics']['dragChangePercent'];
            $inertiaChanges[] = $metric['metrics']['inertiaChangePercent'];
            
            if ($hasEngine && $metric['topSpeed'] !== null) {
                $topSpeedsOriginal[] = $metric['topSpeed']['original'];
                $topSpeedsAdjusted[] = $metric['topSpeed']['adjusted'];
            }
            
            if ($hasEngine && $metric['acceleration'] !== null) {
                $accelerationsOriginal[] = $metric['acceleration']['original'];
                $accelerationsAdjusted[] = $metric['acceleration']['adjusted'];
            }
        }

        // Compute ranges for each metric
        $ranges['massRatio'] = new RangeMetric(
            min: min($massRatios),
            max: max($massRatios),
            median: $this->computeMedian($massRatios),
            unit: 'ratio',
            label: 'Mass Ratio'
        );

        $ranges['dragChange'] = new RangeMetric(
            min: min($dragChanges),
            max: max($dragChanges),
            median: $this->computeMedian($dragChanges),
            unit: '%',
            label: 'Drag Change'
        );

        $ranges['inertiaChange'] = new RangeMetric(
            min: min($inertiaChanges),
            max: max($inertiaChanges),
            median: $this->computeMedian($inertiaChanges),
            unit: '%',
            label: 'Inertia Change'
        );

        // Add engine-dependent metrics if available
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
     * @param array<array{shipId: string, shipName: string, size: string, massRatio: float, topSpeed: ?array, acceleration: ?array, dragChangePercent: float}> $shipMetrics
     * @return ShipMetricSummary
     */
    private function findWorstCase(array $shipMetrics): ShipMetricSummary
    {
        $worstShip = null;
        $maxMassRatio = 0.0;

        foreach ($shipMetrics as $metric) {
            if ($metric['massRatio'] > $maxMassRatio) {
                $maxMassRatio = $metric['massRatio'];
                $worstShip = $metric;
            }
        }

        return new ShipMetricSummary(
            shipId: $worstShip['shipId'],
            shipName: $worstShip['shipName'],
            size: $worstShip['size'],
            massRatio: $worstShip['massRatio'],
            topSpeed: $worstShip['topSpeed'],
            acceleration: $worstShip['acceleration'],
            dragChangePercent: $worstShip['dragChangePercent']
        );
    }

    /**
     * Finds the best-case ship (lowest mass ratio).
     *
     * @param array<array{shipId: string, shipName: string, size: string, massRatio: float, topSpeed: ?array, acceleration: ?array, dragChangePercent: float}> $shipMetrics
     * @return ShipMetricSummary
     */
    private function findBestCase(array $shipMetrics): ShipMetricSummary
    {
        $bestShip = null;
        $minMassRatio = PHP_FLOAT_MAX;

        foreach ($shipMetrics as $metric) {
            if ($metric['massRatio'] < $minMassRatio) {
                $minMassRatio = $metric['massRatio'];
                $bestShip = $metric;
            }
        }

        return new ShipMetricSummary(
            shipId: $bestShip['shipId'],
            shipName: $bestShip['shipName'],
            size: $bestShip['size'],
            massRatio: $bestShip['massRatio'],
            topSpeed: $bestShip['topSpeed'],
            acceleration: $bestShip['acceleration'],
            dragChangePercent: $bestShip['dragChangePercent']
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

        // Median calculation using sort() - O(n log n) complexity
        // Current dataset: ~80 ships per type (~0.5ms overhead)
        // Acceptable for datasets <1000 items per constraints.md
        // For datasets >1000 items, implement quickselect algorithm (O(n) average case)
        // Reference: https://en.wikipedia.org/wiki/Quickselect
        sort($values);
        $count = count($values);
        $middle = (int)floor($count / 2);

        if ($count % 2 === 0) {
            // Even number: average of two middle values
            return ($values[$middle - 1] + $values[$middle]) / 2.0;
        } else {
            // Odd number: middle value
            return $values[$middle];
        }
    }
}

