/**
 * Boolean toggle switch component with label and optional tooltip.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { InfoTooltip } from '../UI/InfoTooltip';

interface ToggleInputProps {
  label: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  tooltip?: string;
}

export function ToggleInput({ label, checked, onChange, tooltip }: ToggleInputProps) {
  return (
    <div className="flex items-center justify-between mb-4">
      <label className="text-sm font-medium text-gray-700 flex items-center gap-2">
        {label}
        {tooltip && <InfoTooltip content={tooltip} />}
      </label>
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        onClick={() => onChange(!checked)}
        className={`
          relative inline-flex h-6 w-11 items-center rounded-full transition-colors
          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
          ${checked ? 'bg-blue-600' : 'bg-gray-300'}
        `}
      >
        <span
          className={`
            inline-block h-4 w-4 transform rounded-full bg-white transition-transform
            ${checked ? 'translate-x-6' : 'translate-x-1'}
          `}
        />
      </button>
    </div>
  );
}
