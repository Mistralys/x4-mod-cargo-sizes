/**
 * Physics overview component showing key metrics.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { MetricCard } from './MetricCard';
import type { PhysicsResponse } from '../../types/physics';

interface PhysicsOverviewProps {
  data: PhysicsResponse;
}

export function PhysicsOverview({ data }: PhysicsOverviewProps) {
  return (
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
  );
}
