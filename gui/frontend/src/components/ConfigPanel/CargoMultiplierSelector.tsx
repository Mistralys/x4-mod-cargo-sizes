/**
 * Cargo multiplier selector with preset buttons and custom input.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, type ChangeEvent } from 'react';
import { InfoTooltip } from '../UI/InfoTooltip';

interface CargoMultiplierSelectorProps {
  value: number;
  onChange: (value: number) => void;
  presets?: number[];
  tooltip?: string;
}

const DEFAULT_PRESETS = [2, 4, 6, 8, 10];

export function CargoMultiplierSelector({
  value,
  onChange,
  presets = DEFAULT_PRESETS,
  tooltip,
}: CargoMultiplierSelectorProps) {
  const [isCustom, setIsCustom] = useState(() => !presets.includes(value));
  const [customValue, setCustomValue] = useState(value.toString());

  const handlePresetClick = useCallback(
    (preset: number) => {
      setIsCustom(false);
      onChange(preset);
    },
    [onChange]
  );

  const handleCustomClick = useCallback(() => {
    setIsCustom(true);
    setCustomValue(value.toString());
  }, [value]);

  const handleCustomChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
    setCustomValue(e.target.value);
  }, []);

  const handleCustomBlur = useCallback(() => {
    const numValue = parseFloat(customValue);
    if (!isNaN(numValue) && numValue > 0 && numValue <= 100) {
      onChange(numValue);
    } else {
      // Reset to current value if invalid
      setCustomValue(value.toString());
    }
  }, [customValue, onChange, value]);

  const handleCustomKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === 'Enter') {
        handleCustomBlur();
      } else if (e.key === 'Escape') {
        setIsCustom(false);
      }
    },
    [handleCustomBlur]
  );

  return (
    <div className="mb-6">
      <label className="text-sm font-medium text-gray-700 flex items-center gap-2 mb-2">
        Cargo Multiplier
        {tooltip && <InfoTooltip content={tooltip} />}
      </label>

      <div className="flex flex-wrap gap-2 mb-3">
        {presets.map((preset) => (
          <button
            key={preset}
            type="button"
            onClick={() => handlePresetClick(preset)}
            className={`
              px-4 py-2 text-sm font-medium rounded-lg transition-colors
              ${
                !isCustom && value === preset
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }
            `}
          >
            {preset}x
          </button>
        ))}

        <button
          type="button"
          onClick={handleCustomClick}
          className={`
            px-4 py-2 text-sm font-medium rounded-lg transition-colors
            ${
              isCustom
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }
          `}
        >
          Custom
        </button>
      </div>

      {isCustom && (
        <div className="flex items-center gap-2">
          <input
            type="number"
            value={customValue}
            onChange={handleCustomChange}
            onBlur={handleCustomBlur}
            onKeyDown={handleCustomKeyDown}
            className="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter value..."
            min="0.1"
            max="100"
            step="0.1"
            autoFocus
          />
          <span className="text-sm text-gray-600">×</span>
          <span className="text-xs text-gray-500">(0.1 - 100)</span>
        </div>
      )}

      <p className="mt-2 text-xs text-gray-500">
        The multiplier applied to ship cargo capacity. Higher values = larger cargo holds.
      </p>
    </div>
  );
}
