<?php
/**
 * Tests for deterministic example ship selection logic in FileCollection
 * and ReleaseNotesGenerator.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use AppUtils\FileHelper\FolderInfo;
use Misc\Mods\CargoSizesMod\FOMOD\FileCollection;
use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\References\ReleaseNotesGenerator;
use Mistralys\X4\Mods\CargoSizesMod\ShipResult;
use Mistralys\X4\Mods\CargoSizesMod\StorageOverrideFile;
use PHPUnit\Framework\TestCase;

/**
 * Exposes the protected formatComparisonTable() method for unit testing.
 * Defined in the test file only — not part of production code.
 */
class TestableReleaseNotesGenerator extends ReleaseNotesGenerator
{
    public function publicFormatComparisonTable(): string
    {
        return $this->formatComparisonTable();
    }
}

/**
 * Unit tests for deterministic example ship selection.
 *
 * All tests use PHPUnit mock objects — no real X4 XML game files are required.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class ExampleShipSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        FileCollection::reset();
    }

    protected function tearDown(): void
    {
        FileCollection::reset();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns a BuildConfig stub that reports no configured ships (all empty).
     */
    private function makeEmptyConfig(): BuildConfig
    {
        $config = $this->createMock(BuildConfig::class);
        $config->method('getExampleShip')->willReturn('');
        return $config;
    }

    /**
     * Returns a temp-dir FolderInfo suitable for constructing ReleaseNotesGenerator
     * without touching the real build folder.
     */
    private function makeBuildFolder(): FolderInfo
    {
        return FolderInfo::factory(sys_get_temp_dir());
    }

    /**
     * Creates a transport ShipResult mock with the given label and base cargo.
     */
    private function makeTransportShip(string $label, int $cargo = 1000): ShipResult
    {
        $ship = $this->createMock(ShipResult::class);
        $ship->method('getShipType')->willReturn(CargoSizeExtractor::SHIP_TYPE_TRANSPORT);
        $ship->method('getShipLabel')->willReturn($label);
        $ship->method('getCargoValue')->willReturn($cargo);
        $ship->method('calculateCargoValue')->willReturnCallback(
            static fn(float|int $m): int => (int)($cargo * $m)
        );
        return $ship;
    }

    /**
     * Creates a miner ShipResult mock (not a transport ship).
     */
    private function makeMinerShip(string $label): ShipResult
    {
        $ship = $this->createMock(ShipResult::class);
        $ship->method('getShipType')->willReturn(CargoSizeExtractor::SHIP_TYPE_MINER);
        $ship->method('getShipLabel')->willReturn($label);
        $ship->method('getCargoValue')->willReturn(500);
        $ship->method('calculateCargoValue')->willReturnCallback(
            static fn(float|int $m): int => (int)(500 * $m)
        );
        return $ship;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FileCollection::getExampleShipDescription() tests (via getPluginDescription)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifies the example description format: ship name, original cargo,
     * and adjusted cargo all appear in the output.
     */
    public function testExampleShipDescriptionFormat(): void
    {
        $storage = $this->createMock(StorageOverrideFile::class);
        $storage->method('getShipName')->willReturn('Courier Sentinel');
        $storage->method('getCargo')->willReturn(1000);
        $storage->method('getAdjustedCargo')->willReturn(4000);

        $collection = FileCollection::create('trans', 's', 4);
        $collection->addFile($storage);

        // No configured ship — the sole entry is picked by default fallback.
        FileCollection::setConfig($this->makeEmptyConfig());

        $result = $collection->getPluginDescription();

        $this->assertStringContainsString('Courier Sentinel', $result);
        $this->assertStringContainsString('1,000', $result);
        $this->assertStringContainsString('4,000', $result);
    }

    /**
     * Verifies that when a matching configured ship is found in the collection,
     * it is selected over any other entry.
     */
    public function testExampleShipDescriptionDeterministicSelectionPicksConfiguredShip(): void
    {
        $shipA = $this->createMock(StorageOverrideFile::class);
        $shipA->method('getShipName')->willReturn('ShipA');
        $shipA->method('getCargo')->willReturn(1000);
        $shipA->method('getAdjustedCargo')->willReturn(2000);

        $shipB = $this->createMock(StorageOverrideFile::class);
        $shipB->method('getShipName')->willReturn('ShipB');
        $shipB->method('getCargo')->willReturn(1500);
        $shipB->method('getAdjustedCargo')->willReturn(3000);

        $collection = FileCollection::create('trans', 's', 2);
        $collection->addFile($shipA);
        $collection->addFile($shipB);

        $config = $this->createMock(BuildConfig::class);
        $config->method('getExampleShip')->with('trans', 's')->willReturn('ShipB');
        FileCollection::setConfig($config);

        $result = $collection->getPluginDescription();

        $this->assertStringContainsString('ShipB', $result);
        $this->assertStringNotContainsString('ShipA', $result);
    }

    /**
     * Verifies that when the configured ship label is not in the collection,
     * the method falls back gracefully and still returns a non-empty description.
     */
    public function testExampleShipDescriptionFallbackWhenConfiguredShipNotFound(): void
    {
        $shipA = $this->createMock(StorageOverrideFile::class);
        $shipA->method('getShipName')->willReturn('ShipA');
        $shipA->method('getCargo')->willReturn(1000);
        $shipA->method('getAdjustedCargo')->willReturn(2000);

        $shipB = $this->createMock(StorageOverrideFile::class);
        $shipB->method('getShipName')->willReturn('ShipB');
        $shipB->method('getCargo')->willReturn(1500);
        $shipB->method('getAdjustedCargo')->willReturn(3000);

        // Different type+size so this collection ID doesn't collide with other tests.
        $collection = FileCollection::create('trans', 'm', 2);
        $collection->addFile($shipA);
        $collection->addFile($shipB);

        $config = $this->createMock(BuildConfig::class);
        $config->method('getExampleShip')->willReturn('ShipC'); // Not present
        FileCollection::setConfig($config);

        $result = $collection->getPluginDescription();

        // Fallback must produce a valid ship description (ShipA or ShipB).
        $fallbackPresent = str_contains($result, 'ShipA') || str_contains($result, 'ShipB');
        $this->assertTrue($fallbackPresent, 'Expected graceful fallback to a ship present in the collection');
    }

    /**
     * Verifies that when only non-StorageOverrideFile entries exist (or collection
     * is empty), the method returns an empty string — the "Unchanged" invariant.
     */
    public function testExampleShipDescriptionEmptyWhenNoStorageFiles(): void
    {
        // Collection with only a BaseOverrideFile (not a StorageOverrideFile) — no example ship.
        $collection = FileCollection::create('trans', 'l', 4);
        // No addFile() calls — collection stays empty.

        FileCollection::setConfig($this->makeEmptyConfig());

        $description = $collection->getPluginDescription();

        $this->assertStringNotContainsString('Example:', $description);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ReleaseNotesGenerator::formatComparisonTable() tests
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifies the table header row and that the ship name appears in each
     * multiplier row.
     */
    public function testComparisonTableFormat(): void
    {
        $ship = $this->makeTransportShip('Courier Sentinel', 1000);

        $generator = new TestableReleaseNotesGenerator(
            $this->makeBuildFolder(),
            [2, 4],
            [$ship],
            $this->makeEmptyConfig()
        );

        $result = $generator->publicFormatComparisonTable();

        $this->assertStringContainsString('## Cargo Multiplier Comparison', $result);
        $this->assertStringContainsString('Courier Sentinel', $result);
        $this->assertStringContainsString('AIO x2', $result);
        $this->assertStringContainsString('AIO x4', $result);
        $this->assertStringContainsString('Claytronics', $result);
    }

    /**
     * Verifies that cargo math is correct: each row shows original × multiplier.
     */
    public function testComparisonTableMath(): void
    {
        $ship = $this->makeTransportShip('TestShip', 1000);

        $generator = new TestableReleaseNotesGenerator(
            $this->makeBuildFolder(),
            [2.0, 4.0],
            [$ship],
            $this->makeEmptyConfig()
        );

        $result = $generator->publicFormatComparisonTable();

        $this->assertStringContainsString('2,000', $result); // 1000 × 2
        $this->assertStringContainsString('4,000', $result); // 1000 × 4
        $this->assertStringContainsString('83', $result);    // floor(2000 / 24) = 83 Claytronics
        $this->assertStringContainsString('166', $result);   // floor(4000 / 24) = 166 Claytronics
    }

    /**
     * Verifies deterministic ship selection: when 'trans-s' maps to 'ShipB',
     * the table uses ShipB and not ShipA.
     */
    public function testComparisonTableDeterministicSelectionPicksConfiguredShip(): void
    {
        $shipA = $this->makeTransportShip('ShipA', 2000);
        $shipB = $this->makeTransportShip('ShipB', 1000);

        $config = $this->createMock(BuildConfig::class);
        $config->method('getExampleShip')->willReturnCallback(
            static function(string $type, string $size): string {
                return ($type === CargoSizeExtractor::SHIP_TYPE_TRANSPORT && $size === 's') ? 'ShipB' : '';
            }
        );

        $generator = new TestableReleaseNotesGenerator(
            $this->makeBuildFolder(),
            [2],
            [$shipA, $shipB],
            $config
        );

        $result = $generator->publicFormatComparisonTable();

        $this->assertStringContainsString('ShipB', $result);
        $this->assertStringNotContainsString('ShipA', $result);
    }

    /**
     * Verifies that when the configured ship is absent from build data, the
     * method falls back gracefully and still returns a non-empty table.
     */
    public function testComparisonTableFallbackWhenConfiguredShipNotFound(): void
    {
        $ship = $this->makeTransportShip('ShipA', 1000);

        $config = $this->createMock(BuildConfig::class);
        $config->method('getExampleShip')->willReturn('ShipC'); // Not in $shipResults

        $generator = new TestableReleaseNotesGenerator(
            $this->makeBuildFolder(),
            [2],
            [$ship],
            $config
        );

        $result = $generator->publicFormatComparisonTable();

        $this->assertNotEmpty($result, 'Expected graceful fallback to produce a non-empty table');
        $this->assertStringContainsString('ShipA', $result);
    }

    /**
     * Verifies that when no transport ships exist in the result set, the method
     * returns an empty string.
     */
    public function testComparisonTableEmptyWhenNoTransportShips(): void
    {
        $miner = $this->makeMinerShip('MinerShip');

        $generator = new TestableReleaseNotesGenerator(
            $this->makeBuildFolder(),
            [2, 4],
            [$miner],
            $this->makeEmptyConfig()
        );

        $result = $generator->publicFormatComparisonTable();

        $this->assertSame('', $result);
    }
}
