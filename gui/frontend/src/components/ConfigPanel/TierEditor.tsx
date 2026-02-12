/**
 * Editable tier table editor for drag/jerk reduction tiers.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, type ChangeEvent } from 'react';
import type { Tier } from '../../types/physics';
import { InfoTooltip } from '../UI/InfoTooltip';

interface TierEditorProps {
  label: string;
  tiers: Tier[];
  onChange: (tiers: Tier[]) => void;
  tooltip?: string;
}

export function TierEditor({ label, tiers, onChange, tooltip }: TierEditorProps) {
  const [validationErrors, setValidationErrors] = useState<string[]>([]);

  const validateTiers = useCallback((newTiers: Tier[]): string[] => {
    const errors: string[] = [];

    // Check ascending multipliers
    for (let i = 1; i < newTiers.length; i++) {
      if (newTiers[i].maxMultiplier <= newTiers[i - 1].maxMultiplier) {
        errors.push(`Tier ${i + 1}: maxMultiplier must be greater than previous tier`);
      }
    }

    // Check reduction percent range
    newTiers.forEach((tier, idx) => {
      if (tier.reductionPercent < 0 || tier.reductionPercent > 1) {
        errors.push(`Tier ${idx + 1}: reductionPercent must be between 0 and 1`);
      }
    });

    return errors;
  }, []);

  const handleTierChange = useCallback(
    (index: number, field: 'maxMultiplier' | 'reductionPercent', value: string) => {
      const numValue = parseFloat(value);
      if (isNaN(numValue)) return;

      const newTiers = [...tiers];
      newTiers[index] = { ...newTiers[index], [field]: numValue };

      const errors = validateTiers(newTiers);
      setValidationErrors(errors);

      if (errors.length === 0) {
        onChange(newTiers);
      }
    },
    [tiers, onChange, validateTiers]
  );

  const handleAddTier = useCallback(() => {
    const lastTier = tiers[tiers.length - 1];
    const newMaxMultiplier = lastTier ? lastTier.maxMultiplier + 2 : 2;
    const newReductionPercent = lastTier ? Math.min(lastTier.reductionPercent + 0.1, 1.0) : 0.1;

    const newTiers = [
      ...tiers,
      { maxMultiplier: newMaxMultiplier, reductionPercent: newReductionPercent },
    ];

    const errors = validateTiers(newTiers);
    setValidationErrors(errors);

    if (errors.length === 0) {
      onChange(newTiers);
    }
  }, [tiers, onChange, validateTiers]);

  const handleRemoveTier = useCallback(
    (index: number) => {
      if (tiers.length <= 1) {
        setValidationErrors(['At least one tier is required']);
        return;
      }

      const newTiers = tiers.filter((_, idx) => idx !== index);
      const errors = validateTiers(newTiers);
      setValidationErrors(errors);

      if (errors.length === 0) {
        onChange(newTiers);
      }
    },
    [tiers, onChange, validateTiers]
  );

  return (
    <div className="mb-6">
      <div className="flex items-center justify-between mb-2">
        <label className="text-sm font-medium text-gray-700 flex items-center gap-2">
          {label}
          {tooltip && <InfoTooltip content={tooltip} />}
        </label>
        <button
          type="button"
          onClick={handleAddTier}
          className="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          + Add Tier
        </button>
      </div>

      {validationErrors.length > 0 && (
        <div className="mb-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
          {validationErrors.map((error, idx) => (
            <div key={idx}>• {error}</div>
          ))}
        </div>
      )}

      <div className="border border-gray-300 rounded-lg overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-100">
            <tr>
              <th className="px-3 py-2 text-left font-medium text-gray-700">Max Multiplier</th>
              <th className="px-3 py-2 text-left font-medium text-gray-700">Reduction %</th>
              <th className="w-12"></th>
            </tr>
          </thead>
          <tbody>
            {tiers.map((tier, index) => (
              <tr key={index} className="border-t border-gray-200 hover:bg-gray-50">
                <td className="px-3 py-2">
                  <input
                    type="number"
                    value={tier.maxMultiplier}
                    onChange={(e: ChangeEvent<HTMLInputElement>) =>
                      handleTierChange(index, 'maxMultiplier', e.target.value)
                    }
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    step="0.1"
                    min="0"
                  />
                </td>
                <td className="px-3 py-2">
                  <input
                    type="number"
                    value={tier.reductionPercent}
                    onChange={(e: ChangeEvent<HTMLInputElement>) =>
                      handleTierChange(index, 'reductionPercent', e.target.value)
                    }
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    step="0.01"
                    min="0"
                    max="1"
                  />
                </td>
                <td className="px-2 py-2">
                  <button
                    type="button"
                    onClick={() => handleRemoveTier(index)}
                    className="text-red-600 hover:text-red-800 transition-colors"
                    title="Remove tier"
                  >
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                        clipRule="evenodd"
                      />
                    </svg>
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <p className="mt-2 text-xs text-gray-500">
        Tiers define reduction percentages based on cargo multiplier ranges. Multipliers must be in
        ascending order.
      </p>
    </div>
  );
}
