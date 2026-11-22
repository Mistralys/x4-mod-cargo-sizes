<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use ZipArchive;

/**
 * Compares a baseline set of build zips against a fresh build, entry by
 * entry, using the CRC32 of each entry's content. It is the oracle for
 * refactors that must not change the mod output.
 *
 * Baseline layout (written by {@see BaselineCapture}):
 *
 * - `<baseline>/zips/*.zip`
 * - `<baseline>/physics-diagnostics.txt`
 *
 * Build layout (written by the build): `<build>/v<mod>-for-v<game>/*.zip`
 * and `<build>/physics-diagnostics.txt`. When several version folders exist,
 * the most recently modified one is used.
 *
 * Normalisation applied before comparing: the baseline version is replaced
 * with the build version in entry names and text entries (dotted, dashed
 * and the compact `version="320"` form), and generated dates are neutralised
 * (`Build Date:`, `Generated on:` and the `date` attribute of `content.xml`).
 * Line endings are never folded, so a line ending regression is always
 * reported as a content difference.
 *
 * Entries on the allow-list may differ in content, but must still exist on
 * both sides: entry-set equality is evaluated independently of the allow-list.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BuildParityComparator
{
    public const DEFAULT_BASELINE_DIR = 'work/baseline-ship-types';
    public const DEFAULT_BUILD_DIR = 'build';
    public const DIAGNOSTICS_FILE = 'physics-diagnostics.txt';
    public const BASELINE_ZIPS_FOLDER = 'zips';

    /**
     * Entries whose content may legitimately differ between builds.
     * Pattern (matched against the entry path inside the zip) => justification.
     *
     * @var array<string,string>
     */
    public const ALLOWED_CONTENT_DIFFERENCES = array(
        '#^fomod/ModuleConfig\.xml$#' => 'Contains the example ship per option, picked with array_rand() in FOMOD/FileCollection.php when none is configured.'
    );

    /**
     * File extensions of entries that get the text normalisation.
     * Other entries (images, archives) are compared by their stored CRC32.
     */
    private const TEXT_EXTENSIONS = array('xml', 'txt', 'md', 'json', 'bbcode');

    private const DIAGNOSTICS_ENTRY = 'physics-diagnostics.txt';

    private string $baselineDir;
    private string $buildDir;

    /**
     * @var array<string,string>
     */
    private array $allowList;

    /**
     * @param string $baselineDir Folder holding the baseline layout.
     * @param string $buildDir Build output root.
     * @param array<string,string>|null $allowList Pattern => justification. Defaults to {@see self::ALLOWED_CONTENT_DIFFERENCES}.
     */
    public function __construct(
        string $baselineDir = self::DEFAULT_BASELINE_DIR,
        string $buildDir = self::DEFAULT_BUILD_DIR,
        ?array $allowList = null
    )
    {
        $this->baselineDir = rtrim($baselineDir, '/\\');
        $this->buildDir = rtrim($buildDir, '/\\');
        $this->allowList = $allowList ?? self::ALLOWED_CONTENT_DIFFERENCES;
    }

    /**
     * Whether the baseline folder holds at least one baseline zip.
     */
    public static function hasBaseline(string $baselineDir) : bool
    {
        return !empty(glob(rtrim($baselineDir, '/\\').'/'.self::BASELINE_ZIPS_FOLDER.'/*.zip'));
    }

    /**
     * @return BuildParityResult
     * @throws CargoSizeException If the baseline or the build output cannot be read.
     */
    public function compare() : BuildParityResult
    {
        $baselineZips = $this->indexZips(
            glob($this->baselineDir.'/'.self::BASELINE_ZIPS_FOLDER.'/*.zip') ?: array()
        );

        if(empty($baselineZips)) {
            throw new CargoSizeException(
                sprintf('No baseline zips found in [%s].', $this->baselineDir),
                '',
                CargoSizeException::ERROR_PARITY_SETUP
            );
        }

        $buildFolder = self::findBuildZipFolder($this->buildDir);
        $buildZips = $this->indexZips(glob($buildFolder.'/*.zip') ?: array());

        $result = new BuildParityResult();

        foreach($baselineZips as $key => $baseline)
        {
            if(!isset($buildZips[$key])) {
                $result->addMissing($key.': '.basename($baseline['path']));
                continue;
            }

            $this->compareZip($result, $key, $baseline, $buildZips[$key]);
        }

        foreach($buildZips as $key => $build)
        {
            if(!isset($baselineZips[$key])) {
                $result->addUnexpected($key.': '.basename($build['path']));
            }
        }

        $this->compareDiagnostics($result);

        return $result;
    }

    private function compareDiagnostics(BuildParityResult $result) : void
    {
        $baselinePath = $this->baselineDir.'/'.self::DIAGNOSTICS_FILE;
        $buildPath = $this->buildDir.'/'.self::DIAGNOSTICS_FILE;
        $inBaseline = is_file($baselinePath);
        $inBuild = is_file($buildPath);

        if(!$inBaseline && !$inBuild) {
            return;
        }

        if(!$inBaseline) {
            $result->addUnexpected(self::DIAGNOSTICS_ENTRY);
            return;
        }

        if(!$inBuild) {
            $result->addMissing(self::DIAGNOSTICS_ENTRY);
            return;
        }

        // The diagnostics report carries the build date but no version.
        $baselineCrc = $this->crc($this->normalizeDates((string)file_get_contents($baselinePath)));
        $buildCrc = $this->crc($this->normalizeDates((string)file_get_contents($buildPath)));

        if($baselineCrc === $buildCrc) {
            $result->addMatching(self::DIAGNOSTICS_ENTRY);
        } else {
            $result->addDiffering(self::DIAGNOSTICS_ENTRY);
        }
    }

    /**
     * @param array{path:string,version:string} $baseline
     * @param array{path:string,version:string} $build
     */
    private function compareZip(BuildParityResult $result, string $key, array $baseline, array $build) : void
    {
        $baselineEntries = $this->readEntries($baseline['path'], $baseline['version'], $build['version']);
        $buildEntries = $this->readEntries($build['path'], $build['version'], $build['version']);

        foreach($baselineEntries as $name => $baselineEntry)
        {
            $label = $key.': '.$name;

            if(!isset($buildEntries[$name])) {
                $result->addMissing($label);
                continue;
            }

            if($baselineEntry['crc'] === $buildEntries[$name]['crc']) {
                $result->addMatching($label);
            } elseif($this->isAllowed($name)) {
                $result->addAllowedDifference($label);
            } else {
                $result->addDiffering($label);
            }
        }

        foreach($buildEntries as $name => $unused)
        {
            if(!isset($baselineEntries[$name])) {
                $result->addUnexpected($key.': '.$name);
            }
        }
    }

    private function isAllowed(string $entryName) : bool
    {
        foreach(array_keys($this->allowList) as $pattern)
        {
            if(preg_match($pattern, $entryName) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reads the entries of a zip as `normalized name => CRC32 of normalized content`.
     *
     * @param string $path
     * @param string $fromVersion Version to replace.
     * @param string $toVersion Version to replace it with.
     * @return array<string,array{crc:string}>
     * @throws CargoSizeException
     */
    private function readEntries(string $path, string $fromVersion, string $toVersion) : array
    {
        $zip = new ZipArchive();

        if($zip->open($path) !== true) {
            throw new CargoSizeException(
                sprintf('Cannot open zip file [%s].', $path),
                '',
                CargoSizeException::ERROR_PARITY_SETUP
            );
        }

        $entries = array();

        for($i = 0; $i < $zip->numFiles; $i++)
        {
            $stat = $zip->statIndex($i);

            if($stat === false || str_ends_with($stat['name'], '/')) {
                continue;
            }

            $name = $this->normalizeVersion($stat['name'], $fromVersion, $toVersion);

            if(!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::TEXT_EXTENSIONS, true)) {
                $entries[$name] = array('crc' => sprintf('%08x', $stat['crc']));
                continue;
            }

            $content = (string)$zip->getFromIndex($i);
            $content = $this->normalizeDates($this->normalizeVersion($content, $fromVersion, $toVersion));

            $entries[$name] = array('crc' => $this->crc($content));
        }

        $zip->close();

        return $entries;
    }

    /**
     * Replaces the dotted, dashed and compact forms of a version.
     */
    private function normalizeVersion(string $subject, string $from, string $to) : string
    {
        if($from === '' || $from === $to) {
            return $subject;
        }

        return str_replace(
            array(
                $from,
                str_replace('.', '-', $from),
                'version="'.str_replace('.', '', $from).'"'
            ),
            array(
                $to,
                str_replace('.', '-', $to),
                'version="'.str_replace('.', '', $to).'"'
            ),
            $subject
        );
    }

    /**
     * Neutralises generated dates. The character classes exclude line
     * breaks so the line endings of the matched lines stay untouched.
     */
    private function normalizeDates(string $content) : string
    {
        $content = preg_replace('#^(Build Date|Generated on): [^\r\n]*#m', '$1: <date>', $content);
        $content = preg_replace('# date="\d{4}-\d{2}-\d{2}"#', ' date="<date>"', (string)$content);

        return (string)$content;
    }

    private function crc(string $content) : string
    {
        return hash('crc32b', $content);
    }

    /**
     * Indexes zip files by their name without the version suffix.
     *
     * @param string[] $paths
     * @return array<string,array{path:string,version:string}>
     */
    private function indexZips(array $paths) : array
    {
        $result = array();

        foreach($paths as $path)
        {
            if(preg_match('#^(.+)[_-]v(\d+(?:\.\d+)*)\.zip$#', basename($path), $matches) !== 1) {
                $result[basename($path)] = array('path' => $path, 'version' => '');
                continue;
            }

            $result[$matches[1]] = array('path' => $path, 'version' => $matches[2]);
        }

        ksort($result);

        return $result;
    }

    /**
     * Finds the folder holding the zips of the latest build.
     *
     * @param string $buildDir Build output root.
     * @return string
     * @throws CargoSizeException
     */
    public static function findBuildZipFolder(string $buildDir) : string
    {
        $buildDir = rtrim($buildDir, '/\\');
        $folders = glob($buildDir.'/v*-for-v*', GLOB_ONLYDIR) ?: array();

        if(empty($folders)) {
            throw new CargoSizeException(
                sprintf('No build output found in [%s]. Run: composer build', $buildDir),
                '',
                CargoSizeException::ERROR_PARITY_SETUP
            );
        }

        usort($folders, static function (string $a, string $b) : int {
            return (filemtime($b) <=> filemtime($a)) ?: strcmp($b, $a);
        });

        return $folders[0];
    }
}
