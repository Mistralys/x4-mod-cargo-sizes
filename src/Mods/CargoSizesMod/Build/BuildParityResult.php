<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

/**
 * Outcome of a {@see BuildParityComparator} run: which entries match
 * the baseline, which differ, and which are missing or unexpected.
 *
 * Entry names have the form `<zip key>: <entry path>`, for example
 * `cargo-size-aio-2x: content.xml`. A whole zip that exists on one
 * side only is reported as `<zip key>: <zip>`.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BuildParityResult
{
    /**
     * @var string[]
     */
    private array $matching = array();

    /**
     * @var string[]
     */
    private array $differing = array();

    /**
     * @var string[]
     */
    private array $missing = array();

    /**
     * @var string[]
     */
    private array $unexpected = array();

    /**
     * @var string[]
     */
    private array $allowed = array();

    public function addMatching(string $name) : void
    {
        $this->matching[] = $name;
    }

    public function addDiffering(string $name) : void
    {
        $this->differing[] = $name;
    }

    /**
     * Entry exists in the baseline but not in the build.
     */
    public function addMissing(string $name) : void
    {
        $this->missing[] = $name;
    }

    /**
     * Entry exists in the build but not in the baseline.
     */
    public function addUnexpected(string $name) : void
    {
        $this->unexpected[] = $name;
    }

    /**
     * Entry whose content differs, but is on the allow-list.
     */
    public function addAllowedDifference(string $name) : void
    {
        $this->allowed[] = $name;
    }

    /**
     * @return string[]
     */
    public function getMatching() : array
    {
        return $this->matching;
    }

    /**
     * @return string[]
     */
    public function getDiffering() : array
    {
        return $this->differing;
    }

    /**
     * @return string[]
     */
    public function getMissing() : array
    {
        return $this->missing;
    }

    /**
     * @return string[]
     */
    public function getUnexpected() : array
    {
        return $this->unexpected;
    }

    /**
     * @return string[]
     */
    public function getAllowedDifferences() : array
    {
        return $this->allowed;
    }

    public function isParity() : bool
    {
        return empty($this->differing) && empty($this->missing) && empty($this->unexpected);
    }

    /**
     * One-line verdict: `PARITY PASS` or `PARITY FAIL: <n> differing, <m> missing, <k> unexpected`.
     */
    public function getVerdict() : string
    {
        if($this->isParity()) {
            return 'PARITY PASS';
        }

        return sprintf(
            'PARITY FAIL: %d differing, %d missing, %d unexpected',
            count($this->differing),
            count($this->missing),
            count($this->unexpected)
        );
    }

    /**
     * Plain text listing of the differences, one entry per line.
     * Empty when there are no differences.
     *
     * @return string[]
     */
    public function getDifferenceLines() : array
    {
        $lines = array();

        foreach($this->differing as $name) {
            $lines[] = 'DIFFERS    '.$name;
        }

        foreach($this->missing as $name) {
            $lines[] = 'MISSING    '.$name;
        }

        foreach($this->unexpected as $name) {
            $lines[] = 'UNEXPECTED '.$name;
        }

        return $lines;
    }

    /**
     * @return array{matching:string[],differing:string[],missing:string[],unexpected:string[],allowed:string[]}
     */
    public function toArray() : array
    {
        return array(
            'matching' => $this->matching,
            'differing' => $this->differing,
            'missing' => $this->missing,
            'unexpected' => $this->unexpected,
            'allowed' => $this->allowed
        );
    }
}
