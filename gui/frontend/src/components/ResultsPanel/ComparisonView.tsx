/**
 * Comparison view component showing engine performance data.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { PhysicsResponse, EngineDef } from '../../types/physics';
import { Card } from '../UI/Card';
import { EnginePerformanceDisplay } from './EnginePerformanceDisplay';

interface ComparisonViewProps {
  data: PhysicsResponse;
  engine?: EngineDef | null;
}

export function ComparisonView({ data, engine }: ComparisonViewProps) {
  if (!data.enginePerformance) {
    return null;
  }

  return (
    <Card title="Engine Performance">
      <EnginePerformanceDisplay enginePerformance={data.enginePerformance} engine={engine} />
    </Card>
  );
}