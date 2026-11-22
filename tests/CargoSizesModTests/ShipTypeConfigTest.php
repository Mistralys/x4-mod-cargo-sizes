<?php
/**
 * Tests for the shared ship type configuration.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\ShipTypeConfig;
use PHPUnit\Framework\TestCase;

class ShipTypeConfigTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../files/ship-types/';

    protected function tearDown(): void
    {
        ShipTypeConfig::resetInstance();
    }

    public function testResolveTypeStandardKeywords(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertSame('transport', $config->resolveType('ship_arg_l_trans_01_a_macro'));
        $this->assertSame('transport', $config->resolveType('storage_arg_l_storage_01_a_macro'));
        $this->assertSame('mining', $config->resolveType('ship_arg_l_miner_solid_01_a_macro'));
        $this->assertSame('auxiliary', $config->resolveType('ship_arg_m_resupplier_01_a_macro'));
        $this->assertSame('carrier', $config->resolveType('ship_arg_xl_carrier_01_a_macro'));
    }

    public function testResolveTypeKeywordAliases(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertSame('transport', $config->resolveType('ship_pir_l_scavenger_01_a_macro'));
        $this->assertSame('mining', $config->resolveType('ship_xen_l_terraformer_01_a_macro'));
    }

    public function testResolveMacroKeywordStandard(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertSame('trans', $config->resolveMacroKeyword('ship_arg_l_trans_01_a_macro'));
        $this->assertSame('storage', $config->resolveMacroKeyword('ship_arg_l_storage_01_a_macro'));
        $this->assertSame('miner', $config->resolveMacroKeyword('ship_arg_l_miner_solid_01_a_macro'));
        $this->assertSame('resupplier', $config->resolveMacroKeyword('ship_arg_m_resupplier_01_a_macro'));
        $this->assertSame('carrier', $config->resolveMacroKeyword('ship_arg_xl_carrier_01_a_macro'));
    }

    public function testResolveMacroKeywordAliasReturnsPrimaryKeyword(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertSame('trans', $config->resolveMacroKeyword('ship_pir_l_scavenger_01_a_macro'));
        $this->assertSame('miner', $config->resolveMacroKeyword('ship_xen_l_terraformer_01_a_macro'));
    }

    public function testKeywordAliasesAreCheckedBeforeStandardKeywords(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertSame('transport', $config->resolveType('ship_xxx_l_scavenger_miner_01_macro'));
        $this->assertSame('trans', $config->resolveMacroKeyword('ship_xxx_l_scavenger_miner_01_macro'));
    }

    public function testResolveUnknownReturnsNull(): void
    {
        $config = ShipTypeConfig::getInstance();

        $this->assertNull($config->resolveType('ship_arg_s_fighter_01_a_macro'));
        $this->assertNull($config->resolveMacroKeyword('ship_arg_s_fighter_01_a_macro'));
    }

    public function testGetTypes(): void
    {
        $this->assertSame(
            array(
                array('type' => 'transport', 'label' => 'Transport ships'),
                array('type' => 'mining', 'label' => 'Mining ships'),
                array('type' => 'auxiliary', 'label' => 'Auxiliaries'),
                array('type' => 'carrier', 'label' => 'Carriers')
            ),
            ShipTypeConfig::getInstance()->getTypes()
        );
    }

    public function testGetTypeNames(): void
    {
        $this->assertSame(
            array('transport', 'mining', 'auxiliary', 'carrier'),
            ShipTypeConfig::getInstance()->getTypeNames()
        );
    }

    public function testSizesConstant(): void
    {
        $this->assertSame(array('xs', 's', 'm', 'l', 'xl'), ShipTypeConfig::SIZES);
    }

    public function testEveryMacroKeywordHasShipTypesEntry(): void
    {
        $data = json_decode((string)file_get_contents(__DIR__ . '/../../config/ship-types.json'), true);

        foreach($data['types'] as $type => $typeData) {
            foreach($typeData['macroKeywords'] as $keyword) {
                $this->assertArrayHasKey(
                    $keyword,
                    CargoSizeExtractor::SHIP_TYPES,
                    sprintf('Keyword [%s] of type [%s] has no SHIP_TYPES entry.', $keyword, $type)
                );
            }
        }
    }

    public function testInvalidPrimaryKeywordThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('invalid-primary-keyword.json');

        ShipTypeConfig::createFromFile(self::FIXTURES.'invalid-primary-keyword.json');
    }

    public function testFixtureKeywordAliasIsResolved(): void
    {
        $config = ShipTypeConfig::createFromFile(self::FIXTURES.'extra-alias.json');

        $this->assertSame('carrier', $config->resolveType('ship_xxx_xl_freighter_01_macro'));
        $this->assertSame('carrier', $config->resolveMacroKeyword('ship_xxx_xl_freighter_01_macro'));
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('does-not-exist.json');

        ShipTypeConfig::createFromFile(self::FIXTURES.'does-not-exist.json');
    }

    public function testMalformedJsonThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('malformed.json');

        ShipTypeConfig::createFromFile(self::FIXTURES.'malformed.json');
    }

    public function testInvalidAliasTargetThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('invalid-alias-target.json');

        ShipTypeConfig::createFromFile(self::FIXTURES.'invalid-alias-target.json');
    }

    public function testTypeWithoutMacroKeywordsThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('no-macro-keywords.json');

        ShipTypeConfig::createFromFile(self::FIXTURES.'no-macro-keywords.json');
    }
}
