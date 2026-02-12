/**
 * Ship type filter component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ShipType } from '../../types/ships';

interface TypeFilterProps {
  selectedType: ShipType | null;
  onTypeChange: (type: ShipType) => void;
  availableTypes: Array<{ type: ShipType; label: string }>;
}

export function TypeFilter({ selectedType, onTypeChange, availableTypes }: TypeFilterProps) {
  return (
    <div className="mb-4">
      <label className="text-sm font-medium text-gray-700 mb-2 block">Ship Type</label>
      <div className="flex flex-wrap gap-2">
        {availableTypes.map(({ type, label }) => (
          <button
            key={type}
            type="button"
            onClick={() => onTypeChange(type)}
            className={`
              px-4 py-2 text-sm font-medium rounded-lg transition-colors
              ${
                selectedType === type
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }
            `}
          >
            {label}
          </button>
        ))}
      </div>
    </div>
  );
}
