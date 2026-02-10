<?php
/**
 * @package Output
 * @subpackage Diagnostics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\FileHelper;
use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\ShipResult;
use function Mistralys\X4\dec;
use function Mistralys\X4\dec2;

/**
 * Diagnostics logger for physics calculations.
 *
 * Generates comprehensive reports showing:
 * - Ship-by-ship calculations
 * - Tier applications
 * - Configuration used
 * - Warnings for extreme cases
 *
 * **Purpose:** Transparency and debugging for users and developers.
 *
 * @package Output
 * @subpackage Diagnostics
 */
class DiagnosticsLogger
{
    /**
     * @var array<string,mixed>
     */
    private array $ships = [];

    /**
     * @var array<string,string[]>
     */
    private array $warnings = [];

    private string $buildDate;

    public function __construct()
    {
        $this->buildDate = date('Y-m-d H:i:s');
    }

    /**
     * Gets the number of ships logged.
     *
     * @return int Number of ships
     */
    public function getShipCount(): int
    {
        return count($this->ships);
    }

    /**
     * Logs calculations for a ship.
     *
     * @param ShipResult $ship Ship result
     * @param PhysicsCalculator $physics Physics calculations
     * @param ReductionTier $dragTier Drag reduction tier applied
     * @param ReductionTier $jerkTier Jerk reduction tier applied
     * @param BuildConfig $config Build configuration
     * @return void
     */
    public function logShip(
        ShipResult $ship,
        PhysicsCalculator $physics,
        ReductionTier $dragTier,
        ReductionTier $jerkTier,
        BuildConfig $config
    ): void
    {
        $shipName = $ship->getShipLabel();
        $shipID = $ship->getShipXMLFile()->getMacroName();
        $shipSize = $ship->getSize();
        $shipType = $ship->getShipType();

        $this->ships[$shipID] = [
            'name' => $shipName,
            'id' => $shipID,
            'size' => $shipSize,
            'type' => $shipType,
            'baseMass' => $physics->getBaseMass(),
            'originalCargo' => $physics->getBaseMass() === $physics->getOriginalFullMass()
                ? 0
                : $physics->getOriginalFullMass() - $physics->getBaseMass(),
            'adjustedCargo' => $physics->getAdjustedFullMass() - $physics->getBaseMass(),
            'originalFullMass' => $physics->getOriginalFullMass(),
            'adjustedFullMass' => $physics->getAdjustedFullMass(),
            'massRatio' => $physics->getMassRatio(),
            'cargoMultiplier' => $physics->getCargoMultiplier(),
            'effectiveRatio' => $physics->getEffectiveRatio(),
            'dragTierMaxMultiplier' => $dragTier->getMaxMultiplier(),
            'dragTierReduction' => $dragTier->getReductionPercent(),
            'jerkTierMaxMultiplier' => $jerkTier->getMaxMultiplier(),
            'jerkTierReduction' => $jerkTier->getReductionPercent(),
            'inertiaImpactFactor' => $config->getInertiaImpactFactor(),
            'accelerationResponsiveness' => $config->getAccelerationResponsiveness(),
            'effectiveRatioCap' => $config->getUseEffectiveRatioCap()
        ];

        // Check for warnings
        $this->checkWarnings($shipID, $shipName, $physics);
    }

    /**
     * Checks for and logs warnings for extreme cases.
     *
     * @param string $shipID Ship ID
     * @param string $shipName Ship name
     * @param PhysicsCalculator $physics Physics calculations
     * @return void
     */
    private function checkWarnings(string $shipID, string $shipName, PhysicsCalculator $physics): void
    {
        // High mass ratio (>5.0x)
        if ($physics->getMassRatio() > 5.0) {
            $this->addWarning(
                $shipID,
                sprintf(
                    'High mass ratio (%.2fx) - test carefully in-game',
                    $physics->getMassRatio()
                )
            );
        }

        // Extreme mass ratio (>10.0x)
        if ($physics->getMassRatio() > 10.0) {
            $this->addWarning(
                $shipID,
                sprintf(
                    'Extreme mass ratio (%.2fx) - effective ratio cap applied',
                    $physics->getMassRatio()
                )
            );
        }

        // Very low base mass (<100kg)
        if ($physics->getBaseMass() < 100) {
            $this->addWarning(
                $shipID,
                sprintf(
                    'Very low base mass (%.1f kg) - physics may be unstable',
                    $physics->getBaseMass()
                )
            );
        }
    }

    /**
     * Adds a warning for a specific ship.
     *
     * @param string $shipID Ship ID
     * @param string $warning Warning message
     * @return void
     */
    public function addWarning(string $shipID, string $warning): void
    {
        if (!isset($this->warnings[$shipID])) {
            $this->warnings[$shipID] = [];
        }

        $this->warnings[$shipID][] = $warning;
    }

    /**
     * Gets all warnings.
     *
     * @return array<string,string[]> Warnings by ship ID
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Gets the ship name for a given ship ID.
     *
     * @param string $shipID Ship ID (macro name)
     * @return string Ship name, or the ID if not found
     */
    public function getShipName(string $shipID): string
    {
        return $this->ships[$shipID]['name'] ?? $shipID;
    }

    /**
     * Generates a comprehensive diagnostics report.
     *
     * @return string Report text
     */
    public function generateReport(): string
    {
        $report = [];

        // Header
        $report[] = str_repeat('=', 80);
        $report[] = 'CARGO SIZE MOD - Physics Diagnostics Report';
        $report[] = 'Build Date: ' . $this->buildDate;
        $report[] = 'Configuration: Tier-based system';
        $report[] = str_repeat('=', 80);
        $report[] = '';

        // Ship details
        foreach ($this->ships as $shipID => $data) {
            $report[] = sprintf('Ship: %s (%s)', $data['name'], $data['id']);
            $report[] = sprintf('Class: %s %s', strtoupper($data['size']), $data['type']);
            $report[] = str_repeat('-', 80);

            $report[] = 'Mass:';
            $report[] = sprintf('  Base mass: %s kg', dec($data['baseMass'], 0));
            $report[] = sprintf(
                '  Original cargo: %s | New cargo: %s (%.1fx multiplier)',
                dec($data['originalCargo'], 0),
                dec($data['adjustedCargo'], 0),
                $data['cargoMultiplier']
            );
            $report[] = sprintf(
                '  Original full: %s kg | New full: %s kg',
                dec($data['originalFullMass'], 0),
                dec($data['adjustedFullMass'], 0)
            );

            $massIncreasePercent = ($data['massRatio'] - 1.0) * 100;
            $report[] = sprintf(
                '  Mass ratio: %.2fx (%.0f%% increase)',
                $data['massRatio'],
                $massIncreasePercent
            );

            if ($data['massRatio'] !== $data['effectiveRatio']) {
                $report[] = sprintf(
                    '  Effective ratio: %.2fx (capped from %.2fx)',
                    $data['effectiveRatio'],
                    $data['massRatio']
                );
            }

            $report[] = '';
            $report[] = 'Tiers Applied:';
            $report[] = sprintf(
                '  Drag tier: %.1fx → %d%% reduction (%d%% drag remains)',
                $data['dragTierMaxMultiplier'],
                (int)($data['dragTierReduction'] * 100),
                (int)((1.0 - $data['dragTierReduction']) * 100)
            );
            $report[] = sprintf(
                '  Jerk tier: %.1fx → %d%% reduction (%d%% jerk remains)',
                $data['jerkTierMaxMultiplier'],
                (int)($data['jerkTierReduction'] * 100),
                (int)((1.0 - $data['jerkTierReduction']) * 100)
            );

            $report[] = '';
            $report[] = 'Configuration:';
            $inertiaIncreasePercent = (($data['massRatio'] - 1.0) * $data['inertiaImpactFactor']) * 100;
            $report[] = sprintf(
                '  Inertia impact factor: %.2f (inertia increased %.0f%%)',
                $data['inertiaImpactFactor'],
                $inertiaIncreasePercent
            );
            $report[] = sprintf(
                '  Acceleration responsiveness: %.2f (%s feel)',
                $data['accelerationResponsiveness'],
                $data['accelerationResponsiveness'] === 1.0 ? 'vanilla' : ($data['accelerationResponsiveness'] < 1.0 ? 'heavier' : 'lighter')
            );
            $report[] = sprintf(
                '  Effective ratio cap: %s',
                $data['effectiveRatioCap'] ? 'ENABLED' : 'DISABLED'
            );

            // Status and warnings
            $hasWarnings = isset($this->warnings[$shipID]);
            if ($hasWarnings) {
                $report[] = '';
                $report[] = 'Status: ⚠️ WARNING';
                foreach ($this->warnings[$shipID] as $warning) {
                    $report[] = '  - ' . $warning;
                }
            } else {
                $report[] = '';
                $report[] = 'Status: ✓ OK';
            }

            $report[] = '';
            $report[] = str_repeat('=', 80);
            $report[] = '';
        }

        // Summary
        $report[] = 'Summary:';
        $report[] = sprintf('  Total ships: %d', count($this->ships));

        $categorized = $this->categorizeShipsByMassRatio();
        $report[] = sprintf('  Ships with mass ratio < 2.0x: %d (low impact)', $categorized['low']);
        $report[] = sprintf('  Ships with mass ratio 2.0-5.0x: %d (moderate impact)', $categorized['moderate']);
        $report[] = sprintf('  Ships with mass ratio 5.0-10.0x: %d (high impact - test carefully)', $categorized['high']);
        $report[] = sprintf('  Ships with mass ratio > 10.0x: %d (extreme - effective ratio cap applied)', $categorized['extreme']);
        $report[] = '';
        $report[] = sprintf('Warnings: %d ships flagged for testing', count($this->warnings));

        // Configuration summary
        if (!empty($this->ships)) {
            $firstShip = reset($this->ships);
            $report[] = '';
            $report[] = 'Configuration Used:';
            $report[] = '  Drag reduction tiers: 4 tiers (10%, 30%, 50%, 70% max)';
            $report[] = '  Jerk reduction tiers: 4 tiers (5%, 15%, 25%, 35% max)';
            $report[] = sprintf('  Inertia impact factor: %.2f', $firstShip['inertiaImpactFactor']);
            $report[] = sprintf('  Acceleration responsiveness: %.2f', $firstShip['accelerationResponsiveness']);
            $report[] = sprintf('  Effective ratio cap: %s', $firstShip['effectiveRatioCap'] ? 'ENABLED' : 'DISABLED');
        }

        $report[] = '';
        $report[] = str_repeat('=', 80);

        return implode(PHP_EOL, $report);
    }

    /**
     * Categorizes ships by mass ratio for summary.
     *
     * @return array{low: int, moderate: int, high: int, extreme: int}
     */
    private function categorizeShipsByMassRatio(): array
    {
        $categories = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'extreme' => 0
        ];

        foreach ($this->ships as $data) {
            $massRatio = $data['massRatio'];

            if ($massRatio < 2.0) {
                $categories['low']++;
            } elseif ($massRatio < 5.0) {
                $categories['moderate']++;
            } elseif ($massRatio < 10.0) {
                $categories['high']++;
            } else {
                $categories['extreme']++;
            }
        }

        return $categories;
    }

    /**
     * Writes the diagnostics report to a file.
     *
     * @param string $filePath Output file path
     * @return void
     */
    public function writeToFile(string $filePath): void
    {
        FileHelper::saveFile($filePath, $this->generateReport());
    }
}
