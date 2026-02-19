/**
 * Diagnostics panel showing configuration impact on physics calculations.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { PhysicsConfig } from '../../types/physics';
import { Card } from '../UI/Card';

interface DiagnosticsPanelProps {
  config: PhysicsConfig;
}

export function DiagnosticsPanel({ config }: DiagnosticsPanelProps) {
  return (
    <Card title="Diagnostics">
      <div className="space-y-4">
        {/* Configuration Impact */}
        <div>
          <div className="text-sm font-medium text-gray-700 mb-2">Configuration Impact</div>
          <div className="space-y-2 text-xs">
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Cargo Multiplier</span>
              <span className="font-semibold text-gray-900">{config.cargoMultiplier}x</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Acceleration Responsiveness</span>
              <span className="font-semibold text-gray-900">{config.accelerationResponsiveness.toFixed(2)}</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Original Cargo</span>
              <span className="font-semibold text-gray-900">{config.originalCargo.toLocaleString()} m3</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Adjusted Cargo</span>
              <span className="font-semibold text-gray-900">{config.adjustedCargo.toLocaleString()} m3</span>
            </div>
          </div>
        </div>
      </div>
    </Card>
  );
}