/**
 * Main configuration panel component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect } from 'react';
import { useConfig } from '../../hooks/useConfig';
import type { BuildConfig } from '../../types/config';
import { SliderInput } from './SliderInput';
import { ActionButtons } from './ActionButtons';
import { CargoMultiplierSelector } from './CargoMultiplierSelector';
import { Spinner } from '../UI/Spinner';
import { Card } from '../UI/Card';

interface ConfigPanelProps {
  onChange?: (config: BuildConfig, multiplier: number) => void;
}

// Tooltip content for physics parameters
const TOOLTIPS = {
  cargoMultiplier:
    'The multiplier applied to ship cargo capacity. Higher values create larger cargo holds but also increase ship mass, requiring physics adjustments.',
  accelerationResponsiveness:
    'Compensates the acceleration loss from increased mass. 1.0 = fully preserve original acceleration feel. 0.7 = slightly heavier feel. Range: 0.1 (very sluggish) to 5.0 (very snappy).',
};

export function ConfigPanel({ onChange }: ConfigPanelProps) {
  const { config, loading, error, saveConfig, resetToDefault } = useConfig();
  const [localConfig, setLocalConfig] = useState<BuildConfig | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [selectedMultiplier, setSelectedMultiplier] = useState<number>(2);

  // Initialize local config when loaded
  useEffect(() => {
    if (config) {
      setLocalConfig(config);
      // Set initial multiplier from config
      if (config['cargo-multipliers']?.length > 0) {
        setSelectedMultiplier(config['cargo-multipliers'][0]);
      }
    }
  }, [config]);

  // Notify parent of config changes
  useEffect(() => {
    if (localConfig && onChange) {
      onChange(localConfig, selectedMultiplier);
    }
  }, [localConfig, selectedMultiplier, onChange]);

  const handleFlightMechanicsChange = useCallback(
    (field: 'accelerationResponsiveness', value: number) => {
      if (!localConfig) return;

      setLocalConfig({
        ...localConfig,
        'flight-mechanics': {
          ...localConfig['flight-mechanics'],
          [field]: value,
        },
      });
    },
    [localConfig]
  );

  const handleCargoMultiplierChange = useCallback((multiplier: number) => {
    setSelectedMultiplier(multiplier);
  }, []);

  const handleSave = useCallback(async () => {
    if (!localConfig) return;

    setIsSaving(true);
    setSaveError(null);

    try {
      const success = await saveConfig(localConfig);
      if (!success) {
        setSaveError('Failed to save configuration. Please try again.');
      }
    } catch (err) {
      setSaveError(err instanceof Error ? err.message : 'An unexpected error occurred');
    } finally {
      setIsSaving(false);
    }
  }, [localConfig, saveConfig]);

  const handleReset = useCallback(() => {
    resetToDefault();
    // The config will be reset through the useConfig hook
  }, [resetToDefault]);

  if (loading) {
    return (
      <div className="p-6">
        <Card title="Configuration Panel">
          <div className="flex flex-col items-center justify-center py-12">
            <Spinner size="lg" />
            <p className="mt-4 text-sm text-gray-600">Loading configuration...</p>
          </div>
        </Card>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <Card title="Configuration Panel">
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-sm text-red-800">
              <strong>Error loading configuration:</strong> {error}
            </p>
          </div>
        </Card>
      </div>
    );
  }

  if (!localConfig) {
    return null;
  }

  const flightMechanics = localConfig['flight-mechanics'];

  return (
    <div className="p-6">
      <Card title="Physics Configuration">
        <div className="space-y-6">
          {/* Cargo Multiplier Selector */}
          <div className="pb-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Cargo Multiplier</h3>
            <CargoMultiplierSelector
              value={selectedMultiplier}
              onChange={handleCargoMultiplierChange}
              presets={config?.['cargo-multipliers'] || [2, 4, 6, 8, 10]}
              tooltip={TOOLTIPS.cargoMultiplier}
            />
          </div>

          {/* Acceleration Responsiveness */}
          <div className="pb-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Physics Tuning</h3>

            <SliderInput
              label="Acceleration Responsiveness"
              value={flightMechanics.accelerationResponsiveness}
              min={0.1}
              max={5.0}
              step={0.05}
              onChange={(value) =>
                handleFlightMechanicsChange('accelerationResponsiveness', value)
              }
              tooltip={TOOLTIPS.accelerationResponsiveness}
            />
          </div>

          {/* Action Buttons */}
          <ActionButtons
            onSave={handleSave}
            onReset={handleReset}
            isSaving={isSaving}
            saveError={saveError}
          />
        </div>
      </Card>
    </div>
  );
}
