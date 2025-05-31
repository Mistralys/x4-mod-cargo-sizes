<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\ConvertHelper;
use AppUtils\FileHelper;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\ZIPHelper;
use DOMDocument;
use Mistralys\X4\Database\Translations\TranslationExtractor;
use Mistralys\X4\Game\X4Game;
use Mistralys\X4\X4Exception;
use const Mistralys\X4\X4_GAME_FOLDER;

/**
 * Goes through the extracted X4 units folder and extracts the cargo sizes
 * for ships, both transport and miner types. It then generates XML files
 * to override the cargo sizes for each ship type and size combination,
 * using the specified multipliers.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class CargoSizeExtractor
{
    public const SHIP_TYPE_TRANSPORT = 'trans';
    public const SHIP_TYPE_MINER = 'miner';
    public const HOMEPAGE_URL = 'https://github.com/Mistralys/x4-mod-cargo-sizes';
    public const MOD_PREFIX = 'cargo-size';
    public const AUTHOR_NAME = 'AeonsOfTime';

    private FolderInfo $unitsFolder;

    /**
     * @var string[]
     */
    private array $sizes = array(
        'l',
        'm',
        's',
        'xl',
        'xs'
    );

    /**
     * @var string[]
     */
    private const SHIP_TYPES = array(
        self::SHIP_TYPE_TRANSPORT,
        self::SHIP_TYPE_MINER
    );

    /**
     * @var array<int,array{fileName:string,cargo:int,shipType:string,storageType:string,size:string}>
     */
    private array $results = array();

    /**
     * @var array<int,int|float>
     */
    private array $multipliers = array(2);
    private FolderInfo $outputFolder;
    private string $gameVersion;

    /**
     * @var array<string,Translation>
     */
    private array $descriptions = array();

    /**
     * @var array<string,Translation>
     */
    private array $names = array();

    public function __construct(FolderInfo $unitsFolder, FolderInfo $outputFolder)
    {
        $this->unitsFolder = FolderInfo::factory($unitsFolder.'/assets/units');
        $this->outputFolder = $outputFolder;
        $this->gameVersion = X4Game::create(X4_GAME_FOLDER)->getVersion();
    }

    /**
     * @param array<int,int|float> $multipliers
     * @return void
     */
    public function extract(array $multipliers) : void
    {
        $this->multipliers = $multipliers;

        foreach($this->sizes as $size) {
            $sizeFolder = FolderInfo::factory($this->unitsFolder.'/size_'.$size);
            $this->analyzeGameDataFolder($sizeFolder, $size);
        }

        $this->writeFiles();
    }

    private array $zips = array();

    private function writeFiles() : void
    {
        $baseFolder = FolderInfo::factory(sprintf(
            '%s/v%s-for-v%s',
            $this->outputFolder,
            str_replace('.', '-', self::getVersion()),
            str_replace('.', '-', $this->gameVersion)
        ));

        FileHelper::deleteTree($baseFolder);

        foreach($this->multipliers as $multiplier) {
            $this->writeFilesForMultiplier($baseFolder, $multiplier);
        }

        $this->createZIPs($baseFolder);
        $this->cleanUp($baseFolder);
    }

    private function cleanUp(FolderInfo $baseFolder) : void
    {
        foreach($baseFolder->getSubFolders() as $folder) {
            FileHelper::deleteTree($folder);
        }
    }

    private string $multiplierKey = '';

    private function writeFilesForMultiplier(FolderInfo $baseFolder, int|float $multiplier) : void
    {
        $this->multiplierKey = sprintf('aio-%sx', $multiplier);

        $this->descriptions[$this->multiplierKey] = new Translation(Translation::TYPE_DESCR_AIO, array($multiplier));
        $this->names[$this->multiplierKey] = new Translation(Translation::TYPE_NAME_AIO, array($multiplier));

        foreach ($this->results as $result)
        {
            foreach(self::SHIP_TYPES as $shipType)
            {
                if($result['shipType'] !== $shipType) {
                    continue;
                }

                $this->writeFilesForShipType(
                    $baseFolder,
                    $multiplier,
                    $shipType,
                    $result['size'],
                    $result['cargo'],
                    $result['fileName']
                );
            }
        }
    }

    private function getShipTypeDescription(string $shipType, $multiplier) : Translation
    {
        if($shipType === self::SHIP_TYPE_TRANSPORT) {
            $translationID = Translation::TYPE_DESCR_TRANSPORT;
        } else if($shipType === self::SHIP_TYPE_MINER) {
            $translationID = Translation::TYPE_DESCR_MINER;
        } else {
            throw new CargoSizeException(
                'No translation ID found for ship type: '.$shipType,
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        return new Translation($translationID, array($multiplier));
    }

    private function getShipTypeName(string $shipType, $multiplier) : Translation
    {
        if($shipType === self::SHIP_TYPE_TRANSPORT) {
            $translationID = Translation::TYPE_NAME_TRANSPORT;
        } else if($shipType === self::SHIP_TYPE_MINER) {
            $translationID = Translation::TYPE_NAME_MINER;
        } else {
            throw new CargoSizeException(
                'No translation ID found for ship type: '.$shipType,
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        return new Translation($translationID, array($multiplier));
    }

    private function writeFilesForShipType(
        FolderInfo $baseFolder,
        int|float $multiplier,
        string $shipType,
        string $shipSize,
        int $cargo,
        string $fileName
    ) : void
    {
        $typeKey = sprintf(
            '%s-%sx',
            $this->prettifyShipType($shipType),
            $multiplier
        );

        $this->descriptions[$typeKey] = $this->getShipTypeDescription($shipType, $multiplier);
        $this->names[$typeKey] = $this->getShipTypeName($shipType, $multiplier);

        if(!isset($this->zips[$typeKey])) {
            $this->zips[$typeKey] = array();
        }

        foreach ($this->sizes as $size)
        {
            if ($shipSize !== $size) {
                continue;
            }

            $file = $this->writeFile(
                $baseFolder,
                $fileName,
                $cargo,
                $multiplier,
                $shipType,
                $size
            );

            $this->zips[$typeKey][] = $file;

            // Add it to the AIO ZIP as well
            $this->zips[$this->multiplierKey][] = $file;
        }
    }

    private function createZIPs(FolderInfo $baseFolder) : void
    {
        foreach($this->zips as $key => $files)
        {
            $rootName = self::MOD_PREFIX.'-'.$key;

            $zipFile = new ZIPHelper(sprintf(
                '%s/%s-v%s.zip',
                $baseFolder,
                $rootName,
                self::getVersion()
            ));

            echo "Creating ZIP file: $key.zip\n";

            $files = array_unique($files);

            $zipFile->addString($this->renderReadme(), $rootName.'/_readme.txt');
            $zipFile->addString($this->renderContentXML($key), $rootName.'/content.xml');

            foreach($files as $file)
            {
                $zipFile->addFile($file, $rootName.'/'.basename(dirname($file)).'/'.basename($file));
            }

            $zipFile->save();
        }
    }

    private function renderReadme() : string
    {
        $text = <<<TXT
**Dynamically generated ZIP file.**

Mod version: %s
Game version: %s
Generated on: %s

For more details, please refer to the mod homepage at:
%s
TXT;

        return sprintf(
            $text,
            self::getVersion(),
            $this->gameVersion,
            date('Y-m-d H:i:s'),
            CargoSizeExtractor::HOMEPAGE_URL
        );
    }

    private string $template = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- 
    Original cargo value: %1$s
    Multiplier: %3$s 
-->
<diff>
	<replace sel="/macros/macro/properties/cargo/@max">%2$s</replace>
</diff>
XML;

    /**
     * @param FolderInfo $baseFolder
     * @param string $fileName
     * @param int $cargo
     * @param int|float $multiplier
     * @param string $shipType
     * @param string $size
     * @return string The path to the file.
     */
    private function writeFile(FolderInfo $baseFolder, string $fileName, int $cargo, int|float $multiplier, string $shipType, string $size) : string
    {
        $newValue = (int)ceil($cargo * $multiplier);
        $xml = sprintf($this->template, $cargo, $newValue, $multiplier);

        $path = sprintf(
            '%s/%s-%sx-%s-%s/%s',
            $baseFolder,
            self::MOD_PREFIX,
            $multiplier,
            $this->prettifyShipType($shipType),
            $size,
            $fileName
        );

        return FileInfo::factory($path)
            ->putContents($xml."\n")
            ->getPath();
    }

    private function prettifyShipType(string $shipType) : string
    {
        if($shipType === self::SHIP_TYPE_TRANSPORT) {
            return 'transport';
        } else if($shipType === self::SHIP_TYPE_MINER) {
            return 'miner';
        }

        return $shipType;
    }

    /**
     * Goes through the extracted game files to discover all macro XML
     * files that contain cargo.
     *
     * @param FolderInfo $folder
     * @param string $size
     * @return void
     */
    private function analyzeGameDataFolder(FolderInfo $folder, string $size) : void
    {
        $macroFiles = FileHelper::createFileFinder($folder.'/macros')
            ->includeExtension('xml')
            ->getFiles()
            ->typeANY();

        foreach($macroFiles as $macroFile)
        {
            $parts = ConvertHelper::explodeTrim('_', $macroFile->getBaseName());
            $shipType = '';

            if(in_array(self::SHIP_TYPE_TRANSPORT, $parts)) {
                $shipType = self::SHIP_TYPE_TRANSPORT;
            } else if(in_array(self::SHIP_TYPE_MINER, $parts)) {
                $shipType = self::SHIP_TYPE_MINER;
            }

            if(!in_array($shipType, self::SHIP_TYPES)) {
                continue;
            }

            $xml = $macroFile->getContents();
            if(!str_contains($xml, 'generic_storage')) {
                continue;
            }

            $this->registerShip($macroFile, $xml, $size, $shipType);
        }
    }

    private function registerShip(FileInfo $macroFile, string $xml, string $size, string $shipType) : void
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        foreach($dom->getElementsByTagName('cargo') as $el) {
            $value = (int)$el->getAttribute('max');
            $storageType = (string)$el->getAttribute('tags');

            $this->results[] = array(
                'fileName' => $macroFile->getName(),
                'cargo' => $value,
                'shipType' => $shipType,
                'storageType' => $storageType,
                'size' => $size
            );

            break;
        }
    }

    public static function getVersionFile() : FileInfo
    {
        return FileInfo::factory(__DIR__.'/../../../mod-version.txt');
    }

    /**
     * Gets the version of the mod.
     * @return string
     */
    public static function getVersion() : string
    {
        return self::getVersionFile()->getContents();
    }

    /**
     * Renders the content for the `Content.xml` file that is
     * used by X4 to display information on the mod in the
     * "Extensions" UI.
     *
     * @param string $key
     * @return string
     */
    private function renderContentXML(string $key) : string
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<content id="%1$s" name="%2$s" description="%3$s" author="%4$s" version="%5$s" date="%6$s" save="0" enabled="1">
    %7$s
</content>
XML;

        $description = $this->descriptions[$key];
        $name = $this->names[$key];

        $translations = array();
        foreach(array_keys(TranslationExtractor::LANGUAGES) as $langID) {
            $translations[] = sprintf(
                '<text language="%d" name="%s" description="%s" author="%s" />',
                $langID,
                $name->getByLanguageID($langID),
                $description->getByLanguageID($langID),
                self::AUTHOR_NAME
            );
        }

        return sprintf(
            $xml,
            self::MOD_PREFIX .'-'.$key,
            $name->getInvariant(),
            $description->getInvariant(),
            self::AUTHOR_NAME,
            str_replace('.', '', self::getVersion()),
            date('Y-m-d'),
            implode("\n    ", $translations)."\n"
        );
    }
}
