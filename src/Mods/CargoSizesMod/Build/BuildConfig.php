<?php
/**
 * @package Build Tools
 * @subpackage Configuration
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Build;

use AppUtils\ArrayDataCollection;
use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;

/**
 * Build configuration manager for cargo size mod.
 *
 * Reads two settings from `config/build-config.json`:
 * - `cargo-multipliers` — list of cargo size multipliers to build
 * - `flight-mechanics.accelerationResponsiveness` — single physics tuning knob
 *
 * @package Build Tools
 * @subpackage Configuration
 */
class BuildConfig
{
    public const string KEY_ACCELERATION_RESPONSIVENESS = 'accelerationResponsiveness';
    public const string KEY_MULTIPLIERS = 'cargo-multipliers';
    public const string KEY_FLIGHT_MECHANICS = 'flight-mechanics';

    /**
     * @var float[]
     */
    public private(set) array $multipliers = array();

    private float $accelerationResponsiveness = 1.0;

    /**
     * @throws CargoSizeException
     */
    public function __construct()
    {
        $config = ArrayDataCollection::create(JSONFile::factory(__DIR__.'/../../../../config/build-config.json')->parse());

        // Load cargo multipliers
        foreach($config->getArray(self::KEY_MULTIPLIERS) as $value) {
            if(is_numeric($value)) {
                $this->multipliers[] = (float)$value;
            }
        }

        // Load acceleration responsiveness
        $flightMechanics = $config->getArray(self::KEY_FLIGHT_MECHANICS);
        if (isset($flightMechanics[self::KEY_ACCELERATION_RESPONSIVENESS]) && is_numeric($flightMechanics[self::KEY_ACCELERATION_RESPONSIVENESS])) {
            $this->accelerationResponsiveness = (float)$flightMechanics[self::KEY_ACCELERATION_RESPONSIVENESS];
        }

        $this->validate();
    }

    /**
     * Validates configuration values.
     *
     * @throws CargoSizeException
     */
    private function validate(): void
    {
        if (empty($this->multipliers)) {
            throw new CargoSizeException(
                'No cargo multipliers configured. At least one multiplier is required.',
                '',
                CargoSizeException::ERROR_INVALID_CONFIG
            );
        }

        $accelResponsiveness = $this->getAccelerationResponsiveness();
        if ($accelResponsiveness < 0.1 || $accelResponsiveness > 5.0) {
            throw new CargoSizeException(
                sprintf('Acceleration responsiveness must be between 0.1 and 5.0. Received: %f', $accelResponsiveness),
                '',
                CargoSizeException::ERROR_INVALID_CONFIG
            );
        }
    }

    /**
     * @return float[]
     */
    public function getMultipliers() : array
    {
        return $this->multipliers;
    }

    /**
     * Gets the acceleration responsiveness factor (0.1-5.0).
     *
     * Adjusts how acceleration factors scale with mass.
     * - < 1.0 = less responsive (more gradual scaling)
     * - 1.0 = physics-correct (recommended)
     * - > 1.0 = more responsive (aggressive scaling)
     *
     * @return float Responsiveness factor (default: 1.0)
     */
    public function getAccelerationResponsiveness(): float
    {
        return $this->accelerationResponsiveness;
    }
}

