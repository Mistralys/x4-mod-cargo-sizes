<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\References;

use AppUtils\FileHelper;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\ChangelogParser\ChangelogParser;
use Mistralys\ChangelogParser\ChangelogVersion;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use Mistralys\X4\UI\Console;

class ReleaseNotesGenerator
{
    private FolderInfo $buildFolder;
    
    public function __construct(FolderInfo $buildFolder)
    {
        $this->buildFolder = $buildFolder;
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
     * Get output file path with version number
     */
    private function getOutputPath(string $version): string
    {
        return $this->buildFolder->getPath() . '/release-notes-v' . $version . '.md';
    }
}
