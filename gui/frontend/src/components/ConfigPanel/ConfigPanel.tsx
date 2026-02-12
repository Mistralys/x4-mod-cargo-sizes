/**
 * Main configuration panel component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect } from 'react';
import { useConfig } from '../../hooks/useConfig';
import type { BuildConfig, FlightMechanics } from '../../types/config';
import type { Tier } from '../../types/physics';
import { SliderInput } from './SliderInput';
import { TierEditor } from './TierEditor';
import { ToggleInput } from './ToggleInput';
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
  dragReductionFactor:
    'Base multiplier for drag reduction across all axes. Higher values reduce more drag to compensate for increased mass. Range: 0.5 (less reduction) to 2.0 (more reduction).',
  inertiaImpactFactor:
    'Controls how much the mass ratio affects rotational inertia. 0.0 = no inertia adjustment, 1.0 = full mass ratio applied to inertia. Affects ship turning responsiveness.',
  accelerationResponsiveness:
    'Adjusts acceleration compensation. Higher values increase jerk (acceleration rate) to maintain responsiveness. Range: 0.5 (slower) to 2.0 (faster).',
  dragReductionTiers:
    'Tier-based drag reduction percentages. Each tier applies additional reduction based on cargo multiplier ranges. Higher multipliers get more aggressive reduction.',
  jerkReductionTiers:
    'Tier-based jerk (acceleration rate) reduction percentages. Balances acceleration feel across different cargo multiplier levels.',
  useEffectiveRatioCap:
    'When enabled, caps the effective mass ratio to prevent extreme physics behavior at very high cargo multipliers. Recommended for multipliers above 10x.',
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
    (field: keyof FlightMechanics, value: number | boolean | Tier[]) => {
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

          {/* Base Physics Parameters */}
          <div className="pb-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Base Parameters</h3>

            <SliderInput
              label="Drag Reduction Factor"
              value={flightMechanics.dragReductionFactor}
              min={0.5}
              max={2.0}
              step={0.1}
              onChange={(value) => handleFlightMechanicsChange('dragReductionFactor', value)}
              tooltip={TOOLTIPS.dragReductionFactor}
            />

            <SliderInput
              label="Inertia Impact Factor"
              value={flightMechanics.inertiaImpactFactor}
              min={0.0}
              max={1.0}
              step={0.05}
              onChange={(value) => handleFlightMechanicsChange('inertiaImpactFactor', value)}
              tooltip={TOOLTIPS.inertiaImpactFactor}
            />

            <SliderInput
              label="Acceleration Responsiveness"
              value={flightMechanics.accelerationResponsiveness}
              min={0.5}
              max={2.0}
              step={0.1}
              onChange={(value) =>
                handleFlightMechanicsChange('accelerationResponsiveness', value)
              }
              tooltip={TOOLTIPS.accelerationResponsiveness}
            />

            <ToggleInput
              label="Use Effective Ratio Cap"
              checked={flightMechanics.useEffectiveRatioCap}
              onChange={(value) => handleFlightMechanicsChange('useEffectiveRatioCap', value)}
              tooltip={TOOLTIPS.useEffectiveRatioCap}
            />
          </div>

          {/* Tier-Based Reductions */}
          <div className="pb-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Tier-Based Reductions</h3>

            <TierEditor
              label="Drag Reduction Tiers"
              tiers={flightMechanics.dragReductionTiers}
              onChange={(value) => handleFlightMechanicsChange('dragReductionTiers', value)}
              tooltip={TOOLTIPS.dragReductionTiers}
            />

            <TierEditor
              label="Jerk Reduction Tiers"
              tiers={flightMechanics.jerkReductionTiers}
              onChange={(value) => handleFlightMechanicsChange('jerkReductionTiers', value)}
              tooltip={TOOLTIPS.jerkReductionTiers}
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
