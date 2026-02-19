/**
 * Physics overview component showing key metrics.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { MetricCard } from './MetricCard';
import { AbsoluteMetricCard } from './AbsoluteMetricCard';
import { getSpeedContext, getAccelerationContext } from '../../utils/metricContext';
import type { PhysicsResponse } from '../../types/physics';

interface PhysicsOverviewProps {
  data: PhysicsResponse;
  shipSize?: string;
}

export function PhysicsOverview({ data, shipSize = 'M' }: PhysicsOverviewProps) {
  const hasAbsoluteMetrics = data.topSpeed || data.acceleration;

  return (
    <div className="space-y-4">
      {/* Base metrics grid */}
      <div className="grid grid-cols-2 gap-4">
        <MetricCard
          label="Mass Ratio"
          value={data.massRatio.toFixed(2)}
          unit="x"
          variant="neutral"
        />
        <MetricCard
          label="Effective Ratio"
          value={data.effectiveRatio.toFixed(2)}
          unit="x"
          variant={data.effectiveRatio < data.massRatio ? 'positive' : 'neutral'}
        />
        <MetricCard
          label="Original Cargo"
          value={data.originalCargo.toLocaleString()}
          unit="m³"
          variant="neutral"
        />
        <MetricCard
          label="Adjusted Cargo"
          value={data.adjustedCargo.toLocaleString()}
          unit="m³"
          variant="positive"
        />
        <MetricCard
          label="Original Full Mass"
          value={data.originalFullMass.toLocaleString()}
          unit="kg"
          variant="neutral"
        />
        <MetricCard
          label="Adjusted Full Mass"
          value={data.adjustedFullMass.toLocaleString()}
          unit="kg"
          variant="negative"
        />
      </div>

      {/* Absolute metrics (when engine selected) */}
      {hasAbsoluteMetrics && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
          {data.topSpeed && (
            <AbsoluteMetricCard
              label="Top Speed"
              originalValue={data.topSpeed.original}
              adjustedValue={data.topSpeed.adjusted}
              unit=" m/s"
              higherIsBetter={true}
              contextPhrase={getSpeedContext(data.topSpeed.adjusted, shipSize)}
            />
          )}
          {data.acceleration && (
            <AbsoluteMetricCard
              label="Acceleration"
              originalValue={data.acceleration.original}
              adjustedValue={data.acceleration.adjusted}
              unit=" m/s²"
              higherIsBetter={true}
              contextPhrase={getAccelerationContext(data.acceleration.adjusted, shipSize)}
            />
          )}
        </div>
      )}
    </div>
  );
}
