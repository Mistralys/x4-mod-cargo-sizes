<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\References;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\ChangelogParser\ChangelogParser;
use Mistralys\ChangelogParser\ChangelogVersion;
use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\ShipResult;
use Mistralys\X4\UI\Console;

class ReleaseNotesGenerator
{
    private FolderInfo $buildFolder;

    /** @var float[]|int[] */
    private array $multipliers;

    /** @var ShipResult[] */
    private array $shipResults;

    private BuildConfig $buildConfig;

    /**
     * @param FolderInfo $buildFolder
     * @param float[]|int[] $multipliers
     * @param ShipResult[] $shipResults
     * @param BuildConfig $buildConfig
     */
    public function __construct(FolderInfo $buildFolder, array $multipliers, array $shipResults, BuildConfig $buildConfig)
    {
        $this->buildFolder = $buildFolder;
        $this->multipliers = $multipliers;
        $this->shipResults = $shipResults;
        $this->buildConfig = $buildConfig;
    }
    
    /**
     * Main entry point - generates release notes file
     */
    public function generate(): void
    {
        // Check main changelog exists
        $mainChangelogPath = dirname(__DIR__, 4) . '/changelog.md';
        if (!file_exists($mainChangelogPath)) {
            throw new CargoSizeException(
                'Cannot generate release notes: changelog.md not found',
                '',
                CargoSizeException::ERROR_MISSING_CHANGELOG
            );
        }
        
        // Parse main changelog
        $mainVersion = $this->parseChangelog($mainChangelogPath);
        if ($mainVersion === null) {
            throw new CargoSizeException(
                'Failed to parse changelog.md',
                '',
                CargoSizeException::ERROR_CHANGELOG_PARSE
            );
        }
        
        // Parse builder changelog (optional)
        $builderChangelogPath = dirname(__DIR__, 4) . '/changelog-builder.md';
        $builderVersion = null;
        
        if (file_exists($builderChangelogPath)) {
            $builderVersion = $this->parseChangelog($builderChangelogPath);
        } else {
            Console::line2('changelog-builder.md not found, skipping builder section.');
        }
        
        // Generate content
        $content = $this->formatMainChangelog($mainVersion);
        
        if ($builderVersion !== null) {
            $content .= "\n\n" . $this->formatBuilderChangelog($builderVersion);
        }

        $comparisonTable = $this->formatComparisonTable();
        if ($comparisonTable !== '') {
            $content .= "\n\n" . $comparisonTable;
        }

        $content .= "\n\n" . $this->formatFooter();
        
        // Write file
        $outputPath = $this->getOutputPath($mainVersion->getNumber());
        
        try {
            FileHelper::saveFile($outputPath, $content);
        } catch (\Exception $e) {
            throw new CargoSizeException(
                sprintf('Failed to write release notes to %s: %s', $outputPath, $e->getMessage()),
                $e->getMessage(),
                CargoSizeException::ERROR_FILE_WRITE,
                $e
            );
        }
    }
    
    /**
     * Parse a changelog file and return latest version
     */
    private function parseChangelog(string $path): ?ChangelogVersion
    {
        if (!file_exists($path)) {
            return null;
        }
        
        $parser = ChangelogParser::parseMarkdownFile($path);
        $versions = $parser->getVersions();
        
        if (empty($versions)) {
            return null;
        }
        
        return $versions[0]; // First version is the latest
    }
    
    /**
     * Format main changelog section
     */
    private function formatMainChangelog(?ChangelogVersion $version): string
    {
        if ($version === null) {
            return '';
        }
        
        $lines = [];
        $label = trim($version->getLabel(), " \t\n\r\0\x0B-");
        $lines[] = '# Release v' . $version->getNumber() . ' - ' . $label;
        
        foreach ($version->getChanges() as $change) {
            $lines[] = '- ' . $change->getText();
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Format builder changelog section
     */
    private function formatBuilderChangelog(?ChangelogVersion $version): string
    {
        if ($version === null) {
            return '';
        }
        
        $lines = [];
        $label = trim($version->getLabel(), " \t\n\r\0\x0B-");
        $lines[] = '## Builder v' . $version->getNumber() . ' - ' . $label;
        
        foreach ($version->getChanges() as $change) {
            $lines[] = '- ' . $change->getText();
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Format installation instructions footer
     */
    private function formatFooter(): string
    {
        $lines = [];
        $lines[] = '----';
        $lines[] = '';
        $lines[] = 'Choose your ZIP file for installing manually or via Vortex.';
        $lines[] = '';
        $lines[] = 'AIO = All In One, with all supported ship types';
        $lines[] = 'FOMOD = Installer to choose by ship type and size';
        
        return implode("\n", $lines);
    }
    
    /**
     * Generates a Markdown comparison table showing cargo changes
     * for a randomly-selected transport ship across all multipliers.
     *
     * @return string Markdown table section, or empty string if no transport ships found
     */
    protected function formatComparisonTable(): string
    {
        $transportShips = array_filter(
            $this->shipResults,
            static fn(ShipResult $ship): bool => in_array(
                $ship->getShipType(),
                [CargoSizeExtractor::SHIP_TYPE_TRANSPORT, CargoSizeExtractor::SHIP_TYPE_STORAGE],
                true
            )
        );

        if (empty($transportShips)) {
            return '';
        }

        $transportShips = array_values($transportShips);

        // Prefer the deterministically configured ship if available.
        $exampleShip = null;
        $typeSizeCombinations = [
            [CargoSizeExtractor::SHIP_TYPE_TRANSPORT, 's'],
            [CargoSizeExtractor::SHIP_TYPE_TRANSPORT, 'm'],
            [CargoSizeExtractor::SHIP_TYPE_TRANSPORT, 'l'],
            [CargoSizeExtractor::SHIP_TYPE_STORAGE, 's'],
            [CargoSizeExtractor::SHIP_TYPE_STORAGE, 'm'],
            [CargoSizeExtractor::SHIP_TYPE_STORAGE, 'l'],
        ];
        foreach ($typeSizeCombinations as [$type, $size]) {
            $label = $this->buildConfig->getExampleShip($type, $size);
            if ($label === '') {
                continue;
            }
            foreach ($transportShips as $ship) {
                if ($ship->getShipLabel() === $label) {
                    $exampleShip = $ship;
                    break 2;
                }
            }
        }

        // Fall back to random selection when not configured or ship not found in build data.
        if ($exampleShip === null) {
            $exampleShip = $transportShips[array_rand($transportShips)];
        }

        $lines = [];
        $lines[] = '## Cargo Multiplier Comparison';
        $lines[] = '';
        $lines[] = '| Variant | Example Ship | Vanilla Cargo | Adjusted Cargo |';
        $lines[] = '|---------|-------------|---------------|----------------|';

        foreach ($this->multipliers as $multiplier) {
            $lines[] = sprintf(
                '| AIO x%s | %s | %s m³ | %s m³ |',
                $multiplier,
                $exampleShip->getShipLabel(),
                number_format($exampleShip->getCargoValue(), 0, '.', ','),
                number_format($exampleShip->calculateCargoValue($multiplier), 0, '.', ',')
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Get output file path with version number
     */
    private function getOutputPath(string $version): string
    {
        return $this->buildFolder->getPath() . '/release-notes-v' . $version . '.md';
    }
}
