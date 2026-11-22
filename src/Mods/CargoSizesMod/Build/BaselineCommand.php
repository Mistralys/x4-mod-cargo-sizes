<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

/**
 * Logic of the Composer commands `build:parity` and `build:baseline`.
 * Built for agent use: never prompts, prints a one-line verdict first
 * and maps the outcome to fixed exit codes (see {@see BaselineCommandResult}).
 *
 * Relative paths resolve against the project root, not the working directory.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class BaselineCommand
{
    /**
     * Compares the latest build against the baseline.
     *
     * @param string[] $arguments
     * @param string|null $buildDir Build output root. Defaults to the project's `build` folder.
     * @return BaselineCommandResult
     */
    public static function compare(array $arguments, ?string $buildDir = null) : BaselineCommandResult
    {
        try
        {
            $options = BaselineOptions::parse($arguments, false);
            $baselineDir = self::resolvePath($options->getBaselineDir());

            if(!BuildParityComparator::hasBaseline($baselineDir)) {
                return self::error(sprintf(
                    'No baseline at %s. Run: composer build:baseline',
                    $options->getBaselineDir()
                ));
            }

            $result = (new BuildParityComparator(
                $baselineDir,
                $buildDir ?? self::resolvePath(BuildParityComparator::DEFAULT_BUILD_DIR)
            ))->compare();
        }
        catch (CargoSizeException $e)
        {
            return self::error($e->getMessage());
        }

        return new BaselineCommandResult(
            $result->isParity() ? BaselineCommandResult::EXIT_OK : BaselineCommandResult::EXIT_DIFFERENCES,
            array_merge(array($result->getVerdict()), $result->getDifferenceLines())
        );
    }

    /**
     * Builds from the current source (through the given callback), then
     * snapshots the result into the baseline folder.
     *
     * @param string[] $arguments
     * @param callable():bool $build Runs the build. Returns false on failure.
     * @param string|null $buildDir Build output root. Defaults to the project's `build` folder.
     * @return BaselineCommandResult
     */
    public static function capture(array $arguments, callable $build, ?string $buildDir = null) : BaselineCommandResult
    {
        try
        {
            $options = BaselineOptions::parse($arguments, true);

            $capture = new BaselineCapture(
                $buildDir ?? self::resolvePath(BuildParityComparator::DEFAULT_BUILD_DIR),
                self::resolvePath($options->getBaselineDir())
            );

            // Refuse before building, so a refusal does not cost a full build.
            $capture->assertWritable($options->isForce());

            if(!$build()) {
                return self::error('The build failed, no baseline was captured. Fix the build error and run again.');
            }

            $capture->capture($options->isForce());
        }
        catch (CargoSizeException $e)
        {
            return self::error($e->getMessage());
        }

        return new BaselineCommandResult(
            BaselineCommandResult::EXIT_OK,
            array(
                'BASELINE CAPTURED',
                'Note: a build rewrites the tracked file docs/cargo-size-reference.md; that change is expected.',
                $options->getBaselineDir()
            )
        );
    }

    /**
     * @return string[]
     */
    public static function getUsageLines() : array
    {
        return array(
            'Usage: composer build:baseline [-- '.implode(' ', BaselineOptions::getValidOptions(true)).']',
            '       composer build:parity [-- '.implode(' ', BaselineOptions::getValidOptions(false)).']',
            'Default baseline folder: '.BuildParityComparator::DEFAULT_BASELINE_DIR,
            'Exit codes: 0 = pass, 1 = differences, 2 = usage or setup error',
            'Note: a build rewrites the tracked file docs/cargo-size-reference.md; that change is expected.'
        );
    }

    private static function error(string $message) : BaselineCommandResult
    {
        return new BaselineCommandResult(
            BaselineCommandResult::EXIT_ERROR,
            array('ERROR: '.$message)
        );
    }

    /**
     * Resolves a relative path against the project root.
     */
    private static function resolvePath(string $path) : string
    {
        if(preg_match('#^([a-zA-Z]:)?[/\\\\]#', $path) === 1) {
            return $path;
        }

        return dirname(__DIR__, 4).'/'.$path;
    }
}
