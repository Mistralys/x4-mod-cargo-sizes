<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ShipDetails;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
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
    private const string SHIP_TYPE_MINING = 'miner';
    private const string SHIP_TYPE_AUXILIARY = 'resupplier';
    private const string SHIP_TYPE_CARRIER = 'carrier';

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
     * NOTE: This is a simplified implementation. Full implementation would require
     * loading extracted ship data from the game files, which is beyond the scope
     * of the initial GUI. For now, we return a subset of well-known ships for testing.
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

        // TODO: In production, this would query extracted game data to get all ships of this type
        // For now, return sample ships for each type for testing purposes
        $sampleShips = [
            'transport' => [
                ['id' => 'ship_arg_l_trans_container_01_a', 'name' => 'Colossus Vanguard (Argon L Freighter)', 'size' => 'l', 'mass' => 500.0, 'cargo' => 30000.0],
                ['id' => 'ship_arg_m_trans_container_01_a', 'name' => 'Mercury Vanguard (Argon M Freighter)', 'size' => 'm', 'mass' => 200.0, 'cargo' => 12000.0],
                ['id' => 'ship_par_xl_trans_container_01_a', 'name' => 'Shuyaku Vanguard (Paranid XL Freighter)', 'size' => 'xl', 'mass' => 650.415, 'cargo' => 37000.0]
            ],
            'mining' => [
                ['id' => 'ship_arg_l_miner_liquid_01_a', 'name' => 'Magnetar (Liquid) Vanguard', 'size' => 'l', 'mass' => 205.27, 'cargo' => 42000.0],
                ['id' => 'ship_arg_m_miner_solid_01_a', 'name' => 'Platypus Vanguard', 'size' => 'm', 'mass' => 150.0, 'cargo' => 9000.0]
            ],
            'auxiliary' => [
                ['id' => 'ship_arg_l_destroyer_01_a', 'name' => 'Destroyer Auxiliary', 'size' => 'l', 'mass' => 800.0, 'cargo' => 5000.0]
            ],
            'carrier' => [
                ['id' => 'ship_arg_xl_carrier_01_a', 'name' => 'Raptor Vanguard', 'size' => 'xl', 'mass' => 1200.0, 'cargo' => 8000.0]
            ]
        ];

        return $sampleShips[$type] ?? [];
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
            // NOTE: This is simplified for the initial implementation
            // In production, this would load from extracted game data
            $shipDef = ShipDefs::getInstance()->getByID($shipId);
            
            // Get basic ship info
            $type = 'transport'; // Would be determined from ship classification
            $size = $this->extractShipSize($shipId);
            $mass = $shipDef->getMass();
            
            // Get cargo capacity (would come from storage modules)
            $cargo = 10000.0; // Placeholder
            
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
     * @param string $shipId Ship identifier
     * @return array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>
     * @throws GUIException
     */
    public function getEnginesForShip(string $shipId): array
    {
        try {
            // TODO: In production, retrieve compatible engines from ShipDef
            // For now, return sample engines based on ship size
            $size = $this->extractShipSize($shipId);
            
            return $this->getSampleEnginesBySize($size);
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
     * @return array<array{id: string, name: string, thrustForward: float}>
     * @throws GUIException
     */
    public function getAllEngines(): array
    {
        try {
            // Use X4 Core to get all engines
            $engineDefs = EngineDefs::getInstance();
            $engines = [];

            foreach ($engineDefs->getAll() as $engineDef) {
                $engines[] = [
                    'id' => $engineDef->getID(),
                    'name' => $engineDef->getLabel(),
                    'thrustForward' => $engineDef->getThrustForward()
                ];
            }

            return $engines;
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
        foreach (CargoSizeExtractor::SHIP_SIZES as $size) {
            if (str_contains($shipId, '_' . $size . '_')) {
                return $size;
            }
        }
        return 'm'; // Default to medium
    }

    /**
     * Gets sample engines by ship size (temporary implementation).
     *
     * @param string $size
     * @return array<array{id: string, name: string, thrustForward: float, thrustReverse: float, thrustBoost: float, thrustTravel: float}>
     */
    private function getSampleEnginesBySize(string $size): array
    {
        $engines = [
            's' => [
                ['id' => 'engine_arg_s_allround_01_mk1', 'name' => 'Argon S Engine MK1', 'thrustForward' => 500.0, 'thrustReverse' => 250.0, 'thrustBoost' => 1000.0, 'thrustTravel' => 2000.0]
            ],
            'm' => [
                ['id' => 'engine_arg_m_allround_01_mk1', 'name' => 'Argon M Engine MK1', 'thrustForward' => 1500.0, 'thrustReverse' => 750.0, 'thrustBoost' => 3000.0, 'thrustTravel' => 6000.0]
            ],
            'l' => [
                ['id' => 'engine_arg_l_allround_01_mk1', 'name' => 'Argon L Engine MK1', 'thrustForward' => 4000.0, 'thrustReverse' => 2000.0, 'thrustBoost' => 8000.0, 'thrustTravel' => 16000.0]
            ],
            'xl' => [
                ['id' => 'engine_arg_xl_allround_01_mk1', 'name' => 'Argon XL Engine MK1', 'thrustForward' => 10000.0, 'thrustReverse' => 5000.0, 'thrustBoost' => 20000.0, 'thrustTravel' => 40000.0]
            ]
        ];

        return $engines[$size] ?? $engines['m'];
    }
}
