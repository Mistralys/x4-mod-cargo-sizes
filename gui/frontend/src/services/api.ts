/**
 * API client service for backend communication.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import axios from 'axios';
import type { AxiosInstance } from 'axios';
import type { PhysicsConfig, PhysicsResponse, EngineDef, ClassRangeRequest, ClassRangeResponse } from '../types/physics';
import type { ShipTypeInfo, ShipInfo, ShipDetails } from '../types/ships';
import type { BuildConfig, ValidationResult } from '../types/config';

/**
 * API timeout configuration per endpoint type.
 *
 * Rationale:
 * - physics: Single-ship calculations are fast (~50ms backend time)
 * - classRange: Iterates 80+ ships (~80ms backend), needs buffer for network latency
 * - shipData: Simple data fetch, typically fast
 * - default: Conservative timeout for unknown endpoints
 */
const API_TIMEOUTS = {
  physics: 10000,      // 10s - Single-ship physics calculation
  classRange: 15000,   // 15s - Class-wide calculations (80+ ships)
  shipData: 10000,     // 10s - Ship data fetching
  default: 10000       // 10s - Fallback for other endpoints
} as const;

/**
 * Base API client configuration.
 */
const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: API_TIMEOUTS.default,
});

/**
 * Physics calculation API methods.
 */
export const physicsApi = {
  /**
   * Calculate physics for a single configuration.
   */
  async calculate(request: PhysicsConfig): Promise<PhysicsResponse> {
    const response = await apiClient.post<PhysicsResponse>(
      '/calculate/physics',
      request,
      { timeout: API_TIMEOUTS.physics }
    );
    return response.data;
  },

  /**
   * Calculate physics for multiple configurations in one request.
   */
  async calculateBatch(requests: PhysicsConfig[]): Promise<PhysicsResponse[]> {
    const response = await apiClient.post<{ results: PhysicsResponse[] }>(
      '/calculate/batch',
      { requests },
      { timeout: API_TIMEOUTS.physics }
    );
    return response.data.results;
  },
};

/**
 * Class-range calculation API methods.
 */
export const classRangeApi = {
  /**
   * Calculate min/max/median ranges for all ships of a given type.
   */
  async calculate(request: ClassRangeRequest): Promise<ClassRangeResponse> {
    const response = await apiClient.post<ClassRangeResponse>(
      '/calculate/class-range',
      request,
      { timeout: API_TIMEOUTS.classRange }
    );
    return response.data;
  },
};

/**
 * Ship and engine data API methods.
 */
export const shipsApi = {
  /**
   * Get all supported ship types.
   */
  async getTypes(): Promise<ShipTypeInfo[]> {
    const response = await apiClient.get<{ types: ShipTypeInfo[] }>(
      '/ships/types',
      { timeout: API_TIMEOUTS.shipData }
    );
    return response.data.types;
  },

  /**
   * Get ships filtered by type.
   */
  async getShipsByType(type: string): Promise<ShipInfo[]> {
    const response = await apiClient.get<{ ships: ShipInfo[] }>(
      `/ships/${type}`,
      { timeout: API_TIMEOUTS.shipData }
    );
    return response.data.ships;
  },

  /**
   * Get detailed information for a specific ship.
   */
  async getDetails(shipId: string): Promise<ShipDetails> {
    const response = await apiClient.get<ShipDetails>(
      `/ships/details/${shipId}`,
      { timeout: API_TIMEOUTS.shipData }
    );
    return response.data;
  },

  /**
   * Get compatible engines for a specific ship.
   */
  async getEnginesForShip(shipId: string): Promise<EngineDef[]> {
    const response = await apiClient.get<{ engines: EngineDef[] }>(
      `/ships/${shipId}/engines`,
      { timeout: API_TIMEOUTS.shipData }
    );
    return response.data.engines;
  },

  /**
   * Get all available engines.
   */
  async getAllEngines(): Promise<EngineDef[]> {
    const response = await apiClient.get<{ engines: EngineDef[] }>(
      '/engines',
      { timeout: API_TIMEOUTS.shipData }
    );
    return response.data.engines;
  },
};

/**
 * Configuration API methods.
 */
export const configApi = {
  /**
   * Get current build configuration.
   */
  async get(): Promise<BuildConfig> {
    const response = await apiClient.get<BuildConfig>('/config');
    return response.data;
  },

  /**
   * Save updated configuration.
   */
  async update(config: BuildConfig): Promise<{ success: boolean; message: string }> {
    const response = await apiClient.post<{ success: boolean; message: string }>('/config', config);
    return response.data;
  },

  /**
   * Validate configuration without saving.
   */
  async validate(config: BuildConfig): Promise<ValidationResult> {
    const response = await apiClient.post<ValidationResult>('/config/validate', config);
    return response.data;
  },
};
