/**
 * Comparison view component with tabs for different physics aspects.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { PhysicsResponse, EngineDef } from '../../types/physics';
import type { Tab } from '../UI/Tabs';
import { Tabs } from '../UI/Tabs';
import { Card } from '../UI/Card';
import { ValueComparison } from './ValueComparison';
import { EnginePerformanceDisplay } from './EnginePerformanceDisplay';

interface ComparisonViewProps {
  data: PhysicsResponse;
  engine?: EngineDef | null;
}

export function ComparisonView({ data, engine }: ComparisonViewProps) {
  const tabs: Tab[] = [
    {
      id: 'engine',
      label: 'Engine',
      content: (
        <div>
          <EnginePerformanceDisplay enginePerformance={data.enginePerformance} engine={engine} />
        </div>
      ),
    },
    {
      id: 'drag',
      label: 'Drag',
      content: (
        <div className="space-y-1">
          <ValueComparison
            label="Forward Drag"
            original={data.dragOriginal.forward}
            adjusted={data.dragAdjusted.forward}
            percent={data.dragAdjusted.forwardPercent}
          />
          <ValueComparison
            label="Reverse Drag"
            original={data.dragOriginal.reverse}
            adjusted={data.dragAdjusted.reverse}
            percent={data.dragAdjusted.reversePercent}
          />
          <ValueComparison
            label="Horizontal Drag"
            original={data.dragOriginal.horizontal}
            adjusted={data.dragAdjusted.horizontal}
            percent={data.dragAdjusted.horizontalPercent}
          />
          <ValueComparison
            label="Vertical Drag"
            original={data.dragOriginal.vertical}
            adjusted={data.dragAdjusted.vertical}
            percent={data.dragAdjusted.verticalPercent}
          />
          <ValueComparison
            label="Pitch Drag"
            original={data.dragOriginal.pitch}
            adjusted={data.dragAdjusted.pitch}
            percent={data.dragAdjusted.pitchPercent}
          />
          <ValueComparison
            label="Yaw Drag"
            original={data.dragOriginal.yaw}
            adjusted={data.dragAdjusted.yaw}
            percent={data.dragAdjusted.yawPercent}
          />
          <ValueComparison
            label="Roll Drag"
            original={data.dragOriginal.roll}
            adjusted={data.dragAdjusted.roll}
            percent={data.dragAdjusted.rollPercent}
          />
        </div>
      ),
    },
    {
      id: 'inertia',
      label: 'Inertia',
      content: (
        <div className="space-y-1">
          <ValueComparison
            label="Pitch Inertia"
            original={data.inertiaOriginal.pitch}
            adjusted={data.inertiaAdjusted.pitch}
            percent={data.inertiaAdjusted.pitchPercent}
          />
          <ValueComparison
            label="Yaw Inertia"
            original={data.inertiaOriginal.yaw}
            adjusted={data.inertiaAdjusted.yaw}
            percent={data.inertiaAdjusted.yawPercent}
          />
          <ValueComparison
            label="Roll Inertia"
            original={data.inertiaOriginal.roll}
            adjusted={data.inertiaAdjusted.roll}
            percent={data.inertiaAdjusted.rollPercent}
          />
        </div>
      ),
    },
    {
      id: 'jerk',
      label: 'Jerk',
      content: (
        <div className="space-y-4">
          <div>
            <div className="text-sm font-semibold text-gray-700 mb-2">Forward Jerk</div>
            <div className="space-y-1">
              <ValueComparison
                label="Acceleration"
                original={data.jerkOriginal.forward.accel}
                adjusted={data.jerkAdjusted.forward.accel}
                percent={data.jerkAdjusted.forward.accelPercent}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerkOriginal.forward.decel}
                adjusted={data.jerkAdjusted.forward.decel}
                percent={data.jerkAdjusted.forward.decelPercent}
                unit="m/s³"
                decimals={1}
              />
            </div>
          </div>

          <div>
            <div className="text-sm font-semibold text-gray-700 mb-2">Boost Jerk</div>
            <div className="space-y-1">
              <ValueComparison
                label="Acceleration"
                original={data.jerkOriginal.boost.accel}
                adjusted={data.jerkAdjusted.boost.accel}
                percent={data.jerkAdjusted.boost.accelPercent}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerkOriginal.boost.decel}
                adjusted={data.jerkAdjusted.boost.decel}
                percent={data.jerkAdjusted.boost.decelPercent}
                unit="m/s³"
                decimals={1}
              />
            </div>
          </div>

          <div>
            <div className="text-sm font-semibold text-gray-700 mb-2">Travel Jerk</div>
            <div className="space-y-1">
              <ValueComparison
                label="Acceleration"
                original={data.jerkOriginal.travel.accel}
                adjusted={data.jerkAdjusted.travel.accel}
                percent={data.jerkAdjusted.travel.accelPercent}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerkOriginal.travel.decel}
                adjusted={data.jerkAdjusted.travel.decel}
                percent={data.jerkAdjusted.travel.decelPercent}
                unit="m/s³"
                decimals={1}
              />
            </div>
          </div>
        </div>
      ),
    },
  ];

  return (
    <Card title="Physics Comparisons">
      <Tabs tabs={tabs} defaultTab="drag" />
    </Card>
  );
}
