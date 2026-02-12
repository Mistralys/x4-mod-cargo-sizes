/**
 * Reusable slider input component with label, tooltip, and keyboard entry support.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, type ChangeEvent } from 'react';
import { InfoTooltip } from '../UI/InfoTooltip';

interface SliderInputProps {
  label: string;
  value: number;
  min: number;
  max: number;
  step: number;
  onChange: (value: number) => void;
  tooltip?: string;
  unit?: string;
}

export function SliderInput({
  label,
  value,
  min,
  max,
  step,
  onChange,
  tooltip,
  unit = '',
}: SliderInputProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [editValue, setEditValue] = useState(value.toString());

  const handleSliderChange = useCallback(
    (e: ChangeEvent<HTMLInputElement>) => {
      onChange(parseFloat(e.target.value));
    },
    [onChange]
  );

  const handleValueClick = useCallback(() => {
    setIsEditing(true);
    setEditValue(value.toString());
  }, [value]);

  const handleInputChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
    setEditValue(e.target.value);
  }, []);

  const handleInputBlur = useCallback(() => {
    const numValue = parseFloat(editValue);
    if (!isNaN(numValue) && numValue >= min && numValue <= max) {
      onChange(numValue);
    }
    setIsEditing(false);
  }, [editValue, min, max, onChange]);

  const handleInputKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === 'Enter') {
        handleInputBlur();
      } else if (e.key === 'Escape') {
        setIsEditing(false);
      }
    },
    [handleInputBlur]
  );

  return (
    <div className="mb-6">
      <div className="flex items-center justify-between mb-2">
        <label className="text-sm font-medium text-gray-700 flex items-center gap-2">
          {label}
          {tooltip && <InfoTooltip content={tooltip} />}
        </label>
        {isEditing ? (
          <input
            type="number"
            value={editValue}
            onChange={handleInputChange}
            onBlur={handleInputBlur}
            onKeyDown={handleInputKeyDown}
            className="w-20 px-2 py-1 text-sm font-mono text-right border border-blue-500 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
            min={min}
            max={max}
            step={step}
            autoFocus
          />
        ) : (
          <span
            onClick={handleValueClick}
            className="text-sm font-mono cursor-pointer px-2 py-1 rounded hover:bg-gray-100 transition-colors"
            title="Click to edit"
          >
            {value.toFixed(step < 1 ? 2 : 1)}
            {unit}
          </span>
        )}
      </div>
      <input
        type="range"
        min={min}
        max={max}
        step={step}
        value={value}
        onChange={handleSliderChange}
        className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
      />
      <div className="flex justify-between mt-1">
        <span className="text-xs text-gray-500">
          {min}
          {unit}
        </span>
        <span className="text-xs text-gray-500">
          {max}
          {unit}
        </span>
      </div>
    </div>
  );
}
