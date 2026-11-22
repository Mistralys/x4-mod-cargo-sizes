<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

/**
 * Outcome of a baseline command: the exit code and the output lines.
 * The first line is always the verdict (or the error message).
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BaselineCommandResult
{
    public const EXIT_OK = 0;
    public const EXIT_DIFFERENCES = 1;
    public const EXIT_ERROR = 2;

    private int $exitCode;

    /**
     * @var string[]
     */
    private array $lines;

    /**
     * @param int $exitCode
     * @param string[] $lines
     */
    public function __construct(int $exitCode, array $lines)
    {
        $this->exitCode = $exitCode;
        $this->lines = $lines;
    }

    public function getExitCode() : int
    {
        return $this->exitCode;
    }

    /**
     * @return string[]
     */
    public function getLines() : array
    {
        return $this->lines;
    }

    public function getVerdict() : string
    {
        return $this->lines[0] ?? '';
    }

    public function render() : string
    {
        return implode(PHP_EOL, $this->lines).PHP_EOL;
    }
}
