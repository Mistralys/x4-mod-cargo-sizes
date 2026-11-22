<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipDetails;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\ShipTypeConfig;
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
     * Cache for loaded ships (loaded once per request).
     * @var array<array{id: string, name: string, type: string, size: string, mass: float, cargo: float}>|null
     */
    private ?array $shipCache = null;

    /**
     * Cache for loaded engines (loaded once per request).
     * @var array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>|null
     */
    private ?array $engineCache = null;

    private ?ShipDefs $shipDefs;
    private ?EngineDefs $engineDefs;
    private ShipTypeConfig $shipTypeConfig;

    /**
     * @param ShipDefs|null $shipDefs
     * @param EngineDefs|null $engineDefs
     * @param ShipTypeConfig|null $shipTypeConfig Ship type definitions; defaults to the shared instance (`config/ship-types.json`).
     */
    public function __construct(
        ?ShipDefs $shipDefs = null,
        ?EngineDefs $engineDefs = null,
        ?ShipTypeConfig $shipTypeConfig = null
    ) {
        $this->shipDefs = $shipDefs;
        $this->engineDefs = $engineDefs;
        $this->shipTypeConfig = $shipTypeConfig ?? ShipTypeConfig::getInstance();
    }

    private function getShipDefs(): ShipDefs
    {
        return $this->shipDefs ?? ShipDefs::getInstance();
    }

    private function getEngineDefs(): EngineDefs
    {
        return $this->engineDefs ?? EngineDefs::getInstance();
    }

    /**
     * Gets all supported ship types.
     *
     * @return array{type: string, label: string}[]
     */
    public function getShipTypes(): array
    {
        return $this->shipTypeConfig->getTypes();
    }

    /**
     * Gets ships filtered by type.
     *
     * Loads all ships from X4 Core's ShipDefs and filters by the requested type.
     * Ships are classified by their macro name patterns (e.g., "ship_arg_m_trans_...")
     *
     * @param string $type Ship type (transport, mining, auxiliary, carrier)
     * @return array<array{id: string, name: string, type: string, size: string, mass: float, cargo: float}>
     * @throws GUIException
     */
    public function getShipsByType(string $type): array
    {
        if (!in_array($type, $this->shipTypeConfig->getTypeNames(), true)) {
            throw new GUIException(
                sprintf('Unknown ship type: %s', $type),
                '',
                GUIException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        // Load all ships if not cached
        if ($this->shipCache === null) {
            $this->loadAllShips();
        }

        // Filter by requested type
        return array_values(array_filter(
            $this->shipCache,
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
            $shipDef = $this->getShipDefs()->getByID($shipId);
            
            // Determine ship type from macro name
            $type = $this->determineShipType($shipId);
            if ($type === null) {
                $type = 'transport'; // Fallback default
            }
            
            $size = $this->extractShipSize($shipId);
            $mass = $shipDef->getMass();
            
            // Get cargo capacity with fallback
            $cargo = $this->getShipCargoCapacity($shipDef, $size);
            $cargoType = $shipDef->getCargoType();
            
            // Get compatible engines
            $engines = $this->getEnginesForShip($shipId);
            
            // Get engine count
            $engineCount = $shipDef->countEngines();
            
            // Get real drag values
            $dragOriginal = [
                'forward' => $shipDef->getDragForward(),
                'reverse' => $shipDef->getDragReverse(),
                'horizontal' => $shipDef->getDragHorizontal(),
                'vertical' => $shipDef->getDragVertical(),
                'pitch' => $shipDef->getDragPitch(),
                'yaw' => $shipDef->getDragYaw(),
                'roll' => $shipDef->getDragRoll()
            ];
            
            // Get real inertia values
            $inertiaOriginal = [
                'pitch' => $shipDef->getInertiaPitch(),
                'yaw' => $shipDef->getInertiaYaw(),
                'roll' => $shipDef->getInertiaRoll()
            ];
            
            // Get real jerk values
            $jerkOriginal = [
                'strafe' => $shipDef->getJerkStrafe(),
                'angular' => $shipDef->getJerkAngular(),
                'forwardAccel' => $shipDef->getJerkForwardAccel(),
                'forwardDecel' => $shipDef->getJerkForwardDecel(),
                'forwardRatio' => $shipDef->getJerkForwardRatio(),
                'boostAccel' => $shipDef->getJerkBoostAccel(),
                'boostRatio' => $shipDef->getJerkBoostRatio(),
                'travelAccel' => $shipDef->getJerkTravelAccel(),
                'travelDecel' => $shipDef->getJerkTravelDecel(),
                'travelRatio' => $shipDef->getJerkTravelRatio()
            ];

            return new ShipDetails(
                id: $shipId,
                name: $shipDef->getLabel(),
                type: $type,
                size: $size,
                mass: $mass,
                cargo: $cargo,
                engines: array_column($engines, 'id'),
                engineCount: $engineCount,
                cargoType: $cargoType,
                dragOriginal: $dragOriginal,
                inertiaOriginal: $inertiaOriginal,
                jerkOriginal: $jerkOriginal
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
            if ($this->engineCache === null) {
                $this->loadAllEngines();
            }
            
            $size = $this->extractShipSize($shipId);
            
            // Filter engines by size
            return array_values(array_filter(
                $this->engineCache,
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
            if ($this->engineCache === null) {
                $this->loadAllEngines();
            }

            return $this->engineCache;
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
        foreach (ShipTypeConfig::SIZES as $size) {
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
        $this->shipCache = [];
        $shipDefs = $this->getShipDefs();

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
            if (!in_array($size, ShipTypeConfig::SIZES, true)) {
                continue;
            }
            
            // Get cargo capacity with fallback
            $cargo = $this->getShipCargoCapacity($shipDef, $size);
            
            $this->shipCache[] = [
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
     * Delegates to {@see ShipTypeConfig::resolveType()}, which reads the
     * ship type definitions from `config/ship-types.json`.
     *
     * @param string $shipId Ship macro name
     * @return string|null Ship type or null if not classifiable
     */
    private function determineShipType(string $shipId): ?string
    {
        return $this->shipTypeConfig->resolveType($shipId);
    }

    /**
     * Gets ship cargo capacity.
     * 
     * Uses ShipDef.getCargoCapacity() from x4-core when available (>0).
     * Falls back to size-based estimates only when cargo capacity is 0.
     *
     * @param \Mistralys\X4\Database\Ships\ShipDef $shipDef
     * @param string $size Ship size
     * @return float Cargo capacity in cubic meters
     */
    private function getShipCargoCapacity($shipDef, string $size): float
    {
        // Try to get real cargo capacity from x4-core
        $realCapacity = $shipDef->getCargoCapacity();
        
        // Use real capacity if available (>0)
        if ($realCapacity > 0) {
            return (float)$realCapacity;
        }
        
        // Fallback: Use size-based estimates when cargo capacity is 0
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
     * Includes real thrust data (forward, reverse, boost, travel) from x4-core.
     *
     * @return void
     */
    private function loadAllEngines(): void
    {
        $this->engineCache = [];
        $engineDefs = $this->getEngineDefs();

        foreach ($engineDefs->getAll() as $engineDef) {
            $thrustForward = $engineDef->getThrustForward();
            $thrustReverse = $engineDef->getThrustReverse();
            $thrustBoost = $engineDef->getBoostThrust();
            $thrustTravel = $engineDef->getTravelThrust();
            
            $this->engineCache[] = [
                'id' => $engineDef->getID(),
                'name' => $engineDef->getLabel(),
                'thrustForward' => $thrustForward,
                'thrustReverse' => $thrustReverse,
                'thrustBoost' => $thrustBoost,
                'thrustTravel' => $thrustTravel
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
        foreach (ShipTypeConfig::SIZES as $size) {
            if (str_contains($engineId, '_' . $size . '_')) {
                return $size;
            }
        }
        return 'm'; // Default to medium
    }
}

