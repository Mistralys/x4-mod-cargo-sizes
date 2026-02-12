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
            original={data.drag.original.forward}
            adjusted={data.drag.adjusted.forward}
            percent={data.drag.percentChange.forward}
          />
          <ValueComparison
            label="Reverse Drag"
            original={data.drag.original.reverse}
            adjusted={data.drag.adjusted.reverse}
            percent={data.drag.percentChange.reverse}
          />
          <ValueComparison
            label="Horizontal Drag"
            original={data.drag.original.horizontal}
            adjusted={data.drag.adjusted.horizontal}
            percent={data.drag.percentChange.horizontal}
          />
          <ValueComparison
            label="Vertical Drag"
            original={data.drag.original.vertical}
            adjusted={data.drag.adjusted.vertical}
            percent={data.drag.percentChange.vertical}
          />
          <ValueComparison
            label="Pitch Drag"
            original={data.drag.original.pitch}
            adjusted={data.drag.adjusted.pitch}
            percent={data.drag.percentChange.pitch}
          />
          <ValueComparison
            label="Yaw Drag"
            original={data.drag.original.yaw}
            adjusted={data.drag.adjusted.yaw}
            percent={data.drag.percentChange.yaw}
          />
          <ValueComparison
            label="Roll Drag"
            original={data.drag.original.roll}
            adjusted={data.drag.adjusted.roll}
            percent={data.drag.percentChange.roll}
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
            original={data.inertia.original.pitch}
            adjusted={data.inertia.adjusted.pitch}
            percent={data.inertia.percentChange.pitch}
          />
          <ValueComparison
            label="Yaw Inertia"
            original={data.inertia.original.yaw}
            adjusted={data.inertia.adjusted.yaw}
            percent={data.inertia.percentChange.yaw}
          />
          <ValueComparison
            label="Roll Inertia"
            original={data.inertia.original.roll}
            adjusted={data.inertia.adjusted.roll}
            percent={data.inertia.percentChange.roll}
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
                original={data.jerk.original.forward.accel}
                adjusted={data.jerk.adjusted.forward.accel}
                percent={data.jerk.percentChange.forward.accel}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerk.original.forward.decel}
                adjusted={data.jerk.adjusted.forward.decel}
                percent={data.jerk.percentChange.forward.decel}
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
                original={data.jerk.original.boost.accel}
                adjusted={data.jerk.adjusted.boost.accel}
                percent={data.jerk.percentChange.boost.accel}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerk.original.boost.decel}
                adjusted={data.jerk.adjusted.boost.decel}
                percent={data.jerk.percentChange.boost.decel}
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
                original={data.jerk.original.travel.accel}
                adjusted={data.jerk.adjusted.travel.accel}
                percent={data.jerk.percentChange.travel.accel}
                unit="m/s³"
                decimals={1}
              />
              <ValueComparison
                label="Deceleration"
                original={data.jerk.original.travel.decel}
                adjusted={data.jerk.adjusted.travel.decel}
                percent={data.jerk.percentChange.travel.decel}
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
