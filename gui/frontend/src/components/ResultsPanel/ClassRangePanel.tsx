/**
 * ClassRangePanel - Container for class-wide physics impact visualization.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ClassRangeResponse } from '../../types/physics';
import { RangeBar } from '../UI/RangeBar';
import { WorstCaseCard } from './WorstCaseCard';
import { Card } from '../UI/Card';
import { Spinner } from '../UI/Spinner';

interface ClassRangePanelProps {
  data: ClassRangeResponse | null;
  loading: boolean;
  error: string | null;
  engineSelected: boolean;
}

/**
 * Displays class-wide min/max/median ranges for all physics metrics.
 */
export function ClassRangePanel({ data, loading, error, engineSelected }: ClassRangePanelProps) {
  // Loading state
  if (loading) {
    return (
      <Card title="Class-Wide Impact">
        <div className="flex flex-col items-center justify-center py-12">
          <Spinner size="lg" />
          <p className="mt-4 text-sm text-gray-600">Analyzing ship class...</p>
        </div>
      </Card>
    );
  }

  // Error state
  if (error) {
    return (
      <Card title="Class-Wide Impact">
        <div className="flex flex-col items-center justify-center py-12">
          <svg
            className="w-12 h-12 text-red-300 mb-3"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <p className="text-sm font-medium text-red-600">Analysis Error</p>
          <p className="mt-2 text-xs text-gray-500">{error}</p>
        </div>
      </Card>
    );
  }

  // No data state
  if (!data) {
    return (
      <Card title="Class-Wide Impact">
        <div className="flex flex-col items-center justify-center py-12 text-gray-500">
          <svg
            className="w-12 h-12 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
          </svg>
          <p className="mt-4 text-sm font-medium">No Class Data</p>
          <p className="mt-2 text-xs text-gray-400">
            Select a ship type and adjust settings to see class-wide impact
          </p>
        </div>
      </Card>
    );
  }

  // Data display
  return (
    <Card title={`Class Impact: ${data.shipCount} ships analyzed `}>
      <div className="space-y-6">
        {/* Always-shown metrics */}
        <div>
          <h4 className="text-sm font-semibold text-gray-700 mb-3">Mass & Physics Changes</h4>
          <div className="space-y-1">
            {data.metrics.massRatio && (
              <RangeBar
                min={data.metrics.massRatio.min}
                max={data.metrics.massRatio.max}
                median={data.metrics.massRatio.median}
                unit={data.metrics.massRatio.unit}
                label={data.metrics.massRatio.label}
              />
            )}
            {data.metrics.dragChange && (
              <RangeBar
                min={data.metrics.dragChange.min}
                max={data.metrics.dragChange.max}
                median={data.metrics.dragChange.median}
                unit={data.metrics.dragChange.unit}
                label={data.metrics.dragChange.label}
              />
            )}
            {data.metrics.inertiaChange && (
              <RangeBar
                min={data.metrics.inertiaChange.min}
                max={data.metrics.inertiaChange.max}
                median={data.metrics.inertiaChange.median}
                unit={data.metrics.inertiaChange.unit}
                label={data.metrics.inertiaChange.label}
              />
            )}
            {data.metrics.jerkChange && (
              <RangeBar
                min={data.metrics.jerkChange.min}
                max={data.metrics.jerkChange.max}
                median={data.metrics.jerkChange.median}
                unit={data.metrics.jerkChange.unit}
                label={data.metrics.jerkChange.label}
              />
            )}
          </div>
        </div>

        {/* Engine-dependent metrics */}
        {engineSelected && (data.metrics.topSpeed || data.metrics.acceleration) && (
          <div>
            <h4 className="text-sm font-semibold text-gray-700 mb-3">Performance Metrics</h4>
            <div className="space-y-1">
              {data.metrics.topSpeed && (
                <RangeBar
                  min={data.metrics.topSpeed.min}
                  max={data.metrics.topSpeed.max}
                  median={data.metrics.topSpeed.median}
                  unit={data.metrics.topSpeed.unit}
                  label={data.metrics.topSpeed.label}
                />
              )}
              {data.metrics.acceleration && (
                <RangeBar
                  min={data.metrics.acceleration.min}
                  max={data.metrics.acceleration.max}
                  median={data.metrics.acceleration.median}
                  unit={data.metrics.acceleration.unit}
                  label={data.metrics.acceleration.label}
                />
              )}
            </div>
          </div>
        )}

        {/* Worst/Best case identification */}
        <div>
          <h4 className="text-sm font-semibold text-gray-700 mb-3">Edge Cases</h4>
          <WorstCaseCard
            worstCase={data.worstCase}
            bestCase={data.bestCase}
            engineSelected={engineSelected}
          />
        </div>
      </div>
    </Card>
  );
}
