/**
 * Configuration hook for loading, saving, and resetting config.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect } from 'react';
import { configApi } from '../services/api';
import type { BuildConfig, ValidationResult } from '../types/config';

interface UseConfigResult {
  config: BuildConfig | null;
  loading: boolean;
  error: string | null;
  validationResult: ValidationResult | null;
  loadConfig: () => Promise<void>;
  saveConfig: (config: BuildConfig) => Promise<boolean>;
  validateConfig: (config: BuildConfig) => Promise<ValidationResult>;
  resetToDefault: () => void;
}

/**
 * Default configuration values (fallback if API fails).
 */
const DEFAULT_CONFIG: BuildConfig = {
  'cargo-multipliers': [2, 4, 6, 8, 10],
  'flight-mechanics': {
    dragReductionFactor: 1.0,
    steeringIncreaseFactor: 1.0,
    inertiaIncreaseFactor: 1.0,
    dragReductionTiers: [
      { maxMultiplier: 2.0, reductionPercent: 0.1 },
      { maxMultiplier: 4.0, reductionPercent: 0.3 },
      { maxMultiplier: 8.0, reductionPercent: 0.5 },
      { maxMultiplier: 999, reductionPercent: 0.7 },
    ],
    jerkReductionTiers: [
      { maxMultiplier: 2.0, reductionPercent: 0.05 },
      { maxMultiplier: 4.0, reductionPercent: 0.15 },
      { maxMultiplier: 8.0, reductionPercent: 0.25 },
      { maxMultiplier: 999, reductionPercent: 0.35 },
    ],
    inertiaImpactFactor: 0.5,
    useEffectiveRatioCap: true,
    accelerationResponsiveness: 0.5,
  },
};

/**
 * Hook for managing build configuration.
 */
export function useConfig(): UseConfigResult {
  const [config, setConfig] = useState<BuildConfig | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationResult, setValidationResult] = useState<ValidationResult | null>(null);

  const loadConfig = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const loadedConfig = await configApi.get();
      setConfig(loadedConfig);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load configuration');
      // Fallback to default config
      setConfig(DEFAULT_CONFIG);
    } finally {
      setLoading(false);
    }
  }, []);

  const saveConfig = useCallback(async (newConfig: BuildConfig): Promise<boolean> => {
    setLoading(true);
    setError(null);

    try {
      await configApi.update(newConfig);
      setConfig(newConfig);
      return true;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save configuration');
      return false;
    } finally {
      setLoading(false);
    }
  }, []);

  const validateConfig = useCallback(async (configToValidate: BuildConfig): Promise<ValidationResult> => {
    setLoading(true);
    setError(null);

    try {
      const result = await configApi.validate(configToValidate);
      setValidationResult(result);
      return result;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to validate configuration');
      const fallbackResult = { valid: false, errors: ['Validation service unavailable'] };
      setValidationResult(fallbackResult);
      return fallbackResult;
    } finally {
      setLoading(false);
    }
  }, []);

  const resetToDefault = useCallback(() => {
    setConfig(DEFAULT_CONFIG);
    setValidationResult(null);
    setError(null);
  }, []);

  // Load config on mount
  useEffect(() => {
    loadConfig();
  }, [loadConfig]);

  return {
    config,
    loading,
    error,
    validationResult,
    loadConfig,
    saveConfig,
    validateConfig,
    resetToDefault,
  };
}
