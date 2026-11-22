<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\BaseException;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\FileHelper\JSONFile;
use Mistralys\ChangelogParser\ChangelogParser;
use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\UI\UserInterface;
use const Mistralys\X4\X4_EXTRACTED_CAT_FILES_FOLDER;

/**
 * Endpoint for the Composer command `build-mod`. Uses the {@see CargoSizeExtractor}
 * to fetch information on ship cargo sizes and generate the necessary XML files.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class CargoSizeBuildTools
{
    public static function build() : void
    {
        self::runBuild();
    }

    /**
     * Composer entry point `build:parity`: compares the latest build against
     * the baseline. Run `composer build` first. See {@see BaselineCommand}.
     *
     * Exit codes: 0 parity, 1 differences, 2 usage or setup error.
     *
     * @param object|null $event The Composer script event, when called by Composer.
     */
    public static function compareBaseline(?object $event = null) : void
    {
        self::finish(BaselineCommand::compare(self::resolveArguments($event)));
    }

    /**
     * Composer entry point `build:baseline`: builds from the current source,
     * then snapshots the result as the baseline for a later `build:parity`.
     * See {@see BaselineCommand}.
     *
     * Exit codes: 0 captured, 2 usage or setup error.
     *
     * @param object|null $event The Composer script event, when called by Composer.
     */
    public static function captureBaseline(?object $event = null) : void
    {
        self::finish(BaselineCommand::capture(
            self::resolveArguments($event),
            static function () : bool {
                return self::runBuild();
            }
        ));
    }

    /**
     * @return bool False when the build failed with an exception.
     */
    private static function runBuild() : bool
    {
        self::init();

        try
        {
            $extractor = new CargoSizeExtractor(
                FolderInfo::factory(X4_EXTRACTED_CAT_FILES_FOLDER),
                FolderInfo::factory(__DIR__.'/../../../../build'),
                self::getConfig()
            );

            $extractor->extract(self::getConfig()->getMultipliers());
        }
        catch (BaseException $e)
        {
            UserInterface::displayException($e);

            return false;
        }

        return true;
    }

    /**
     * Prints the command output and ends the process with the exit code
     * when it is not 0. Unknown-option errors also print the usage.
     */
    private static function finish(BaselineCommandResult $result) : void
    {
        echo $result->render();

        if($result->getExitCode() === BaselineCommandResult::EXIT_ERROR) {
            echo implode(PHP_EOL, BaselineCommand::getUsageLines()).PHP_EOL;
        }

        if($result->getExitCode() !== BaselineCommandResult::EXIT_OK) {
            exit($result->getExitCode());
        }
    }

    /**
     * Arguments given after the `--` separator of the Composer command.
     * Composer passes them through the script event; the fallback reads
     * them from the raw command line.
     *
     * @param object|null $event
     * @return string[]
     */
    private static function resolveArguments(?object $event) : array
    {
        if($event !== null && method_exists($event, 'getArguments')) {
            return array_values(array_map('strval', $event->getArguments()));
        }

        $argv = $_SERVER['argv'] ?? array();
        $separator = array_search('--', $argv, true);

        if($separator === false) {
            return array();
        }

        return array_slice($argv, $separator + 1);
    }

    private static function init() : void
    {
        require_once __DIR__.'/../../../../vendor/autoload.php';
        require_once __DIR__.'/../../../../dev-config.php';

        self::updateVersion();
    }

    private static function updateVersion() : void
    {
        $version = ChangelogParser::parseMarkdownFile(__DIR__.'/../../../../changelog.md')
            ->requireLatestVersion()
            ->getNumber();

        ModInfo::getVersionFile()->putContents($version);
    }

    private static ?BuildConfig $config = null;

    public static function getConfig() : BuildConfig
    {
        if(!isset(self::$config)) {
            self::$config = new BuildConfig();
        }

        return self::$config;
    }
}
