<?php
/**
 * Tests for the agent-facing behaviour of the `build:baseline` and
 * `build:parity` commands: option parsing, verdict line, exit codes and
 * the baseline capture. Works on temp folders only.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\BaselineCapture;
use Mistralys\X4\Mods\CargoSizesMod\BaselineCommand;
use Mistralys\X4\Mods\CargoSizesMod\BaselineCommandResult;
use Mistralys\X4\Mods\CargoSizesMod\BaselineOptions;
use Mistralys\X4\Mods\CargoSizesMod\BuildParityComparator;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class BuildBaselineCommandTest extends TestCase
{
    private string $root;
    private string $buildDir;
    private string $baselineDir;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/x4-baseline-'.uniqid('', true);
        $this->buildDir = $this->root.'/build';
        $this->baselineDir = $this->root.'/baseline';

        mkdir($this->buildDir.'/v3-2-1-for-v9', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);
    }

    public function testOptionParserDefaultsAndUnknownOption(): void
    {
        $defaults = BaselineOptions::parse(array(), true);

        $this->assertSame(BuildParityComparator::DEFAULT_BASELINE_DIR, $defaults->getBaselineDir());
        $this->assertFalse($defaults->isForce());

        $custom = BaselineOptions::parse(array('--baseline=work/other', '--force'), true);

        $this->assertSame('work/other', $custom->getBaselineDir());
        $this->assertTrue($custom->isForce());

        try {
            BaselineOptions::parse(array('--bogus'), true);
            $this->fail('An unknown option must be rejected.');
        } catch (CargoSizeException $e) {
            $this->assertSame(CargoSizeException::ERROR_PARITY_SETUP, $e->getCode());
            $this->assertStringContainsString('--bogus', $e->getMessage());
            $this->assertStringContainsString('--baseline=<dir>', $e->getMessage());
            $this->assertStringContainsString('--force', $e->getMessage());
        }
    }

    public function testCompareCommandRejectsForceAndEmptyBaselineValue(): void
    {
        $this->assertSame(
            BaselineCommandResult::EXIT_ERROR,
            BaselineCommand::compare(array('--force'), $this->buildDir)->getExitCode()
        );

        $this->assertSame(
            BaselineCommandResult::EXIT_ERROR,
            BaselineCommand::compare(array('--baseline='), $this->buildDir)->getExitCode()
        );
    }

    public function testVerdictLineIsFirstAndExitCodesMap(): void
    {
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));
        $this->createZip($this->baselineDir.'/zips/mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));

        $pass = BaselineCommand::compare(array('--baseline='.$this->baselineDir), $this->buildDir);

        $this->assertSame(BaselineCommandResult::EXIT_OK, $pass->getExitCode());
        $this->assertSame('PARITY PASS', $pass->getLines()[0]);

        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<changed/>'));

        $fail = BaselineCommand::compare(array('--baseline='.$this->baselineDir), $this->buildDir);

        $this->assertSame(BaselineCommandResult::EXIT_DIFFERENCES, $fail->getExitCode());
        $this->assertSame('PARITY FAIL: 1 differing, 0 missing, 0 unexpected', $fail->getLines()[0]);
        $this->assertSame('DIFFERS    mod: mod/a.xml', $fail->getLines()[1]);

        $error = BaselineCommand::compare(array('--bogus'), $this->buildDir);

        $this->assertSame(BaselineCommandResult::EXIT_ERROR, $error->getExitCode());
        $this->assertStringStartsWith('ERROR:', $error->getLines()[0]);
    }

    public function testMissingBaselineReturnsSetupErrorWithHint(): void
    {
        $result = BaselineCommand::compare(array('--baseline='.$this->baselineDir), $this->buildDir);

        $this->assertSame(BaselineCommandResult::EXIT_ERROR, $result->getExitCode());
        $this->assertStringContainsString('composer build:baseline', $result->getVerdict());
    }

    public function testMissingBuildOutputReturnsSetupError(): void
    {
        $this->createZip($this->baselineDir.'/zips/mod_v3.2.0.zip', array('mod/a.xml' => '<a/>'));

        $result = BaselineCommand::compare(array('--baseline='.$this->baselineDir), $this->root.'/nothing');

        $this->assertSame(BaselineCommandResult::EXIT_ERROR, $result->getExitCode());
        $this->assertStringContainsString('composer build', $result->getVerdict());
    }

    public function testCaptureWritesBaselineLayout(): void
    {
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));
        file_put_contents($this->buildDir.'/physics-diagnostics.txt', "Build Date: 2026-11-22 10:00:00\r\nShip: A\r\n");

        $built = false;
        $result = BaselineCommand::capture(
            array('--baseline='.$this->baselineDir),
            function () use (&$built): bool {
                $built = true;
                return true;
            },
            $this->buildDir
        );

        $this->assertTrue($built, 'The capture must run the build itself.');
        $this->assertSame(BaselineCommandResult::EXIT_OK, $result->getExitCode());
        $this->assertSame($this->baselineDir, $result->getLines()[count($result->getLines()) - 1]);
        $this->assertFileExists($this->baselineDir.'/zips/mod_v3.2.1.zip');
        $this->assertFileExists($this->baselineDir.'/physics-diagnostics.txt');

        $compare = BaselineCommand::compare(array('--baseline='.$this->baselineDir), $this->buildDir);

        $this->assertSame(BaselineCommandResult::EXIT_OK, $compare->getExitCode(), $compare->render());
    }

    public function testCaptureRefusesNonEmptyBaselineWithoutForce(): void
    {
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<a/>'));
        mkdir($this->baselineDir, 0777, true);
        file_put_contents($this->baselineDir.'/keep.txt', 'precious');

        $built = false;
        $result = BaselineCommand::capture(
            array('--baseline='.$this->baselineDir),
            function () use (&$built): bool {
                $built = true;
                return true;
            },
            $this->buildDir
        );

        $this->assertSame(BaselineCommandResult::EXIT_ERROR, $result->getExitCode());
        $this->assertStringContainsString('--force', $result->getVerdict());
        $this->assertStringContainsString('--baseline=<dir>', $result->getVerdict());
        $this->assertFalse($built, 'A refusal must not cost a build.');
        $this->assertFileDoesNotExist($this->baselineDir.'/zips/mod_v3.2.1.zip');
    }

    public function testCaptureOverwritesWithForce(): void
    {
        $this->addBuildZip('mod_v3.2.1.zip', array('mod/a.xml' => '<new/>'));
        $this->createZip($this->baselineDir.'/zips/old_v3.2.0.zip', array('old/a.xml' => '<old/>'));
        file_put_contents($this->baselineDir.'/keep.txt', 'unrelated');

        $result = BaselineCommand::capture(
            array('--baseline='.$this->baselineDir, '--force'),
            static fn(): bool => true,
            $this->buildDir
        );

        $this->assertSame(BaselineCommandResult::EXIT_OK, $result->getExitCode());
        $this->assertFileExists($this->baselineDir.'/zips/mod_v3.2.1.zip');
        $this->assertFileDoesNotExist($this->baselineDir.'/zips/old_v3.2.0.zip');
        $this->assertFileExists($this->baselineDir.'/keep.txt', 'Only captured files are replaced.');
    }

    public function testCaptureReportsBuildFailure(): void
    {
        $result = BaselineCommand::capture(
            array('--baseline='.$this->baselineDir),
            static fn(): bool => false,
            $this->buildDir
        );

        $this->assertSame(BaselineCommandResult::EXIT_ERROR, $result->getExitCode());
        $this->assertStringContainsString('build failed', $result->getVerdict());
        $this->assertDirectoryDoesNotExist($this->baselineDir);
    }

    public function testCaptureWithoutBuildOutputThrows(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('composer build');

        (new BaselineCapture($this->root.'/nothing', $this->baselineDir))->capture(false);
    }

    // region: Helpers

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

        if(!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
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
