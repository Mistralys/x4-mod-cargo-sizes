<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use AppUtils\ConvertHelper;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipDetails;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Database\Ships\ShipDefs;
use Mistralys\X4\Database\Engines\EngineDefs;

/**
 * Ship and engine data service using X4 Core.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ShipDataService
{
    /**
     * Ship type constants matching CargoSizeExtractor.
     */
    private const string SHIP_TYPE_TRANSPORT = 'trans';
    private const string SHIP_TYPE_STORAGE = 'storage';
    private const string SHIP_TYPE_MINING = 'miner';
    private const string SHIP_TYPE_AUXILIARY = 'resupplier';
    private const string SHIP_TYPE_CARRIER = 'carrier';

    /**
     * Ship sizes supported by the mod (matching CargoSizeExtractor::SHIP_SIZES).
     */
    private const array SHIP_SIZES = ['xs', 's', 'm', 'l', 'xl'];

    /**
     * Maps internal ship types to extractor types.
     */
    private const array SHIP_TYPE_MAP = [
        'transport' => self::SHIP_TYPE_TRANSPORT,
        'mining' => self::SHIP_TYPE_MINING,
        'auxiliary' => self::SHIP_TYPE_AUXILIARY,
        'carrier' => self::SHIP_TYPE_CARRIER
    ];

    /**
     * Cache for loaded ships (loaded once per request).
     * @var array<string,array>|null
     */
    private static ?array $shipCache = null;

    /**
     * Cache for loaded engines (loaded once per request).
     * @var array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>|null
     */
    private static ?array $engineCache = null;

    /**
     * Gets all supported ship types.
     *
     * @return array{type: string, label: string}[]
     */
    public function getShipTypes(): array
    {
        return [
            ['type' => 'transport', 'label' => 'Transport ships'],
            ['type' => 'mining', 'label' => 'Mining ships'],
            ['type' => 'auxiliary', 'label' => 'Auxiliaries'],
            ['type' => 'carrier', 'label' => 'Carriers']
        ];
    }

    /**
     * Gets ships filtered by type.
     *
     * Loads all ships from X4 Core's ShipDefs and filters by the requested type.
     * Ships are classified by their macro name patterns (e.g., "ship_arg_m_trans_...")
     *
     * @param string $type Ship type (transport, mining, auxiliary, carrier)
     * @return array<array{id: string, name: string, size: string, mass: float, cargo: float}>
     * @throws GUIException
     */
    public function getShipsByType(string $type): array
    {
        if (!isset(self::SHIP_TYPE_MAP[$type])) {
            throw new GUIException(
                sprintf('Unknown ship type: %s', $type),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        // Load all ships if not cached
        if (self::$shipCache === null) {
            $this->loadAllShips();
        }

        // Filter by requested type
        return array_values(array_filter(
            self::$shipCache,
            fn($ship) => $ship['type'] === $type
        ));
    }

    /**
     * Gets detailed information about a specific ship.
     *
     * @param string $shipId Ship identifier
     * @return ShipDetails
     * @throws GUIException
     */
    public function getShipDetails(string $shipId): ShipDetails
    {
        try {
            $shipDef = ShipDefs::getInstance()->getByID($shipId);
            
            // Determine ship type from macro name
            $type = $this->determineShipType($shipId);
            if ($type === null) {
                $type = 'transport'; // Fallback default
            }
            
            $size = $this->extractShipSize($shipId);
            $mass = $shipDef->getMass();
            
            // Get cargo capacity with fallback
            $cargo = $this->getShipCargoCapacity($shipDef, $size);
            
            // Get compatible engines
            $engines = $this->getEnginesForShip($shipId);

            return new ShipDetails(
                id: $shipId,
                name: $shipDef->getLabel(),
                type: $type,
                size: $size,
                mass: $mass,
                cargo: $cargo,
                engines: array_column($engines, 'id')
            );
        } catch (\Exception $e) {
            throw new GUIException(
                sprintf('Failed to get ship details for %s: %s', $shipId, $e->getMessage()),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE,
                $e
            );
        }
    }

    /**
     * Gets compatible engines for a ship.
     *
     * Filters engines by size matching. In X4, engines are size-specific (S, M, L, XL).
     *
     * @param string $shipId Ship identifier
     * @return array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>
     * @throws GUIException
     */
    public function getEnginesForShip(string $shipId): array
    {
        try {
            // Load all engines if not cached
            if (self::$engineCache === null) {
                $this->loadAllEngines();
            }
            
            $size = $this->extractShipSize($shipId);
            
            // Filter engines by size
            return array_values(array_filter(
                self::$engineCache,
                function($engine) use ($size) {
                    $engineSize = $this->extractEngineSize($engine['id']);
                    return $engineSize === $size;
                }
            ));
        } catch (\Exception $e) {
            throw new GUIException(
                sprintf('Failed to get engines for ship %s: %s', $shipId, $e->getMessage()),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE,
                $e
            );
        }
    }

    /**
     * Gets all available engines.
     *
     * @return array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>
     * @throws GUIException
     */
    public function getAllEngines(): array
    {
        try {
            // Load all engines if not cached
            if (self::$engineCache === null) {
                $this->loadAllEngines();
            }

            return self::$engineCache;
        } catch (\Exception $e) {
            throw new GUIException(
                'Failed to get engines: ' . $e->getMessage(),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE,
                $e
            );
        }
    }

    /**
     * Extracts ship size from ship ID.
     *
     * @param string $shipId
     * @return string
     */
    private function extractShipSize(string $shipId): string
    {
        foreach (self::SHIP_SIZES as $size) {
            if (str_contains($shipId, '_' . $size . '_')) {
                return $size;
            }
        }
        return 'm'; // Default to medium
    }

    /**
     * Loads all ships from X4 Core ShipDefs and populates the cache.
     * Ships are classified by their macro name patterns.
     *
     * @return void
     */
    private function loadAllShips(): void
    {
        self::$shipCache = [];
        $shipDefs = ShipDefs::getInstance();

        foreach ($shipDefs->getAll() as $shipDef) {
            $shipId = $shipDef->getID();
            
            // Determine ship type from macro name
            $shipType = $this->determineShipType($shipId);
            if ($shipType === null) {
                continue; // Skip ships that don't match our types
            }
            
            // Extract ship size
            $size = $this->extractShipSize($shipId);
            
            // Filter by supported sizes (xs, s, m, l, xl)
            if (!in_array($size, self::SHIP_SIZES)) {
                continue;
            }
            
            // Get cargo capacity with fallback
            $cargo = $this->getShipCargoCapacity($shipDef, $size);
            
            self::$shipCache[] = [
                'id' => $shipId,
                'name' => $shipDef->getLabel(),
                'type' => $shipType,
                'size' => $size,
                'mass' => $shipDef->getMass(),
                'cargo' => $cargo
            ];
        }
    }

    /**
     * Determines ship type from ship macro name.
     * Uses the same logic as CargoSizeExtractor::resolveShipType().
     *
     * @param string $shipId Ship macro name
     * @return string|null Ship type or null if not classifiable
     */
    private function determineShipType(string $shipId): ?string
    {
        $parts = ConvertHelper::explodeTrim('_', $shipId);
        
        // Check if any part matches a known ship type
        foreach (array_keys(self::SHIP_TYPE_MAP) as $type) {
            $extractorType = self::SHIP_TYPE_MAP[$type];
            if (in_array($extractorType, $parts)) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Gets ship cargo capacity.
     * 
     * NOTE: X4 Core's ShipDef doesn't directly expose cargo capacity 
     * (it's in storage modules). We use size-based estimates as fallback.
     *
     * @param \Mistralys\X4\Database\Ships\ShipDef $shipDef
     * @param string $size Ship size
     * @return float Cargo capacity in cubic meters
     */
    private function getShipCargoCapacity($shipDef, string $size): float
    {
        // TODO: If X4 Core adds cargo capacity API, use it here
        // For now, use reasonable estimates based on ship size and type
        return match($size) {
            'xs' => 2000.0,
            's' => 5000.0,
            'm' => 12000.0,
            'l' => 30000.0,
            'xl' => 50000.0,
            default => 10000.0
        };
    }

    /**
     * Loads all engines from X4 Core EngineDefs and populates the cache.
     * Includes full thrust data (forward, reverse, boost, travel).
     *
     * @return void
     */
    private function loadAllEngines(): void
    {
        self::$engineCache = [];
        $engineDefs = EngineDefs::getInstance();

        foreach ($engineDefs->getAll() as $engineDef) {
            $thrustForward = $engineDef->getThrustForward();
            
            self::$engineCache[] = [
                'id' => $engineDef->getID(),
                'name' => $engineDef->getLabel(),
                'thrustForward' => $thrustForward,
                'thrustReverse' => $this->estimateThrustReverse($thrustForward),
                'thrustBoost' => $this->estimateThrustBoost($thrustForward),
                'thrustTravel' => $this->estimateThrustTravel($thrustForward)
            ];
        }
    }

    /**
     * Extracts engine size from engine ID.
     *
     * @param string $engineId Engine identifier
     * @return string Size code (xs, s, m, l, xl)
     */
    private function extractEngineSize(string $engineId): string
    {
        // Engine IDs follow pattern: engine_{faction}_{size}_...
        // Example: engine_arg_m_allround_01_mk1
        foreach (self::SHIP_SIZES as $size) {
            if (str_contains($engineId, '_' . $size . '_')) {
                return $size;
            }
        }
        return 'm'; // Default to medium
    }

    /**
     * Estimates reverse thrust based on forward thrust.
     * Typically 40-60% of forward thrust in X4.
     *
     * @param float $thrustForward Forward thrust in kN
     * @return float Estimated reverse thrust
     */
    private function estimateThrustReverse(float $thrustForward): float
    {
        return $thrustForward * 0.5;
    }

    /**
     * Estimates boost thrust based on forward thrust.
     * Typically 180-220% of forward thrust in X4.
     *
     * @param float $thrustForward Forward thrust in kN
     * @return float Estimated boost thrust
     */
    private function estimateThrustBoost(float $thrustForward): float
    {
        return $thrustForward * 2.0;
    }

    /**
     * Estimates travel thrust based on forward thrust.
     * Typically 350-450% of forward thrust in X4.
     *
     * @param float $thrustForward Forward thrust in kN
     * @return float Estimated travel thrust
     */
    private function estimateThrustTravel(float $thrustForward): float
    {
        return $thrustForward * 4.0;
    }
}
