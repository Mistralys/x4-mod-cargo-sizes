/**
 * Physics calculation hook with debouncing.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect, useRef } from 'react';
import { physicsApi } from '../services/api';
import type { PhysicsConfig, PhysicsResponse } from '../types/physics';

interface UsePhysicsCalculationResult {
  result: PhysicsResponse | null;
  loading: boolean;
  error: string | null;
  calculate: (config: PhysicsConfig) => void;
  reset: () => void;
}

/**
 * Hook for performing physics calculations with 300ms debounce.
 */
export function usePhysicsCalculation(): UsePhysicsCalculationResult {
  const [result, setResult] = useState<PhysicsResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const debounceTimerRef = useRef<number | null>(null);

  // Cleanup debounce timer on unmount
  useEffect(() => {
    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, []);

  const calculate = useCallback((config: PhysicsConfig) => {
    // Clear existing timer
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }

    // Set loading state immediately
    setLoading(true);
    setError(null);

    // Debounce the actual API call
    debounceTimerRef.current = setTimeout(async () => {
      try {
        const response = await physicsApi.calculate(config);
        setResult(response);
        setError(null);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Physics calculation failed');
        setResult(null);
      } finally {
        setLoading(false);
      }
    }, 300); // 300ms debounce as per plan requirement
  }, []);

  const reset = useCallback(() => {
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }
    setResult(null);
    setLoading(false);
    setError(null);
  }, []);

  return {
    result,
    loading,
    error,
    calculate,
    reset,
  };
}
