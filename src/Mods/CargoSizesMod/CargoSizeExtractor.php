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
use Mistralys\X4\Database\Translations\TranslationDefs;
use Mistralys\X4\Database\Translations\TranslationExtractor;
use Mistralys\X4\Game\X4Game;
use const Mistralys\X4\X4_EXTRACTED_CAT_FILES_FOLDER;
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
    public const SHIP_TYPE_AUXILIARY = 'resupplier';
    public const SHIP_TYPE_CARRIER = 'carrier';

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
     * Table of ship types with the JSON key names for
     * translating the ship names and descriptions.
     *
     * @var array<string,array{name:string,description:string}>
     */
    public const SHIP_TYPES = array(
        self::SHIP_TYPE_TRANSPORT => array(
            'label' => 'Transport ships',
            'name' => Translation::TYPE_NAME_TRANSPORT,
            'description' => Translation::TYPE_DESCR_TRANSPORT
        ),
        self::SHIP_TYPE_MINER => array(
            'label' => 'Mining ships',
            'name' => Translation::TYPE_NAME_MINER,
            'description' => Translation::TYPE_DESCR_MINER
        ),
        self::SHIP_TYPE_AUXILIARY => array(
            'label' => 'Auxiliaries',
            'name' => Translation::TYPE_NAME_AUXILIARY,
            'description' => Translation::TYPE_DESCR_AUXILIARY
        ),
        self::SHIP_TYPE_CARRIER => array(
            'label' => 'Carriers',
            'name' => Translation::TYPE_NAME_CARRIER,
            'description' => Translation::TYPE_DESCR_CARRIER
        )
    );

    /**
     * @var CargoShipResult[]
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
    private array $keyDescriptions = array();

    /**
     * @var array<string,Translation>
     */
    private array $keyNames = array();

    /*
     * @var array<string,int|float>
     */
    private array $keyMultipliers = array();
    private TranslationDefs $gameTranslations;

    public function __construct(FolderInfo $unitsFolder, FolderInfo $outputFolder)
    {
        $this->unitsFolder = FolderInfo::factory($unitsFolder.'/assets/units');
        $this->outputFolder = $outputFolder;
        $this->gameVersion = X4Game::create(X4_GAME_FOLDER)->getVersion();

        $this->initTranslations();
    }

    private function initTranslations() : void
    {
        $lang = TranslationExtractor::LANGUAGE_ENGLISH;

        $extractor = new TranslationExtractor(FolderInfo::factory(X4_EXTRACTED_CAT_FILES_FOLDER . '/t'));
        $extractor->selectLanguage($lang);
        $extractor->extract();

        $this->gameTranslations = new TranslationDefs($lang);
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
            $this->analyzeCargoStorage($sizeFolder, $size);
            $this->analyzeShipConnections($sizeFolder);
        }

        $this->writeFiles();
    }

    /**
     * @var array<string,MacroFile[]>
     */
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

        $this->writeZIPFiles($baseFolder);
        $this->writeReferenceFiles();
        $this->cleanUp($baseFolder);
    }

    private function cleanUp(FolderInfo $baseFolder) : void
    {
        foreach($baseFolder->getSubFolders() as $folder) {
            FileHelper::deleteTree($folder);
        }
    }

    private function writeReferenceFiles() : void
    {
        $lines = array(
            '# Cargo Sizes Mod - Change Reference',
            ''
        );

        $categorized = array();
        foreach($this->zips as $key => $files)
        {
            $name = $this->keyNames[$key];
            $description = $this->keyDescriptions[$key];
            $multiplier = $this->keyMultipliers[$key];

            if(!isset($categorized[$multiplier])) {
                $categorized[$multiplier] = array();
            }

            $categorized[$multiplier][$key] = array(
                'name' => $name,
                'description' => $description,
                'files' => $files
            );
        }

        foreach($categorized as $multiplier => $keys)
        {
            $lines[] = '## Cargo Size x'.$multiplier;
            $lines[] = '';

            foreach($keys as $data)
            {
                $this->generateZIPReferenceLines(
                    $lines,
                    $data['name'],
                    $data['description'],
                    $data['files']
                );
            }
        }

        FileInfo::factory(__DIR__.'/../../../docs/cargo-size-reference.md')
            ->putContents(implode("\n", $lines)."\n");
    }

    /**
     * @param string[] $lines
     * @param Translation $name
     * @param Translation $description
     * @param MacroFile[] $files
     * @return void
     */
    private function generateZIPReferenceLines(array &$lines, Translation $name, Translation $description, array $files) : void
    {
        $lines[] = '### '.$name->getInvariant();
        $lines[] = '';
        $lines[] = $description->getInvariant();
        $lines[] = '';

        usort($files, static function(MacroFile $a, MacroFile $b) : int {
            return strnatcasecmp($a->getShipName(), $b->getShipName());
        });

        $categorized = array();
        foreach($files as $file) {
            $categorized[$file->getTypeLabel().' '.strtoupper($file->getSize())][] = $file;
        }

        ksort($categorized);

        foreach($categorized as $typeLabel => $files)
        {
            $this->generateTypeReferenceLines($lines, $typeLabel, $files);
        }
    }

    /**
     * @param string[] $lines
     * @param string $typeLabel
     * @param MacroFile[] $files
     * @return void
     */
    private function generateTypeReferenceLines(array &$lines, string $typeLabel, array $files) : void
    {
        $lines[] = '#### '.$typeLabel;
        $lines[] = '';

        foreach($files as $file) {
            $lines[] = sprintf(
                '- _%s_ (%s): %s m3 > **%s m3**',
                $file->getShipName(),
                strtoupper($file->getSize()),
                number_format($file->getCargo(), 0, '.', ','),
                number_format($file->getAdjustedCargo(), 0, '.', ','),
            );
        }

        $lines[] = '';
    }

    private string $multiplierKey = '';

    private function writeFilesForMultiplier(FolderInfo $baseFolder, int|float $multiplier) : void
    {
        $this->multiplierKey = sprintf('aio-%sx', $multiplier);

        $this->keyDescriptions[$this->multiplierKey] = new Translation(Translation::TYPE_DESCR_AIO, array($multiplier));
        $this->keyNames[$this->multiplierKey] = new Translation(Translation::TYPE_NAME_AIO, array($multiplier));
        $this->keyMultipliers[$this->multiplierKey] = $multiplier;

        foreach ($this->results as $result)
        {
            foreach(array_keys(self::SHIP_TYPES) as $shipType)
            {
                if($result->getShipType() !== $shipType) {
                    continue;
                }

                $this->writeFilesForShipType(
                    $baseFolder,
                    $multiplier,
                    $shipType,
                    $result
                );
            }
        }
    }

    private function getShipTypeDescription(string $shipType, $multiplier) : Translation
    {
        return $this->getShipTypeTranslation($shipType, 'description', $multiplier);
    }

    private function getShipTypeTranslation(string $shipType, string $textType, $multiplier) : Translation
    {
        $key = self::SHIP_TYPES[$shipType][$textType] ?? null;

        if($key !== null) {
            return new Translation($key, array($multiplier));
        }

        throw new CargoSizeException(
            sprintf(
                'No translation ID found for ship type [%s] and text key [%s]. '.PHP_EOL.
                'Known ship types are: '.PHP_EOL.
                '- %s',
                $shipType,
                $textType,
                implode(PHP_EOL.'- ', array_keys(self::SHIP_TYPES))
            ),
            '',
            CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }

    private function getShipTypeName(string $shipType, $multiplier) : Translation
    {
        return $this->getShipTypeTranslation($shipType, 'name', $multiplier);
    }

    private function writeFilesForShipType(
        FolderInfo $baseFolder,
        int|float $multiplier,
        string $shipType,
        CargoShipResult $result
    ) : void
    {
        $typeKey = sprintf(
            '%s-%sx',
            self::prettifyShipType($shipType),
            $multiplier
        );

        $this->keyDescriptions[$typeKey] = $this->getShipTypeDescription($shipType, $multiplier);
        $this->keyNames[$typeKey] = $this->getShipTypeName($shipType, $multiplier);
        $this->keyMultipliers[$typeKey] = $multiplier;

        if(!isset($this->zips[$typeKey])) {
            $this->zips[$typeKey] = array();
        }

        foreach ($this->sizes as $size)
        {
            if ($result->getSize() !== $size) {
                continue;
            }

            $file =  new MacroFile(
                $baseFolder,
                $multiplier,
                $result
            );

            $this->zips[$typeKey][$file->getID()] = $file;

            // Add it to the AIO ZIP as well
            $this->zips[$this->multiplierKey][$file->getID()] = $file;
        }
    }

    private function writeZIPFiles(FolderInfo $baseFolder) : void
    {
        foreach($this->zips as $key => $files)
        {
            $rootName = self::MOD_PREFIX.'-'.$key;

            $path = sprintf(
                '%s/%s_v%s.zip',
                $baseFolder,
                $rootName,
                self::getVersion()
            );

            FileInfo::factory($path)->getFolder()->create();

            $zipFile = new ZIPHelper($path);

            echo "Creating ZIP file: $key.zip\n";

            $zipFile->addString($this->renderReadme(), $rootName.'/_readme.txt');
            $zipFile->addString($this->renderContentXML($key), $rootName.'/content.xml');

            foreach($files as $file)
            {
                $path = $file->write();

                $zipFile->addFile(
                    $path,
                    sprintf(
                        '%s/assets/units/size_%s/macros/%s',
                        $rootName,
                        $file->getSize(),
                        basename($path)
                    )
                );
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

    public static function prettifyShipType(string $shipType) : string
    {
        if($shipType === self::SHIP_TYPE_TRANSPORT) {
            return 'transport';
        } else if($shipType === self::SHIP_TYPE_MINER) {
            return 'miner';
        } else if ($shipType === self::SHIP_TYPE_AUXILIARY) {
            return 'auxiliary';
        }

        return $shipType;
    }

    /**
     * Goes through the extracted game files to discover all macro XML
     * files that contain cargo.
     *
     * @param FolderInfo $sizeFolder
     * @param string $size
     * @return void
     */
    private function analyzeCargoStorage(FolderInfo $sizeFolder, string $size) : void
    {
        foreach($this->getSizeMacros($sizeFolder) as $macroFile)
        {
            $shipType = $this->detectShipType($macroFile->getBaseName());

            if($shipType === null) {
                continue;
            }

            $xml = $macroFile->getContents();
            if(!str_contains($xml, 'generic_storage')) {
                continue;
            }

            $this->registerShip($macroFile, $xml, $size, $shipType);
        }
    }

    private function detectShipType(string $macroName) : ?string
    {
        $parts = ConvertHelper::explodeTrim('_', $macroName);

        foreach(array_keys(self::SHIP_TYPES) as $type) {
            if(in_array($type, $parts)) {
                return $type;
            }
        }

        return null;
    }

    private function analyzeShipConnections(FolderInfo $sizeFolder) : void
    {
        $xmlSources = array();
        foreach($this->getSizeMacros($sizeFolder) as $macroFile) {
            $xmlSources[] = $macroFile->getContents();
        }

        foreach($this->results as $result)
        {
            $macroName = $result->getMacroName();
            foreach($xmlSources as $xml) {
                if(str_contains($xml, 'ref="'.$macroName.'"')) {
                    $result->setShipName($this->resolveShipName($xml));
                }
            }
        }
    }

    private function resolveShipName(string $xml) : string
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $translationID = $dom->getElementsByTagName('identification')->item(0)->getAttribute('name');

        return $this->gameTranslations->ts($translationID);
    }

    private function getSizeMacros(FolderInfo $sizeFolder) : array
    {
        return FileHelper::createFileFinder($sizeFolder.'/macros')
            ->includeExtension('xml')
            ->getFiles()
            ->typeANY();
    }

    private function registerShip(FileInfo $macroFile, string $xml, string $size, string $shipType) : void
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        foreach($dom->getElementsByTagName('cargo') as $el)
        {
            $value = (int)$el->getAttribute('max');
            $storageType = (string)$el->getAttribute('tags');

            $this->results[] = new CargoShipResult(
                $dom->getElementsByTagName('macro')->item(0)->getAttribute('name'),
                $macroFile->getName(),
                $value,
                $shipType,
                $storageType,
                $size
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
     * Renders the content for the `content.xml` file that is
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

        $description = $this->keyDescriptions[$key];
        $name = $this->keyNames[$key];

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
