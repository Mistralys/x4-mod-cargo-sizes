<?php
declare(strict_types=1);

/**
 * Comprehensive tests for ConfigService.
 *
 * Testing Strategy:
 * ----------------
 * This test suite validates ConfigService's configuration file management
 * and validation logic. Each test uses isolated temporary files to ensure
 * no side effects between tests.
 *
 * Key aspects:
 * 1. **File Isolation:** Each test creates its own temporary config file
 *    via setUp() and cleans it up via tearDown(). No shared state.
 *
 * 2. **Validation Coverage:** Tests cover all validation rules for the
 *    simplified acceleration-only schema:
 *    - Required keys (cargo-multipliers, flight-mechanics)
 *    - Type constraints (arrays, objects, numbers)
 *    - Value ranges (positive multipliers, 0.1–5.0 accelerationResponsiveness)
 *
 * 3. **CRUD Operations:** Tests verify both read (getConfig) and write
 *    (updateConfig) operations work correctly with real file I/O.
 *
 * 4. **Error Handling:** Tests verify appropriate exceptions are thrown
 *    for missing files, invalid JSON, and validation failures.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ConfigService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ValidationResult;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;

/**
 * Test suite for ConfigService.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */
class ConfigServiceTest extends TestCase
{
    private string $tempConfigPath;
    private ConfigService $service;

    protected function setUp(): void
    {
        // Create a unique temporary file for this test
        $this->tempConfigPath = sys_get_temp_dir() . '/test-config-' . uniqid() . '.json';
        
        // Create service with temp config path
        $this->service = new ConfigService($this->tempConfigPath);
    }

    protected function tearDown(): void
    {
        // Clean up temporary file
        if (file_exists($this->tempConfigPath)) {
            unlink($this->tempConfigPath);
        }
    }

    /**
     * Helper to create a valid test configuration (simplified acceleration-only schema).
     *
     * @return array<string, mixed>
     */
    private function getValidConfig(): array
    {
        return [
            'cargo-multipliers' => [2, 4, 8],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ],
        ];
    }

    /**
     * Test getConfig reads and parses JSON correctly.
     *
     * Verifies that ConfigService can read a configuration file
     * and return the expected parsed array structure.
     */
    public function testGetConfigReadsAndParsesJSON(): void
    {
        // Arrange: Write a known config to temp file
        $expectedConfig = $this->getValidConfig();
        file_put_contents($this->tempConfigPath, json_encode($expectedConfig));

        // Act: Read config
        $actualConfig = $this->service->getConfig();

        // Assert: Config matches what we wrote
        $this->assertEquals($expectedConfig, $actualConfig);
        $this->assertIsArray($actualConfig['cargo-multipliers']);
        $this->assertCount(3, $actualConfig['cargo-multipliers']);
        $this->assertEquals([2, 4, 8], $actualConfig['cargo-multipliers']);
        $this->assertArrayHasKey('flight-mechanics', $actualConfig);
        $this->assertArrayHasKey('accelerationResponsiveness', $actualConfig['flight-mechanics']);
    }

    /**
     * Test getConfig throws exception when file doesn't exist.
     *
     * Verifies proper error handling for missing configuration files.
     */
    public function testGetConfigThrowsOnMissingFile(): void
    {
        // Arrange: Use service with non-existent file path
        $nonExistentPath = sys_get_temp_dir() . '/non-existent-config-' . uniqid() . '.json';
        $service = new ConfigService($nonExistentPath);

        // Assert: Exception is thrown
        $this->expectException(GUIException::class);
        $this->expectExceptionMessage('Configuration file not found');

        // Act: Try to read non-existent file
        $service->getConfig();
    }

    /**
     * Test getConfig throws exception on invalid JSON.
     *
     * Verifies that malformed JSON is properly detected and reported.
     */
    public function testGetConfigThrowsOnInvalidJSON(): void
    {
        // Arrange: Write invalid JSON to temp file
        file_put_contents($this->tempConfigPath, '{invalid json}');

        // Assert: Exception is thrown
        $this->expectException(GUIException::class);
        $this->expectExceptionMessage('Failed to parse configuration JSON');

        // Act: Try to read invalid JSON
        $this->service->getConfig();
    }

    /**
     * Test updateConfig writes valid configuration correctly.
     *
     * Verifies that ConfigService can write configuration to disk
     * and that the written content matches what was provided.
     */
    public function testUpdateConfigWritesValidConfig(): void
    {
        // Arrange: Prepare valid config
        $config = $this->getValidConfig();

        // Act: Write config
        $this->service->updateConfig($config);

        // Assert: File exists and contains expected data
        $this->assertFileExists($this->tempConfigPath);
        
        $writtenContent = file_get_contents($this->tempConfigPath);
        $this->assertNotFalse($writtenContent);
        
        $writtenConfig = json_decode($writtenContent, true);
        $this->assertEquals($config, $writtenConfig);
    }

    /**
     * Test updateConfig rejects invalid configuration.
     *
     * Verifies that validation happens before writing and prevents
     * invalid configurations from being persisted.
     */
    public function testUpdateConfigRejectsInvalidConfig(): void
    {
        // Arrange: Prepare invalid config (missing cargo-multipliers)
        $invalidConfig = [
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ],
        ];

        // Assert: Exception is thrown
        $this->expectException(GUIException::class);
        $this->expectExceptionMessage('Invalid configuration');

        // Act: Try to write invalid config
        $this->service->updateConfig($invalidConfig);
    }

    /**
     * Test validateConfig with fully valid configuration.
     *
     * Verifies that a properly structured configuration passes
     * all validation rules.
     */
    public function testValidateConfigWithValidData(): void
    {
        // Arrange: Get valid config
        $config = $this->getValidConfig();

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation passes
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid(), 'Valid config should pass validation');
        $this->assertEmpty($result->getErrors(), 'Valid config should have no errors');
    }

    /**
     * Test validateConfig fails when cargo-multipliers is missing.
     *
     * Validates that required keys are enforced.
     */
    public function testValidateConfigMissingCargoMultipliers(): void
    {
        // Arrange: Config without cargo-multipliers
        $config = [
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ],
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config without cargo-multipliers should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('cargo-multipliers', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig fails when cargo-multipliers is empty.
     *
     * Validates that array cardinality constraints are enforced.
     */
    public function testValidateConfigEmptyCargoMultipliers(): void
    {
        // Arrange: Config with empty cargo-multipliers
        $config = [
            'cargo-multipliers' => [],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ],
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with empty cargo-multipliers should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('empty', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig fails when multiplier values are negative.
     *
     * Validates that value range constraints are enforced.
     */
    public function testValidateConfigNegativeMultiplierValues(): void
    {
        // Arrange: Config with negative multiplier
        $config = [
            'cargo-multipliers' => [2, -4, 8],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ],
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with negative multipliers should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('positive', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig handles missing flight-mechanics gracefully.
     *
     * Validates that missing optional sections are reported.
     */
    public function testValidateConfigMissingFlightMechanics(): void
    {
        // Arrange: Config without flight-mechanics
        $config = [
            'cargo-multipliers' => [2, 4, 8]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config without flight-mechanics should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('flight-mechanics', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig passes with minimal valid flight-mechanics.
     *
     * Flight mechanics may contain unrecognised keys — these are simply ignored.
     * Validates that the config passes when flight-mechanics is present and valid.
     */
    public function testValidateConfigMinimalFlightMechanics(): void
    {
        // Arrange: Config with flight-mechanics containing only accelerationResponsiveness
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 1.0,
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation passes — simplification removed tier checks
        $this->assertTrue($result->isValid(), 'Config with valid flight-mechanics should pass');
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Test validateConfig with accelerationResponsiveness bounds.
     *
     * Validates that accelerationResponsiveness range is enforced (0.1 to 5.0).
     */
    public function testValidateConfigAccelerationResponsivenessOutOfRange(): void
    {
        // Arrange: Config with value above maximum
        $configAbove = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 5.1  // Max is 5.0
            ]
        ];

        $resultAbove = $this->service->validateConfig($configAbove);
        $this->assertFalse($resultAbove->isValid());
        $this->assertStringContainsString('accelerationResponsiveness', implode(' ', $resultAbove->getErrors()));

        // Arrange: Config with value below minimum
        $configBelow = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 0.05  // Min is 0.1
            ]
        ];

        $resultBelow = $this->service->validateConfig($configBelow);
        $this->assertFalse($resultBelow->isValid());
        $this->assertStringContainsString('accelerationResponsiveness', implode(' ', $resultBelow->getErrors()));
    }

    /**
     * Test that temporary files are properly cleaned up.
     *
     * Verifies that tearDown() successfully removes test files.
     */
    public function testTempFileCleanup(): void
    {
        // Arrange: Write something to temp file
        $config = $this->getValidConfig();
        file_put_contents($this->tempConfigPath, json_encode($config));

        // Assert: File exists before tearDown
        $this->assertFileExists($this->tempConfigPath);

        // Act: Manually call tearDown to test cleanup
        $this->tearDown();

        // Assert: File is removed
        $this->assertFileDoesNotExist($this->tempConfigPath);
    }
}
