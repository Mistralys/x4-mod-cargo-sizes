/**
 * Ship size filter component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ShipSize } from '../../types/ships';

interface SizeFilterProps {
  selectedSize: ShipSize | null;
  onSizeChange: (size: ShipSize | null) => void;
}

const SIZES: Array<{ value: ShipSize; label: string }> = [
  { value: 'xs', label: 'XS' },
  { value: 's', label: 'S' },
  { value: 'm', label: 'M' },
  { value: 'l', label: 'L' },
  { value: 'xl', label: 'XL' },
];

export function SizeFilter({ selectedSize, onSizeChange }: SizeFilterProps) {
  return (
    <div className="mb-4">
      <label className="text-sm font-medium text-gray-700 mb-2 block">Ship Size (Optional)</label>
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => onSizeChange(null)}
          className={`
            px-3 py-2 text-sm font-medium rounded-lg transition-colors
            ${
              selectedSize === null
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }
          `}
        >
          All
        </button>
        {SIZES.map(({ value, label }) => (
          <button
            key={value}
            type="button"
            onClick={() => onSizeChange(value)}
            className={`
              px-3 py-2 text-sm font-medium rounded-lg transition-colors
              ${
                selectedSize === value
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
