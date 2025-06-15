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
use Misc\Mods\CargoSizesMod\FOMOD\FileCollection;
use Mistralys\X4\Database\Translations\Languages;
use Mistralys\X4\Database\Translations\TranslationDefs;
use Mistralys\X4\Database\Translations\TranslationExtractor;
use Mistralys\X4\ExtractedData\DataFolder;
use Mistralys\X4\ExtractedData\DataFolders;
use Mistralys\X4\Game\X4Game;
use Mistralys\X4\Mods\CargoSizesMod\FOMOD\FomodWriter;
use Mistralys\X4\Mods\CargoSizesMod\Output\FlightMechanicsOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\References\BBCodeReference;
use Mistralys\X4\Mods\CargoSizesMod\References\MarkdownReference;
use Mistralys\X4\Mods\CargoSizesMod\XML\CargoXMLFile;
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
    public const SHIP_TYPE_STORAGE = 'storage';
    public const SHIP_TYPE_MINER = 'miner';
    public const SHIP_TYPE_AUXILIARY = 'resupplier';
    public const SHIP_TYPE_CARRIER = 'carrier';

    public const HOMEPAGE_URL = 'https://github.com/Mistralys/x4-mod-cargo-sizes';
    public const MOD_PREFIX = 'cargo-size';
    public const AUTHOR_NAME = 'AeonsOfTime';
    public const PROPS_FOLDER = 'assets/props/StorageModules/macros';
    const UNITS_FOLDER = 'assets/units/size_%s/macros';
    const FILE_PROP_FOLDER_RELATIVE = 'folderRelative';

    public const SHIP_SIZES = array(
        'xs',
        's',
        'm',
        'l',
        'xl'
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
        self::SHIP_TYPE_STORAGE => array(
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

    private FolderInfo $extractedDataFolder;
    private DataFolders $dataFolders;

    public function __construct(FolderInfo $extractedDataFolder, FolderInfo $outputFolder)
    {
        $this->extractedDataFolder = $extractedDataFolder;
        $this->outputFolder = $outputFolder;
        $this->gameVersion = X4Game::create(X4_GAME_FOLDER)->getVersion();

        $this->dataFolders = DataFolders::create($extractedDataFolder);
    }

    private static ?TranslationDefs $translations = null;

    public static function getTranslations() : TranslationDefs
    {
        if(isset(self::$translations)) {
            return self::$translations;
        }

        self::$translations = new TranslationDefs(Languages::LANGUAGE_ENGLISH);

        return self::$translations;
    }

    /**
     * @param array<int,int|float> $multipliers
     * @return void
     */
    public function extract(array $multipliers) : void
    {
        $this->multipliers = $multipliers;

        FileCollection::reset();

        $this->analyzeCargoMacros();
        $this->analyzeShipMacros();
        $this->writeFiles();
    }

    /**
     * @return string[]
     */
    public static function getShipTypesPretty() : array
    {
        $result = array();

        foreach(self::SHIP_TYPES as $type => $data) {
            $pretty = self::prettifyShipType($type);
            if(!in_array($pretty, $result)) {
                $result[] = $pretty;
            }
        }

        return $result;
    }

    /**
     * @var array<string,StorageOverrideFile[]>
     */
    private array $zips = array();

    private function writeFiles() : void
    {
        $baseFolder = FolderInfo::factory(sprintf(
            '%s/v%s-for-v%s',
            $this->outputFolder,
            str_replace('.', '-', ModInfo::getVersion()),
            str_replace('.', '-', $this->gameVersion)
        ));

        Console::header('Writing mod files');
        Console::line1('Output folder: [%s]', $baseFolder->getName());

        FileHelper::deleteTree($baseFolder);

        $baseFolder->create();

        foreach($this->multipliers as $multiplier) {
            $this->writeFilesForMultiplier($baseFolder, $multiplier);
        }

        $this->writeZIPFiles($baseFolder);
        $this->writeFomodFiles();
        $this->writeReferenceFiles();
        $this->cleanUp($baseFolder);
    }

    private function writeFomodFiles() : void
    {
        (new FomodWriter(
            $this->multipliers,
            $this->outputFolder,
            $this->dataFolders
        ))->write();
    }

    private function cleanUp(FolderInfo $baseFolder) : void
    {
        foreach($baseFolder->getSubFolders() as $folder) {
            FileHelper::deleteTree($folder);
        }
    }

    private function writeReferenceFiles() : void
    {
        Console::header('Writing reference files');

        $this->writeNexusBBCodeReference();
        $this->writeMarkdownReference();
    }

    private function writeMarkdownReference() : void
    {
        Console::line1('Writing Markdown reference file.');

        (new MarkdownReference($this->multipliers, $this->getResultsCategorized()))->write();
    }

    private function writeNexusBBCodeReference() : void
    {
        Console::line1('Writing Nexus BBCode reference file.');

        FileInfo::factory(__DIR__.'/../../../docs/nexus-description.bbcode')
            ->putContents(str_replace(
                '{{CARGO_SIZES_REFERENCE}}',
                (new BBCodeReference($this->multipliers, $this->getResultsCategorized()))->generate(),
                FileInfo::factory(__DIR__.'/../../../docs/nexus-description.bbcode.tpl')->getContents()
            ));
    }

    private function getResultsCategorized() : array
    {
        $categorized = array();

        foreach($this->results as $result)
        {
            $type = $result->getTypeLabel().' '.strtoupper($result->getSize());
            if(!isset($categorized[$type])) {
                $categorized[$type] = array();
            }

            $categorized[$type][] = $result;
        }

        uksort($categorized, 'strnatcasecmp');

        return $categorized;
    }

    private string $multiplierKey = '';

    private function writeFilesForMultiplier(FolderInfo $baseFolder, int|float $multiplier) : void
    {
        Console::line1('Processing multiplier x%s', $multiplier);

        $this->multiplierKey = sprintf('aio-%sx', $multiplier);

        $this->keyDescriptions[$this->multiplierKey] = new Translation(Translation::TYPE_DESCR_AIO, array($multiplier));
        $this->keyNames[$this->multiplierKey] = new Translation(Translation::TYPE_NAME_AIO, array($multiplier));

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

        Console::line1('Done.');
        Console::nl();
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

        if(!isset($this->zips[$typeKey])) {
            $this->zips[$typeKey] = array();
        }

        foreach (self::SHIP_SIZES as $size)
        {
            if ($result->getSize() !== $size) {
                continue;
            }

            $this->writeShipFiles(
                $typeKey,
                $baseFolder,
                $multiplier,
                $result
            );
        }
    }

    private function writeShipFiles(
        string $typeKey,
        FolderInfo $baseFolder,
        int|float $multiplier,
        CargoShipResult $result
    ) : void
    {
        $this->registerOverrideFile(
            $typeKey,
            $multiplier,
            new StorageOverrideFile(
                $baseFolder,
                $multiplier,
                $result
            )
        );

        $this->registerOverrideFile(
            $typeKey,
            $multiplier,
            new FlightMechanicsOverrideFile(
                $baseFolder,
                $multiplier,
                $result
            )
        );
    }

    private function registerOverrideFile(string $typeKey, int|float $multiplier, BaseOverrideFile $file) : void
    {
        $this->zips[$typeKey][$file->getID()] = $file;

        // Add it to the AIO ZIP as well
        $this->zips[$this->multiplierKey][$file->getID()] = $file;

        FileCollection::create($file->getShipType(), $file->getShipSize(), $multiplier)->addFile($file);

        Console::line2(
            'Written file [%s].',
            $file->getName(),
            $file->getShipType(),
            $file->getShipSize()
        );
    }

    private function writeZIPFiles(FolderInfo $baseFolder) : void
    {
        Console::header('Writing ZIP files');

        Console::line1('Found %s zip files to write.', count($this->zips));

        foreach($this->zips as $key => $files)
        {
            $rootName = self::MOD_PREFIX.'-'.$key;

            $path = sprintf(
                '%s/%s_v%s.zip',
                $baseFolder,
                $rootName,
                ModInfo::getVersion()
            );

            FileInfo::factory($path)->getFolder()->create();

            $zipFile = new ZIPHelper($path);

            Console::line1("Creating ZIP file [%s].", $key);

            $zipFile->addString($this->renderReadme(), $rootName.'/_readme.txt');
            $zipFile->addString($this->renderContentXML($key), $rootName.'/content.xml');

            foreach($files as $file)
            {
                if(empty($file->getShipName())) {
                    Console::line2('SKIP | No ship name for [%s].', $file->getName());
                    continue;
                }

                $zipFile->addString(
                    $file->render(),
                    $file->getZipPath($rootName)
                );
            }

            $zipFile->save();
        }

        Console::line1('Done.');
        Console::nl();
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
            ModInfo::getVersion(),
            $this->gameVersion,
            date('Y-m-d H:i:s'),
            CargoSizeExtractor::HOMEPAGE_URL
        );
    }

    public static function prettifyShipType(string $shipType) : string
    {
        if($shipType === self::SHIP_TYPE_TRANSPORT) {
            return 'transport';
        } else if($shipType === self::SHIP_TYPE_STORAGE) {
            return 'transport';
        } else if($shipType === self::SHIP_TYPE_MINER) {
            return 'miner';
        } else if ($shipType === self::SHIP_TYPE_AUXILIARY) {
            return 'auxiliary';
        }

        return $shipType;
    }

    private function resolveShipType(string $macroName) : ?string
    {
        $parts = ConvertHelper::explodeTrim('_', $macroName);

        foreach(array_keys(self::SHIP_TYPES) as $type) {
            if(in_array($type, $parts)) {
                return $type;
            }
        }

        return null;
    }

    private function analyzeShipMacros() : void
    {
        Console::header('Analyze ship macros');

        foreach ($this->getShipMacros() as $shipMacro) {
            $this->analyzeShipMacro($shipMacro);
        }

        Console::line1('Done, found %s ship macros.', count($this->results));
        Console::nl();
    }

    private function addMessage(string $message, ...$args) : void
    {
        $this->messages[] = vsprintf($message, $args);
    }

    private function analyzeShipMacro(ShipXMLFile $shipXMLFile) : void
    {
        $macroName = $shipXMLFile->getMacroName();

        $shipType = $this->resolveShipType($macroName);
        if($shipType === null) {
            $this->addMessage("SKIP | Unsupported ship type in [%s]", $macroName);
            return;
        }

        $cargoMacroID = $this->resolveCargoConnection($shipXMLFile);
        if ($cargoMacroID === null) {
            $this->addMessage('SKIP | No cargo connection found in [%s].', $macroName);
            return;
        }

        if(!isset($this->cargoMacros[$cargoMacroID])) {
            $this->addMessage('SKIP | No cargo macro found in [%s]. Expected macro [%s] but it does not exist.', $cargoMacroID);
            return;
        }

        $cargoXMLFile = $this->cargoMacros[$cargoMacroID];

        $label = $this->resolveShipLabel($shipXMLFile);
        if(empty($label)) {
            $this->addMessage('SKIP | No ship label found for [%s].', $macroName);
            return;
        }

        Console::line2('Found ship [%s].', $label);

        $this->results[] = new CargoShipResult(
            $label,
            $shipType,
            $shipXMLFile,
            $cargoXMLFile
        );
    }

    public function resolveCargoConnection(ShipXMLFile $xml) : ?string
    {
        foreach($xml->getConnections() as $connection) {
            $ref = $connection->getAttribute('ref');
            if(str_starts_with($ref, 'con_storage')) {
                return $connection->getElementsByTagName('macro')->item(0)
                    ->getAttribute('ref');
            }
        }

        return null;
    }

    private function resolveShipLabel(ShipXMLFile $xml) : string
    {
        $macroName = $xml->getMacroName();

        $name = $xml->resolveShipLabel();
        if ($name !== null) {
            return $name;
        }

        // No translation ID found: This can happen when the macro is an alias for
        // another macro, e.g. "ship_xen_m_corvette_01_b_macro" is an alias for "ship_xen_m_corvette_01_a_macro".
        // In this case, we have to load the original macro to get the name.

        $aliasName = $xml->getAliasName();
        if ($aliasName !== null) {
            foreach ($this->getShipMacros() as $macro) {
                if ($macro->getMacroName() === $aliasName) {
                    return $this->resolveShipLabel($macro);
                }
            }
        }

        Console::line2('WARNING | No ship name translation ID found for [%s].', $macroName);

        return $macroName;
    }

    /**
     * @var ShipXMLFile[]|null
     */
    private ?array $shipMacros = null;

    /**
     * @return ShipXMLFile[]
     */
    private function getShipMacros() : array
    {
        if(isset($this->shipMacros)) {
            return $this->shipMacros;
        }

        $this->shipMacros = array();

        foreach(self::SHIP_SIZES as $shipSize) {
            foreach($this->dataFolders->getAll() as $dataFolder) {
                foreach ($this->getXMLFiles($dataFolder->getFolder() . '/' . sprintf(self::UNITS_FOLDER, $shipSize)) as $file) {
                    if (str_starts_with($file->getBaseName(), 'ship_')) {
                        $this->shipMacros[] = new ShipXMLFile($file, $dataFolder);
                    }
                }
            }
        }

        return $this->shipMacros;
    }

    /**
     * @var array<string,CargoXMLFile>
     */
    private array $cargoMacros = array();

    /**
     * Analyzes the cargo macros in the game folder and stores them,
     * for each adding the relative folder path that will be used
     * for the output XML files in the ZIP archives (as accessible
     * via {@see CargoXMLFile::getRelativePath()}).
     */
    private function analyzeCargoMacros() : void
    {
        Console::header('Analyze cargo macros');

        foreach($this->dataFolders->getAll() as $dataFolder)
        {
            Console::line1('Analyzing data folder [%s]...', $dataFolder->getLabel());

            $files = array();

            foreach ($this->getXMLFiles($dataFolder->getFolder() . '/' . self::PROPS_FOLDER) as $file) {
                $files[] = $file->setRuntimeProperty(self::FILE_PROP_FOLDER_RELATIVE, self::PROPS_FOLDER);
            }

            foreach (self::SHIP_SIZES as $shipSize) {
                $relative = sprintf(self::UNITS_FOLDER, $shipSize);
                foreach ($this->getXMLFiles($dataFolder->getFolder() . '/' . $relative) as $file) {
                    $files[] = $file->setRuntimeProperty(self::FILE_PROP_FOLDER_RELATIVE, $relative);
                }
            }

            Console::line1('Found %s XML files to analyze.', count($files));

            foreach ($files as $file) {
                $this->analyzeCargoMacro($file, $dataFolder);
            }
        }

        Console::line1('Done, found %s cargo macros.', count($this->cargoMacros));
        Console::nl();
    }

    private array $messages = array();

    private function analyzeCargoMacro(FileInfo $file, DataFolder $dataFolder) : void
    {
        if(!str_starts_with($file->getBaseName(), 'storage_')) {
            return;
        }

        $macro = new CargoXMLFile($file, $dataFolder);
        $macroName = $macro->getMacroName();

        if(!$macro->isGenericStorage()) {
            Console::line2('SKIP | Not a cargo macro in [%s]', $macroName);
            return;
        }

        if(!$macro->hasCargoValue()) {
            Console::line2('SKIP | No cargo value in [%s].', $macroName);
            return;
        }

        $this->cargoMacros[$macroName] = $macro;
    }

    /**
     * @param FolderInfo|string $folder
     * @return FileInfo[]
     */
    private function getXMLFiles(FolderInfo|string $folder) : array
    {
        $info = FolderInfo::factory($folder);

        if(!$info->exists()) {
            return array();
        }

        return $info
            ->createFileFinder()
            ->includeExtension('xml')
            ->getFiles()
            ->typeANY();
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
        return (new ContentXMLRenderer(
            self::MOD_PREFIX .'-'.$key,
            $this->keyNames[$key],
            $this->keyDescriptions[$key],
            $this->dataFolders
        ))->render();
    }

    public static function getTypeLabel(string $shipType) : string
    {
        if(isset(self::SHIP_TYPES[$shipType])) {
            return self::SHIP_TYPES[$shipType]['label'];
        }

        foreach(array_keys(self::SHIP_TYPES) as $rawType) {
            $pretty = self::prettifyShipType($rawType);
            if($pretty === $shipType) {
                return self::getTypeLabel($rawType);
            }
        }

        throw new CargoSizeException(
            sprintf('Unknown label for ship type [%s].', $shipType),
            '',
            CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }
}
