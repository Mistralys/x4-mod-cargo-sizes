<?php
/**
 * Tests for the build parity comparator. All zips are synthetic and live
 * in a temp folder: the real `build/` and `work/` folders are never touched.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\BuildParityComparator;
use Mistralys\X4\Mods\CargoSizesMod\BuildParityResult;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class BuildParityComparatorTest extends TestCase
{
    private string $root;
    private string $baselineDir;
    private string $buildDir;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/x4-parity-'.uniqid('', true);
        $this->baselineDir = $this->root.'/baseline';
        $this->buildDir = $this->root.'/build';

        mkdir($this->baselineDir.'/zips', 0777, true);
        mkdir($this->buildDir.'/v3-2-1-for-v9', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);
    }

    public function testIdenticalSetsPass(): void
    {
        $entries = array('mod/content.xml' => '<content/>', 'mod/a.xml' => '<a/>');

        $this->addBaselineZip('mod_v3.2.0.zip', $entries);
        $this->addBuildZip('mod_v3.2.1.zip', $entries);

        $result = $this->compare();

        $this->assertTrue($result->isParity());
        $this->assertSame('PARITY PASS', $result->getVerdict());
        $this->assertSame(array(), $result->getDifferenceLines());
        $this->assertContains('mod: mod/a.xml', $result->getMatching());
    }

    public function testChangedEntryContentIsReported(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>', 'mod/b.xml' => '<b/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a changed="1"/>', 'mod/b.xml' => '<b/>'));

        $result = $this->compare();

        $this->assertFalse($result->isParity());
        $this->assertSame(array('mod: mod/a.xml'), $result->getDiffering());
        $this->assertSame('PARITY FAIL: 1 differing, 0 missing, 0 unexpected', $result->getVerdict());
        $this->assertSame(array('DIFFERS    mod: mod/a.xml'), $result->getDifferenceLines());
    }

    public function testMissingAndUnexpectedEntriesAreReported(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>', 'mod/gone.xml' => '<g/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>', 'mod/new.xml' => '<n/>'));
        $this->addBuildZip('extra_v3.2.1.zip', array('extra/a.xml' => '<a/>'));

        $result = $this->compare();

        $this->assertSame(array('mod: mod/gone.xml'), $result->getMissing());
        $this->assertSame(array('extra: extra_v3.2.1.zip', 'mod: mod/new.xml'), $this->sorted($result->getUnexpected()));
        $this->assertStringContainsString('2 unexpected', $result->getVerdict());
    }

    public function testMissingZipIsReported(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));
        $this->addBaselineZip('other_v3.2.0.zip', array('other/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));

        $this->assertSame(array('other: other_v3.2.0.zip'), $this->compare()->getMissing());
    }

    public function testVersionOnlyDifferencePasses(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array(
            'mod/_readme.txt' => "Mod version: 3.2.0\r\n",
            'mod/content.xml' => '<content id="x" version="320" date="2026-11-22">',
            'mod/3-2-0/a.xml' => 'path 3-2-0',
        ));
        $this->addBuildZip('mod_v3.2.1.zip', array(
            'mod/_readme.txt' => "Mod version: 3.2.1\r\n",
            'mod/content.xml' => '<content id="x" version="321" date="2026-11-22">',
            'mod/3-2-1/a.xml' => 'path 3-2-1',
        ));

        $result = $this->compare();

        $this->assertTrue($result->isParity(), implode("\n", $result->getDifferenceLines()));
    }

    public function testVersionIsNotNormalizedInBinaryEntries(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/image.jpg' => 'bytes 3.2.0'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/image.jpg' => 'bytes 3.2.1'));

        $this->assertSame(array('mod: mod/image.jpg'), $this->compare()->getDiffering());
    }

    public function testBuildDateOnlyDifferencePasses(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array(
            'mod/_readme.txt' => "Generated on: 2026-11-22 16:06:19\r\nMore\r\n",
            'mod/content.xml' => '<content date="2026-11-22" save="0">',
        ));
        $this->addBuildZip('mod_v3.2.1.zip', array(
            'mod/_readme.txt' => "Generated on: 2027-01-01 09:00:00\r\nMore\r\n",
            'mod/content.xml' => '<content date="2027-01-01" save="0">',
        ));

        $this->assertTrue($this->compare()->isParity());
    }

    public function testDiagnosticsReportIsComparedWithNormalizedDate(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));

        file_put_contents($this->baselineDir.'/physics-diagnostics.txt', "Build Date: 2026-11-22 16:19:38\r\nShip: A\r\n");
        file_put_contents($this->buildDir.'/physics-diagnostics.txt', "Build Date: 2027-01-01 10:00:00\r\nShip: A\r\n");

        $this->assertTrue($this->compare()->isParity());

        file_put_contents($this->buildDir.'/physics-diagnostics.txt', "Build Date: 2027-01-01 10:00:00\r\nShip: B\r\n");

        $this->assertSame(array('physics-diagnostics.txt'), $this->compare()->getDiffering());
    }

    public function testAllowListedEntryMayDifferInContent(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/random.xml' => 'Example: A', 'mod/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/random.xml' => 'Example: B', 'mod/a.xml' => '<a/>'));

        $result = $this->compare(array('#random\.xml$#' => 'Random example ship.'));

        $this->assertTrue($result->isParity());
        $this->assertSame(array('mod: mod/random.xml'), $result->getAllowedDifferences());
    }

    public function testAllowListedEntryMissingIsStillReported(): void
    {
        $allowList = array('#random\.xml$#' => 'Random example ship.');

        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/random.xml' => 'Example: A', 'mod/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));

        $this->assertSame(array('mod: mod/random.xml'), $this->compare($allowList)->getMissing());

        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/random.xml' => 'Example: B', 'mod/a.xml' => '<a/>'));

        $this->assertSame(array('mod: mod/random.xml'), $this->compare($allowList)->getUnexpected());
    }

    public function testDefaultAllowListHasJustificationPerPattern(): void
    {
        $this->assertNotEmpty(BuildParityComparator::ALLOWED_CONTENT_DIFFERENCES);

        foreach(BuildParityComparator::ALLOWED_CONTENT_DIFFERENCES as $pattern => $justification) {
            $this->assertNotSame('', trim($justification), 'Pattern '.$pattern.' needs a justification.');
        }
    }

    public function testLineEndingDifferenceIsAlwaysReported(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => "<a>\r\n</a>\r\n"));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => "<a>\n</a>\n"));

        $this->assertSame(array('mod: mod/a.xml'), $this->compare()->getDiffering());
    }

    public function testMissingBuildOutputThrows(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));

        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('composer build');

        (new BuildParityComparator($this->baselineDir, $this->root.'/nothing'))->compare();
    }

    public function testToArrayExposesAllLists(): void
    {
        $this->addBaselineZip('mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));

        $this->assertSame(
            array('matching' => array('mod: mod/a.xml'), 'differing' => array(), 'missing' => array(), 'unexpected' => array(), 'allowed' => array()),
            $this->compare()->toArray()
        );
    }

    // region: Helpers

    /**
     * @param array<string,string>|null $allowList
     */
    private function compare(?array $allowList = array()): BuildParityResult
    {
        return (new BuildParityComparator($this->baselineDir, $this->buildDir, $allowList))->compare();
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    private function sorted(array $values): array
    {
        sort($values);
        return $values;
    }

    /**
     * @param string $name
     * @param array<string,string> $entries
     */
    private function addBaselineZip(string $name, array $entries): void
    {
        $this->createZip($this->baselineDir.'/zips/'.$name, $entries);
    }

    /**
     * @param string $name
     * @param array<string,string> $entries
     */
    private function addBuildZip(string $name, array $entries): void
    {
        $this->createZip($this->buildDir.'/v3-2-1-for-v9/'.$name, $entries);
    }

    /**
     * @param string $path
     * @param array<string,string> $entries
     */
    private function createZip(string $path, array $entries): void
    {
        if(is_file($path)) {
            unlink($path);
        }

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE) === true);

        foreach($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
    }

    private function deleteTree(string $path): void
    {
        if(!is_dir($path)) {
            return;
        }

        foreach(scandir($path) ?: array() as $item) {
            if($item === '.' || $item === '..') {
                continue;
            }

            $full = $path.'/'.$item;

            if(is_dir($full)) {
                $this->deleteTree($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }

    // endregion
}
