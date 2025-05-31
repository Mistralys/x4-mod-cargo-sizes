<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\Database\Translations\TranslationExtractor;

class Translation
{
    public const TYPE_DESCR_AIO = 'descr-aio';
    public const TYPE_DESCR_TRANSPORT = 'descr-transport';
    public const TYPE_DESCR_MINER = 'descr-miner';
    public const TYPE_NAME_AIO = 'name-aio';
    public const TYPE_NAME_TRANSPORT = 'name-transport';
    public const TYPE_NAME_MINER = 'name-miner';

    /**
     * @var string[]
     */
    private array $placeholders;

    /**
     * @var array<int,string> Language ID to text mapping.
     */
    private array $translations;

    public function __construct(string $id, array $placeholders = array())
    {
        $this->placeholders = $placeholders;
        $this->translations = self::getTranslations($id);
    }

    private static ?array $strings = null;

    private static function loadStrings() : array
    {
        if(!isset(self::$strings)) {
            self::$strings = JSONFile::factory(__DIR__.'/../../../config/translations.json')->parse();
        }

        return self::$strings;
    }

    /**
     * @param string $id
     * @return array<int,string> Language ID to text mapping.
     */
    private static function getTranslations(string $id) : array
    {
        $strings = self::loadStrings();

        if(!isset($strings[$id]) || !is_array($strings[$id])) {
            return array();
        }

        $result = array();
        foreach($strings[$id] as $locale => $text) {
            if(!is_string($text)) {
                continue;
            }

            foreach(TranslationExtractor::LANGUAGES as $langID => $localeCode) {
                if($locale === $localeCode) {
                    $result[$langID] = $text;
                    break;
                }
            }
        }

        return $result;
    }

    public function getInvariant() : string
    {
        return $this->getByLanguageID(TranslationExtractor::LANGUAGE_ENGLISH);
    }

    public function getByLanguageID(int $id) : string
    {
        if(!isset($this->translations[$id])) {
            $id = TranslationExtractor::LANGUAGE_ENGLISH; // Fallback to English if the requested language is not available.
        }

        if(empty($this->translations[$id])) {
            return '';
        }

        return sprintf($this->translations[$id] ?? '', ...$this->placeholders);
    }
}
