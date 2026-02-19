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
 * 2. **Validation Coverage:** Tests cover all validation rules:
 *    - Required keys (cargo-multipliers, flight-mechanics)
 *    - Type constraints (arrays, objects, booleans, numbers)
 *    - Value ranges (positive multipliers, 0.0-2.0 inertia, etc.)
 *    - Complex validation (ascending tiers, non-empty arrays)
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
     * Helper to create a valid test configuration.
     *
     * @return array<string, mixed>
     */
    private function getValidConfig(): array
    {
        return [
            'cargo-multipliers' => [2, 4, 8],
            'flight-mechanics' => [
                'inertiaImpactFactor' => 1.0,
                'accelerationResponsiveness' => 0.5,
                'useEffectiveRatioCap' => true,
                'dragReductionTiers' => [
                    ['maxMultiplier' => 2.0, 'reductionPercent' => 0.10],
                    ['maxMultiplier' => 4.0, 'reductionPercent' => 0.30],
                    ['maxMultiplier' => 8.0, 'reductionPercent' => 0.50]
                ],
                'jerkReductionTiers' => [
                    ['maxMultiplier' => 2.0, 'reductionPercent' => 0.05],
                    ['maxMultiplier' => 4.0, 'reductionPercent' => 0.15]
                ]
            ]
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
                'inertiaImpactFactor' => 1.0
            ]
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
                'inertiaImpactFactor' => 1.0
            ]
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
                'inertiaImpactFactor' => 1.0
            ]
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
                'inertiaImpactFactor' => 1.0
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with negative multipliers should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('positive', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig fails when flight mechanics values are out of range.
     *
     * Validates that numeric range constraints are enforced.
     */
    public function testValidateConfigInvalidFlightMechanics(): void
    {
        // Arrange: Config with out-of-range inertiaImpactFactor
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'inertiaImpactFactor' => 5.0  // Max is 2.0
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with out-of-range inertiaImpactFactor should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('inertiaImpactFactor', implode(' ', $result->getErrors()));
        $this->assertStringContainsString('between', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig fails when tiers are not in ascending order.
     *
     * Validates that tier ordering constraints are enforced.
     */
    public function testValidateConfigNonAscendingTiers(): void
    {
        // Arrange: Config with non-ascending tier maxMultipliers
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'inertiaImpactFactor' => 1.0,
                'dragReductionTiers' => [
                    ['maxMultiplier' => 4.0, 'reductionPercent' => 0.10],  // Out of order
                    ['maxMultiplier' => 2.0, 'reductionPercent' => 0.30]
                ]
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with non-ascending tiers should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('ascending', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig fails when tier values are out of range.
     *
     * Validates that tier value constraints are enforced.
     */
    public function testValidateConfigOutOfRangeTierValues(): void
    {
        // Arrange: Config with out-of-range reductionPercent (must be < 1.0)
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'inertiaImpactFactor' => 1.0,
                'dragReductionTiers' => [
                    ['maxMultiplier' => 2.0, 'reductionPercent' => 1.5]  // Must be < 1.0
                ]
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with out-of-range tier values should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('reductionPercent', implode(' ', $result->getErrors()));
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
     * Test validateConfig handles empty tier arrays.
     *
     * Validates that tier arrays cannot be empty.
     */
    public function testValidateConfigEmptyTierArrays(): void
    {
        // Arrange: Config with empty dragReductionTiers
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'inertiaImpactFactor' => 1.0,
                'dragReductionTiers' => []  // Empty
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid(), 'Config with empty tier arrays should fail');
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('empty', implode(' ', $result->getErrors()));
    }

    /**
     * Test validateConfig with accelerationResponsiveness bounds.
     *
     * Validates that accelerationResponsiveness range is enforced (0.0 to 1.0).
     */
    public function testValidateConfigAccelerationResponsivenessOutOfRange(): void
    {
        // Arrange: Config with out-of-range accelerationResponsiveness
        $config = [
            'cargo-multipliers' => [2, 4],
            'flight-mechanics' => [
                'accelerationResponsiveness' => 2.0  // Max is 1.0
            ]
        ];

        // Act: Validate
        $result = $this->service->validateConfig($config);

        // Assert: Validation fails
        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('accelerationResponsiveness', implode(' ', $result->getErrors()));
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
