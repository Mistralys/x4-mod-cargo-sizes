/**
 * Ship data hook with caching.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback } from 'react';
import { shipsApi } from '../services/api';
import type { ShipTypeInfo, ShipInfo, ShipDetails } from '../types/ships';
import type { EngineDef } from '../types/physics';

interface UseShipDataResult {
  shipTypes: ShipTypeInfo[];
  ships: ShipInfo[];
  shipDetails: ShipDetails | null;
  engines: EngineDef[];
  allEngines: EngineDef[];
  loading: boolean;
  error: string | null;
  loadShipTypes: () => Promise<void>;
  loadShipsByType: (type: string) => Promise<void>;
  loadShipDetails: (shipId: string) => Promise<void>;
  loadEnginesForShip: (shipId: string) => Promise<void>;
  loadAllEngines: () => Promise<void>;
  reset: () => void;
}

/**
 * Cache for ship data to avoid redundant fetches.
 */
const cache: {
  shipTypes: ShipTypeInfo[] | null;
  shipsByType: Map<string, ShipInfo[]>;
  shipDetails: Map<string, ShipDetails>;
  enginesForShip: Map<string, EngineDef[]>;
  allEngines: EngineDef[] | null;
} = {
  shipTypes: null,
  shipsByType: new Map(),
  shipDetails: new Map(),
  enginesForShip: new Map(),
  allEngines: null,
};

/**
 * Hook for fetching ship and engine data with caching to avoid redundant API calls.
 */
export function useShipData(): UseShipDataResult {
  const [shipTypes, setShipTypes] = useState<ShipTypeInfo[]>([]);
  const [ships, setShips] = useState<ShipInfo[]>([]);
  const [shipDetails, setShipDetails] = useState<ShipDetails | null>(null);
  const [engines, setEngines] = useState<EngineDef[]>([]);
  const [allEngines, setAllEngines] = useState<EngineDef[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadShipTypes = useCallback(async () => {
    if (cache.shipTypes) {
      setShipTypes(cache.shipTypes);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const types = await shipsApi.getTypes();
      cache.shipTypes = types;
      setShipTypes(types);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load ship types');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadShipsByType = useCallback(async (type: string) => {
    if (cache.shipsByType.has(type)) {
      setShips(cache.shipsByType.get(type)!);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const shipList = await shipsApi.getShipsByType(type);
      cache.shipsByType.set(type, shipList);
      setShips(shipList);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load ships');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadShipDetails = useCallback(async (shipId: string) => {
    if (cache.shipDetails.has(shipId)) {
      setShipDetails(cache.shipDetails.get(shipId)!);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const details = await shipsApi.getDetails(shipId);
      cache.shipDetails.set(shipId, details);
      setShipDetails(details);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load ship details');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadEnginesForShip = useCallback(async (shipId: string) => {
    if (cache.enginesForShip.has(shipId)) {
      setEngines(cache.enginesForShip.get(shipId)!);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const engineList = await shipsApi.getEnginesForShip(shipId);
      cache.enginesForShip.set(shipId, engineList);
      setEngines(engineList);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load engines');
    } finally {
      setLoading(false);
    }
  }, []);

  const loadAllEngines = useCallback(async () => {
    if (cache.allEngines) {
      setAllEngines(cache.allEngines);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const engineList = await shipsApi.getAllEngines();
      cache.allEngines = engineList;
      setAllEngines(engineList);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load all engines');
    } finally {
      setLoading(false);
    }
  }, []);

  const reset = useCallback(() => {
    setShipTypes([]);
    setShips([]);
    setShipDetails(null);
    setEngines([]);
    setAllEngines([]);
    setLoading(false);
    setError(null);
  }, []);

  return {
    shipTypes,
    ships,
    shipDetails,
    engines,
    allEngines,
    loading,
    error,
    loadShipTypes,
    loadShipsByType,
    loadShipDetails,
    loadEnginesForShip,
    loadAllEngines,
    reset,
  };
}
