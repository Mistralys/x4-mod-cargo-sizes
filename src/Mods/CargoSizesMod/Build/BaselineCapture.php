<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

/**
 * Snapshots the current build output into a baseline folder, in the
 * layout read by {@see BuildParityComparator}: `zips/*.zip` and
 * `physics-diagnostics.txt`.
 *
 * It does not run the build itself; see {@see BaselineCommand::capture()}.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BaselineCapture
{
    private string $buildDir;
    private string $baselineDir;

    public function __construct(string $buildDir, string $baselineDir)
    {
        $this->buildDir = rtrim($buildDir, '/\\');
        $this->baselineDir = rtrim($baselineDir, '/\\');
    }

    /**
     * Fails when the baseline folder holds anything, unless forced.
     *
     * @param bool $force
     * @throws CargoSizeException
     */
    public function assertWritable(bool $force) : void
    {
        if($force || !$this->isNonEmpty()) {
            return;
        }

        throw new CargoSizeException(
            sprintf(
                'Baseline folder [%s] is not empty and may still be needed. '.
                'Use --force to replace it, or --baseline=<dir> to capture into another folder.',
                $this->baselineDir
            ),
            '',
            CargoSizeException::ERROR_PARITY_SETUP
        );
    }

    /**
     * @param bool $force
     * @return string The baseline folder.
     * @throws CargoSizeException
     */
    public function capture(bool $force) : string
    {
        $this->assertWritable($force);

        $zipFolder = BuildParityComparator::findBuildZipFolder($this->buildDir);
        $zips = glob($zipFolder.'/*.zip') ?: array();

        if(empty($zips)) {
            throw new CargoSizeException(
                sprintf('No zip files found in [%s]. Run: composer build', $zipFolder),
                '',
                CargoSizeException::ERROR_PARITY_SETUP
            );
        }

        $this->clear();

        $target = $this->baselineDir.'/'.BuildParityComparator::BASELINE_ZIPS_FOLDER;

        if(!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            throw $this->createWriteError($target);
        }

        foreach($zips as $zip)
        {
            if(!copy($zip, $target.'/'.basename($zip))) {
                throw $this->createWriteError($target.'/'.basename($zip));
            }
        }

        $diagnostics = $this->buildDir.'/'.BuildParityComparator::DIAGNOSTICS_FILE;
        $diagnosticsTarget = $this->baselineDir.'/'.BuildParityComparator::DIAGNOSTICS_FILE;

        if(is_file($diagnostics) && !copy($diagnostics, $diagnosticsTarget)) {
            throw $this->createWriteError($diagnosticsTarget);
        }

        return $this->baselineDir;
    }

    private function isNonEmpty() : bool
    {
        if(!is_dir($this->baselineDir)) {
            return false;
        }

        return count(scandir($this->baselineDir) ?: array()) > 2;
    }

    /**
     * Removes only what a capture writes (never the whole folder),
     * so a mistyped `--baseline` plus `--force` cannot wipe unrelated files.
     */
    private function clear() : void
    {
        foreach(glob($this->baselineDir.'/'.BuildParityComparator::BASELINE_ZIPS_FOLDER.'/*.zip') ?: array() as $zip) {
            unlink($zip);
        }

        $diagnostics = $this->baselineDir.'/'.BuildParityComparator::DIAGNOSTICS_FILE;

        if(is_file($diagnostics)) {
            unlink($diagnostics);
        }
    }

    private function createWriteError(string $path) : CargoSizeException
    {
        return new CargoSizeException(
            sprintf('Cannot write to [%s].', $path),
            '',
            CargoSizeException::ERROR_PARITY_SETUP
        );
    }
}
