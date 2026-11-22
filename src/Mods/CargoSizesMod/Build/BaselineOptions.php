<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

/**
 * Parses the two options shared by the `build:baseline` and `build:parity`
 * Composer commands: `--baseline=<dir>` and `--force`.
 *
 * Deliberately minimal: not a general option framework.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BaselineOptions
{
    public const OPTION_BASELINE = '--baseline';
    public const OPTION_FORCE = '--force';

    private string $baselineDir;
    private bool $force;

    private function __construct(string $baselineDir, bool $force)
    {
        $this->baselineDir = $baselineDir;
        $this->force = $force;
    }

    /**
     * @param string[] $arguments Raw arguments, without the Composer `--` separator.
     * @param bool $allowForce Whether `--force` is a valid option for the calling command.
     * @return BaselineOptions
     * @throws CargoSizeException With {@see CargoSizeException::ERROR_PARITY_SETUP} on an unknown option.
     */
    public static function parse(array $arguments, bool $allowForce) : BaselineOptions
    {
        $baselineDir = BuildParityComparator::DEFAULT_BASELINE_DIR;
        $force = false;
        $prefix = self::OPTION_BASELINE.'=';

        foreach($arguments as $argument)
        {
            if(str_starts_with($argument, $prefix) && strlen($argument) > strlen($prefix)) {
                $baselineDir = substr($argument, strlen($prefix));
                continue;
            }

            if($allowForce && $argument === self::OPTION_FORCE) {
                $force = true;
                continue;
            }

            throw new CargoSizeException(
                sprintf(
                    'Unknown option [%s]. Valid options: %s',
                    $argument,
                    implode(', ', self::getValidOptions($allowForce))
                ),
                '',
                CargoSizeException::ERROR_PARITY_SETUP
            );
        }

        return new BaselineOptions($baselineDir, $force);
    }

    /**
     * @param bool $allowForce
     * @return string[]
     */
    public static function getValidOptions(bool $allowForce) : array
    {
        $options = array(self::OPTION_BASELINE.'=<dir>');

        if($allowForce) {
            $options[] = self::OPTION_FORCE;
        }

        return $options;
    }

    public function getBaselineDir() : string
    {
        return $this->baselineDir;
    }

    public function isForce() : bool
    {
        return $this->force;
    }
}
