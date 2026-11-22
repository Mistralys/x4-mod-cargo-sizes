<?php
/**
 * Pins the console message printed when an alias macro is skipped.
 * It once contained a non-ASCII dash that was garbled on Windows consoles.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\ShipXMLFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AliasSkipMessageTest extends TestCase
{
    public function testAliasSkipMessageIsAscii(): void
    {
        $macroName = 'ship_pir_l_scavenger_01_a_macro';

        $shipFile = $this->createStub(ShipXMLFile::class);
        $shipFile->method('getMacroName')->willReturn($macroName);
        $shipFile->method('getAliasName')->willReturn('ship_arg_l_trans_01_a_macro');

        $reflection = new ReflectionClass(CargoSizeExtractor::class);
        $extractor = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('analyzeShipMacro');

        ob_start();

        try {
            $method->invoke($extractor, $shipFile);
        } finally {
            $output = (string)ob_get_clean();
        }

        $this->assertSame('  - SKIP | Alias macro - skipping ['.$macroName.']'.PHP_EOL, $output);
        $this->assertMatchesRegularExpression('/^[\x00-\x7F]*$/', $output, 'The alias skip message must be ASCII-only.');
    }
}
