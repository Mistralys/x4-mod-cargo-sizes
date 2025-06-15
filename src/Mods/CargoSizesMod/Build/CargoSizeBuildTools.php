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
        self::init();

        try
        {
            $extractor = new CargoSizeExtractor(
                FolderInfo::factory(X4_EXTRACTED_CAT_FILES_FOLDER),
                FolderInfo::factory(__DIR__.'/../../../../build')
            );

            $extractor->extract(self::getMultipliers());
        }
        catch (BaseException $e)
        {
            UserInterface::displayException($e);
        }
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

    /**
     * @return array<int,int|float>
     */
    private static function getMultipliers() : array
    {
        $config = JSONFile::factory(__DIR__.'/../../../../config/build-config.json')->parse();

        return $config['cargo-multipliers'] ?? array(2);
    }
}
