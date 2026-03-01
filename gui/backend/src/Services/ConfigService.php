<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ValidationResult;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;

/**
 * Configuration management service.
 *
 * Handles reading and writing config/build-config.json using synchronous file I/O.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ConfigService
{
    private const string DEFAULT_CONFIG_PATH = __DIR__ . '/../../../../config/build-config.json';

    private string $configPath;

    public function __construct(string $configPath = self::DEFAULT_CONFIG_PATH)
    {
        $this->configPath = $configPath;
    }

    /**
     * Gets the current configuration.
     *
     * @return array<string, mixed>
     * @throws GUIException
     */
    public function getConfig(): array
    {
        try {
            if (!file_exists($this->configPath)) {
                throw new GUIException(
                    'Configuration file not found: ' . $this->configPath,
                    '',
                    GUIException::ERROR_CONFIG_INVALID
                );
            }

            $content = file_get_contents($this->configPath);
            if ($content === false) {
                throw new GUIException(
                    'Failed to read configuration file',
                    '',
                    GUIException::ERROR_CONFIG_INVALID
                );
            }

            $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            return $config;
        } catch (\JsonException $e) {
            throw new GUIException(
                'Failed to parse configuration JSON: ' . $e->getMessage(),
                '',
                GUIException::ERROR_CONFIG_INVALID,
                $e
            );
        }
    }

    /**
     * Updates the configuration file.
     *
     * @param array<string, mixed> $config Configuration array
     * @return void
     * @throws GUIException
     */
    public function updateConfig(array $config): void
    {
        // Validate before writing
        $validation = $this->validateConfig($config);
        if (!$validation->isValid()) {
            throw new GUIException(
                'Invalid configuration: ' . implode(', ', $validation->getErrors()),
                '',
                GUIException::ERROR_CONFIG_INVALID
            );
        }

        try {
            $json = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            $result = file_put_contents($this->configPath, $json);
            
            if ($result === false) {
                throw new GUIException(
                    'Failed to write configuration file',
                    '',
                    GUIException::ERROR_FILE_WRITE
                );
            }
        } catch (\JsonException $e) {
            throw new GUIException(
                'Failed to encode configuration to JSON: ' . $e->getMessage(),
                '',
                GUIException::ERROR_CONFIG_INVALID,
                $e
            );
        }
    }

    /**
     * Validates configuration without saving.
     *
     * @param array<string, mixed> $config Configuration array
     * @return ValidationResult
     */
    public function validateConfig(array $config): ValidationResult
    {
        $errors = [];

        // Validate cargo-multipliers
        if (!isset($config['cargo-multipliers'])) {
            $errors[] = 'Missing cargo-multipliers array';
        } elseif (!is_array($config['cargo-multipliers'])) {
            $errors[] = 'cargo-multipliers must be an array';
        } elseif (count($config['cargo-multipliers']) === 0) {
            $errors[] = 'cargo-multipliers array cannot be empty';
        } else {
            foreach ($config['cargo-multipliers'] as $multiplier) {
                if (!is_numeric($multiplier) || $multiplier <= 0) {
                    $errors[] = 'All cargo multipliers must be positive numbers';
                    break;
                }
            }
        }

        // Validate flight-mechanics
        if (!isset($config['flight-mechanics'])) {
            $errors[] = 'Missing flight-mechanics object';
        } elseif (!is_array($config['flight-mechanics'])) {
            $errors[] = 'flight-mechanics must be an object';
        } else {
            $flightMechanics = $config['flight-mechanics'];

            // Validate accelerationResponsiveness (0.1 = very sluggish, 5.0 = very snappy)
            if (isset($flightMechanics['accelerationResponsiveness'])) {
                $factor = $flightMechanics['accelerationResponsiveness'];
                if (!is_numeric($factor) || $factor < 0.1 || $factor > 5.0) {
                    $errors[] = 'accelerationResponsiveness must be between 0.1 and 5.0';
                }
            }
        }

        return new ValidationResult(
            isValid: count($errors) === 0,
            errors: $errors
        );
    }
}
