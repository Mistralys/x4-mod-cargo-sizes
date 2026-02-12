/**
 * Engine picker component with thrust display.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { EngineDef } from '../../types/physics';
import { InfoTooltip } from '../UI/InfoTooltip';

interface EnginePickerProps {
  engines: EngineDef[];
  selectedEngineId: string | null;
  onEngineSelect: (engineId: string | null) => void;
  loading: boolean;
  shipSelected: boolean;
}

const ENGINE_TOOLTIP =
  'Selecting an engine enables thrust-to-weight ratio (TWR) calculation and shows how physics adjustments affect acceleration. The engine must be compatible with the selected ship.';

export function EnginePicker({
  engines,
  selectedEngineId,
  onEngineSelect,
  loading,
  shipSelected,
}: EnginePickerProps) {
  const selectedEngine = engines.find((e) => e.id === selectedEngineId);

  if (!shipSelected) {
    return (
      <div className="mb-4">
        <label className="text-sm font-medium text-gray-700 flex items-center gap-2 mb-2">
          Select Engine (Optional)
          <InfoTooltip content={ENGINE_TOOLTIP} />
        </label>
        <div className="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500">
          Select a ship first
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="mb-4">
        <label className="text-sm font-medium text-gray-700 flex items-center gap-2 mb-2">
          Select Engine (Optional)
          <InfoTooltip content={ENGINE_TOOLTIP} />
        </label>
        <div className="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500">
          Loading engines...
        </div>
      </div>
    );
  }

  if (engines.length === 0) {
    return (
      <div className="mb-4">
        <label className="text-sm font-medium text-gray-700 flex items-center gap-2 mb-2">
          Select Engine (Optional)
          <InfoTooltip content={ENGINE_TOOLTIP} />
        </label>
        <div className="px-3 py-2 bg-yellow-50 border border-yellow-300 rounded-lg text-sm text-yellow-800">
          No engines available for this ship
        </div>
      </div>
    );
  }

  return (
    <div className="mb-4">
      <label className="text-sm font-medium text-gray-700 flex items-center gap-2 mb-2">
        Select Engine (Optional)
        <InfoTooltip content={ENGINE_TOOLTIP} />
      </label>
      <select
        value={selectedEngineId || ''}
        onChange={(e) => onEngineSelect(e.target.value || null)}
        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">-- No engine (skip TWR calculation) --</option>
        {engines.map((engine) => (
          <option key={engine.id} value={engine.id}>
            {engine.name}
          </option>
        ))}
      </select>

      {selectedEngine && (
        <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
          <div className="text-sm font-medium text-green-900 mb-2">{selectedEngine.name}</div>
          <div className="grid grid-cols-2 gap-2 text-xs text-green-700">
            <div>
              <span className="font-medium">Forward:</span> {selectedEngine.forwardThrust.toLocaleString()}N
            </div>
            <div>
              <span className="font-medium">Reverse:</span> {selectedEngine.reverseThrust.toLocaleString()}N
            </div>
            <div>
              <span className="font-medium">Boost:</span> {selectedEngine.boostThrust.toLocaleString()}N
            </div>
            <div>
              <span className="font-medium">Travel:</span> {selectedEngine.travelThrust.toLocaleString()}N
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
