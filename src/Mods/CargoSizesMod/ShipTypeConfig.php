<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\ConvertHelper;
use JsonException;

/**
 * Shared ship type definitions, loaded from `config/ship-types.json`.
 *
 * Used by both the CLI build system (see {@see self::resolveMacroKeyword()})
 * and the GUI backend (see {@see self::resolveType()}), so that the ship type
 * keywords, keyword aliases and labels are defined in a single place.
 *
 * Note on terminology: "keyword aliases" are macro name parts (such as
 * `scavenger`) that are sent to an existing ship type. They are unrelated
 * to XML alias macros, which are skipped by the extractor.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class ShipTypeConfig
{
    /**
     * Ship sizes supported by the mod. This is a closed,
     * game-defined set and is deliberately not configurable.
     */
    public const array SIZES = ['xs', 's', 'm', 'l', 'xl'];

    private static ?ShipTypeConfig $instance = null;

    private string $path;

    /**
     * @var array<string,array{label:string,primaryKeyword:string,macroKeywords:string[]}>
     */
    private array $types = array();

    /**
     * @var array<string,string> Keyword alias => canonical type name.
     */
    private array $keywordAliases = array();

    /**
     * @param string $path Path to a ship types JSON file.
     * @throws CargoSizeException
     */
    private function __construct(string $path)
    {
        $this->path = $path;

        $this->load();
    }

    /**
     * Gets the shared instance, loaded from the default config file.
     *
     * @return self
     * @throws CargoSizeException
     */
    public static function getInstance() : self
    {
        if(self::$instance === null) {
            self::$instance = new self(self::getDefaultPath());
        }

        return self::$instance;
    }

    /**
     * Creates an instance from a specific file, without
     * affecting the shared instance. Mainly for tests.
     *
     * @param string $path
     * @return self
     * @throws CargoSizeException
     */
    public static function createFromFile(string $path) : self
    {
        return new self($path);
    }

    /**
     * Discards the shared instance, so that the next
     * {@see self::getInstance()} call reloads the file.
     */
    public static function resetInstance() : void
    {
        self::$instance = null;
    }

    private static function getDefaultPath() : string
    {
        return __DIR__ . '/../../../config/ship-types.json';
    }

    /**
     * Resolves a macro name to its canonical ship type
     * (`transport`, `mining`, `auxiliary`, `carrier`...).
     *
     * @param string $macroName
     * @return string|null Null if the macro matches no ship type.
     */
    public function resolveType(string $macroName) : ?string
    {
        $parts = ConvertHelper::explodeTrim('_', $macroName);

        $aliasType = $this->findAliasType($parts);
        if($aliasType !== null) {
            return $aliasType;
        }

        foreach($this->types as $type => $data) {
            if($this->findKeyword($data['macroKeywords'], $parts) !== null) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Resolves a macro name to the internal macro keyword (`trans`,
     * `storage`, `miner`...). Keyword aliases resolve to the primary
     * keyword of their target type.
     *
     * @param string $macroName
     * @return string|null Null if the macro matches no ship type.
     */
    public function resolveMacroKeyword(string $macroName) : ?string
    {
        $parts = ConvertHelper::explodeTrim('_', $macroName);

        $aliasType = $this->findAliasType($parts);
        if($aliasType !== null) {
            return $this->types[$aliasType]['primaryKeyword'];
        }

        foreach($this->types as $data) {
            $keyword = $this->findKeyword($data['macroKeywords'], $parts);
            if($keyword !== null) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * @return array{type:string,label:string}[] In the order of the config file.
     */
    public function getTypes() : array
    {
        $result = array();

        foreach($this->types as $type => $data) {
            $result[] = array('type' => $type, 'label' => $data['label']);
        }

        return $result;
    }

    /**
     * @return string[] Canonical type names, in the order of the config file.
     */
    public function getTypeNames() : array
    {
        return array_keys($this->types);
    }

    /**
     * @param string[] $parts
     * @return string|null The type of the first keyword alias (in config order) found in the parts.
     */
    private function findAliasType(array $parts) : ?string
    {
        foreach($this->keywordAliases as $keyword => $type) {
            if(in_array((string)$keyword, $parts, true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param string[] $keywords
     * @param string[] $parts
     * @return string|null The first keyword found in the parts.
     */
    private function findKeyword(array $keywords, array $parts) : ?string
    {
        foreach($keywords as $keyword) {
            if(in_array($keyword, $parts, true)) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * @throws CargoSizeException
     */
    private function load() : void
    {
        if(!is_file($this->path)) {
            throw $this->createError('Ship types config file not found.');
        }

        $json = file_get_contents($this->path);
        if($json === false) {
            throw $this->createError('Ship types config file could not be read.');
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw $this->createError('Ship types config file is not valid JSON: '.$e->getMessage());
        }

        if(!is_array($data) || !isset($data['types']) || !is_array($data['types'])) {
            throw $this->createError('Missing or invalid "types" section.');
        }

        if(empty($data['types'])) {
            throw $this->createError('The "types" section has no ship types.');
        }

        foreach($data['types'] as $type => $typeData) {
            $this->types[(string)$type] = $this->validateType((string)$type, $typeData);
        }

        $aliases = $data['keywordAliases'] ?? array();
        if(!is_array($aliases)) {
            throw $this->createError('Invalid "keywordAliases" section.');
        }

        foreach($aliases as $keyword => $target) {
            if(!is_string($target) || !isset($this->types[$target])) {
                throw $this->createError(sprintf(
                    'Keyword alias [%s] targets an undefined ship type.',
                    $keyword
                ));
            }

            $this->keywordAliases[(string)$keyword] = $target;
        }
    }

    /**
     * @param string $type
     * @param mixed $typeData
     * @return array{label:string,primaryKeyword:string,macroKeywords:string[]}
     * @throws CargoSizeException
     */
    private function validateType(string $type, mixed $typeData) : array
    {
        if(!is_array($typeData)) {
            throw $this->createError(sprintf('Ship type [%s] must be an object.', $type));
        }

        $label = $typeData['label'] ?? null;
        $primary = $typeData['primaryKeyword'] ?? null;
        $keywords = $typeData['macroKeywords'] ?? null;

        if(!is_string($label) || $label === '') {
            throw $this->createError(sprintf('Ship type [%s] has no label.', $type));
        }

        if(!is_array($keywords) || empty($keywords)) {
            throw $this->createError(sprintf('Ship type [%s] has no macroKeywords.', $type));
        }

        foreach($keywords as $keyword) {
            if(!is_string($keyword) || $keyword === '') {
                throw $this->createError(sprintf('Ship type [%s] has an invalid macro keyword.', $type));
            }
        }

        if(!is_string($primary) || !in_array($primary, $keywords, true)) {
            throw $this->createError(sprintf(
                'The primaryKeyword of ship type [%s] must be one of its macroKeywords.',
                $type
            ));
        }

        return array(
            'label' => $label,
            'primaryKeyword' => $primary,
            'macroKeywords' => array_values($keywords)
        );
    }

    private function createError(string $message) : CargoSizeException
    {
        return new CargoSizeException(
            $message.' Config file: '.$this->path,
            '',
            CargoSizeException::ERROR_INVALID_CONFIG
        );
    }
}
