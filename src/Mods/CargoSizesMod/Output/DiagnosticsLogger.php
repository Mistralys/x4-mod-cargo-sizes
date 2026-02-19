<?php
/**
 * @package Output
 * @subpackage Diagnostics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\FileHelper;
use Mistralys\X4\Mods\CargoSizesMod\ShipResult;
use function Mistralys\X4\dec;

/**
 * Diagnostics logger for physics calculations.
 *
 * Generates reports showing:
 * - Ship-by-ship calculations (mass ratio, acceleration scaling, responsiveness)
 * - Warnings for extreme mass ratios
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
     * @param float $massRatio Adjusted-full-mass / original-full-mass ratio
     * @param float $accelerationScalingFactor Pre-calculated scaling: massRatio × responsiveness
     * @param float $responsiveness Acceleration responsiveness factor from build config
     * @return void
     */
    public function logShip(
        ShipResult $ship,
        float $massRatio,
        float $accelerationScalingFactor,
        float $responsiveness
    ): void
    {
        $shipID = $ship->getShipXMLFile()->getMacroName();

        $this->ships[$shipID] = [
            'name' => $ship->getShipLabel(),
            'id' => $shipID,
            'size' => $ship->getSize(),
            'type' => $ship->getShipType(),
            'massRatio' => $massRatio,
            'accelerationScalingFactor' => $accelerationScalingFactor,
            'responsiveness' => $responsiveness,
        ];

        $this->checkWarnings($shipID, $massRatio);
    }

    /**
     * Checks for and logs warnings for extreme mass ratios.
     *
     * @param string $shipID Ship ID
     * @param float $massRatio Mass ratio
     * @return void
     */
    private function checkWarnings(string $shipID, float $massRatio): void
    {
        if ($massRatio > 10.0) {
            $this->addWarning(
                $shipID,
                sprintf(
                    'Extreme mass ratio (%.2fx) - test carefully in-game',
                    $massRatio
                )
            );
        } elseif ($massRatio > 5.0) {
            $this->addWarning(
                $shipID,
                sprintf(
                    'High mass ratio (%.2fx) - test carefully in-game',
                    $massRatio
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
     * Generates a diagnostics report.
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
        $report[] = 'Configuration: Acceleration-only (preserve original flight feel)';
        $report[] = str_repeat('=', 80);
        $report[] = '';

        // Ship details
        foreach ($this->ships as $shipID => $data) {
            $report[] = sprintf('Ship: %s (%s)', $data['name'], $data['id']);
            $report[] = sprintf('Class: %s %s', strtoupper($data['size']), $data['type']);
            $report[] = str_repeat('-', 80);

            $massIncreasePercent = ($data['massRatio'] - 1.0) * 100;
            $report[] = sprintf(
                'Mass ratio: %.2fx (%.0f%% increase)',
                $data['massRatio'],
                $massIncreasePercent
            );

            $report[] = '';
            $report[] = 'Acceleration:';
            $report[] = sprintf(
                '  Scaling factor: %.3fx (= mass ratio × responsiveness = %.2f × %.2f)',
                $data['accelerationScalingFactor'],
                $data['massRatio'],
                $data['responsiveness']
            );
            $report[] = sprintf(
                '  Responsiveness: %.2f (%s)',
                $data['responsiveness'],
                $data['responsiveness'] === 1.0 ? 'vanilla feel' : ($data['responsiveness'] < 1.0 ? 'heavier feel' : 'lighter feel')
            );

            // Status and warnings
            $hasWarnings = isset($this->warnings[$shipID]);
            if ($hasWarnings) {
                $report[] = '';
                $report[] = 'Status: WARNING';
                foreach ($this->warnings[$shipID] as $warning) {
                    $report[] = '  - ' . $warning;
                }
            } else {
                $report[] = '';
                $report[] = 'Status: OK';
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
        $report[] = sprintf('  Ships with mass ratio > 10.0x: %d (extreme - test carefully)', $categorized['extreme']);
        $report[] = '';
        $report[] = sprintf('Warnings: %d ships flagged for testing', count($this->warnings));

        // Configuration summary
        if (!empty($this->ships)) {
            $firstShip = reset($this->ships);
            $report[] = '';
            $report[] = 'Configuration Used:';
            $report[] = sprintf('  Acceleration responsiveness: %.2f', $firstShip['responsiveness']);
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
