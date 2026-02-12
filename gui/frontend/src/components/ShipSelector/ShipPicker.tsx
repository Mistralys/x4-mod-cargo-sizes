/**
 * Ship picker dropdown component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ShipInfo, ShipDetails } from '../../types/ships';

interface ShipPickerProps {
  ships: ShipInfo[];
  selectedShip: ShipDetails | null;
  onShipSelect: (shipId: string) => void;
  loading: boolean;
}

export function ShipPicker({ ships, selectedShip, onShipSelect, loading }: ShipPickerProps) {
  if (loading) {
    return (
      <div className="mb-4">
        <label className="text-sm font-medium text-gray-700 mb-2 block">Select Ship</label>
        <div className="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500">
          Loading ships...
        </div>
      </div>
    );
  }

  if (ships.length === 0) {
    return (
      <div className="mb-4">
        <label className="text-sm font-medium text-gray-700 mb-2 block">Select Ship</label>
        <div className="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500">
          No ships available for the selected filters
        </div>
      </div>
    );
  }

  return (
    <div className="mb-4">
      <label className="text-sm font-medium text-gray-700 mb-2 block">Select Ship</label>
      <select
        value={selectedShip?.id || ''}
        onChange={(e) => onShipSelect(e.target.value)}
        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">-- Choose a ship --</option>
        {ships.map((ship) => (
          <option key={ship.id} value={ship.id}>
            {ship.name} ({ship.size.toUpperCase()})
          </option>
        ))}
      </select>

      {selectedShip && (
        <div className="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900">{selectedShip.name}</div>
            <div className="text-xs text-blue-700 mt-1">
              Type: {selectedShip.type} | Size: {selectedShip.size.toUpperCase()} | Mass:{' '}
              {selectedShip.mass.toLocaleString()}t | Cargo: {selectedShip.cargo.toLocaleString()}m³
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
