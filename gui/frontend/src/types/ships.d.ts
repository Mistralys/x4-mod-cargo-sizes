/**
 * Ship data type definitions matching backend DTOs.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

/**
 * Ship size categories.
 */
export type ShipSize = 'xs' | 's' | 'm' | 'l' | 'xl';

/**
 * Ship type identifier.
 */
export type ShipType = 'transport' | 'mining' | 'auxiliary' | 'carrier';

/**
 * Ship type info returned from /api/ships/types.
 */
export interface ShipTypeInfo {
  type: ShipType;
  label: string;
}

/**
 * Basic ship information (list item).
 * Matches getShipsByType() backend response.
 */
export interface ShipInfo {
  id: string;
  name: string;
  type: ShipType;
  size: ShipSize;
  mass: number;
  cargo: number;
}

/**
 * Detailed ship information (matching ShipDetails DTO toArray() output).
 */
export interface ShipDetails {
  id: string;
  name: string;
  type: ShipType;
  size: ShipSize;
  mass: number;
  cargo: number;
  engines: string[];
  engineCount: number;
  cargoType: string;
  dragOriginal: Record<string, number>;
  inertiaOriginal: Record<string, number>;
  jerkOriginal: Record<string, number>;
}
