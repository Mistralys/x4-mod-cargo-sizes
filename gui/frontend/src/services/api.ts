/**
 * API client service for backend communication.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import axios from 'axios';
import type { AxiosInstance } from 'axios';
import type { PhysicsConfig, PhysicsResponse, EngineDef } from '../types/physics';
import type { ShipTypeInfo, ShipInfo, ShipDetails } from '../types/ships';
import type { BuildConfig, ValidationResult } from '../types/config';

/**
 * Base API client configuration.
 */
const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 10000,
});

/**
 * Physics calculation API methods.
 */
export const physicsApi = {
  /**
   * Calculate physics for a single configuration.
   */
  async calculate(request: PhysicsConfig): Promise<PhysicsResponse> {
    const response = await apiClient.post<PhysicsResponse>('/calculate/physics', request);
    return response.data;
  },

  /**
   * Calculate physics for multiple configurations in one request.
   */
  async calculateBatch(requests: PhysicsConfig[]): Promise<PhysicsResponse[]> {
    const response = await apiClient.post<{ results: PhysicsResponse[] }>('/calculate/batch', {
      requests,
    });
    return response.data.results;
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
    const response = await apiClient.get<{ types: ShipTypeInfo[] }>('/ships/types');
    return response.data.types;
  },

  /**
   * Get ships filtered by type.
   */
  async getShipsByType(type: string): Promise<ShipInfo[]> {
    const response = await apiClient.get<{ ships: ShipInfo[] }>(`/ships/${type}`);
    return response.data.ships;
  },

  /**
   * Get detailed information for a specific ship.
   */
  async getDetails(shipId: string): Promise<ShipDetails> {
    const response = await apiClient.get<ShipDetails>(`/ships/details/${shipId}`);
    return response.data;
  },

  /**
   * Get compatible engines for a specific ship.
   */
  async getEnginesForShip(shipId: string): Promise<EngineDef[]> {
    const response = await apiClient.get<{ engines: EngineDef[] }>(`/ships/${shipId}/engines`);
    return response.data.engines;
  },

  /**
   * Get all available engines.
   */
  async getAllEngines(): Promise<EngineDef[]> {
    const response = await apiClient.get<{ engines: EngineDef[] }>('/engines');
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
