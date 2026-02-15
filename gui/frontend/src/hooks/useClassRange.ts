/**
 * Class-range calculation hook with debouncing.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect, useRef } from 'react';
import { classRangeApi } from '../services/api';
import type { ClassRangeRequest, ClassRangeResponse } from '../types/physics';

export interface UseClassRangeResult {
  result: ClassRangeResponse | null;
  loading: boolean;
  error: string | null;
  calculate: (request: ClassRangeRequest) => void;
  reset: () => void;
}

/**
 * Hook for performing class-range calculations with 500ms debounce.
 * Heavier computation than single-ship, so longer debounce than usePhysicsCalculation (300ms).
 */
export function useClassRange(): UseClassRangeResult {
  const [result, setResult] = useState<ClassRangeResponse | null>(null);
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

  const calculate = useCallback((request: ClassRangeRequest) => {
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
        const response = await classRangeApi.calculate(request);
        setResult(response);
        setError(null);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Class-range calculation failed');
        setResult(null);
      } finally {
        setLoading(false);
      }
    }, 500); // 500ms debounce for heavier class-range computation
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
