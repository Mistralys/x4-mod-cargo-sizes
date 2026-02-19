/**
 * Engine performance display component showing TWR and acceleration metrics.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { EnginePerformance, EngineDef } from '../../types/physics';
import { ValueComparison } from './ValueComparison';
import { Card } from '../UI/Card';

interface EnginePerformanceDisplayProps {
  enginePerformance: EnginePerformance | null | undefined;
  engine?: EngineDef | null;
}

export function EnginePerformanceDisplay({ enginePerformance, engine }: EnginePerformanceDisplayProps) {
  if (!enginePerformance) {
    return (
      <Card title="Engine Performance">
        <div className="flex flex-col items-center justify-center py-8 text-gray-500">
          <svg
            className="w-12 h-12 text-gray-300 mb-3"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M13 10V3L4 14h7v7l9-11h-7z"
            />
          </svg>
          <p className="text-sm font-medium">Select an engine to see performance metrics</p>
          <p className="text-xs text-gray-400 mt-1">Engine performance calculations require an engine selection</p>
        </div>
      </Card>
    );
  }

  const hasTopSpeedData = enginePerformance.topSpeed !== undefined && enginePerformance.topSpeed !== null;

  return (
    <Card title="Engine Performance">
      {engine && (
        <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
          <div className="text-sm font-semibold text-blue-900">Engine: {engine.name}</div>
          <div className="text-xs text-blue-700 mt-1 grid grid-cols-2 gap-2">
            <div>Forward: {engine.thrustForward.toLocaleString()} kN</div>
            {engine.thrustReverse !== undefined && <div>Reverse: {engine.thrustReverse.toLocaleString()} kN</div>}
            {engine.thrustBoost !== undefined && <div>Boost: {engine.thrustBoost.toLocaleString()} kN</div>}
            {engine.thrustTravel !== undefined && <div>Travel: {engine.thrustTravel.toLocaleString()} kN</div>}
          </div>
        </div>
      )}

      <div className="space-y-4">
        {/* TWR and Acceleration */}
        <div className="space-y-1">
          <ValueComparison
            label="Thrust-to-Weight Ratio (TWR)"
            original={enginePerformance.originalTWR}
            adjusted={enginePerformance.adjustedTWR}
            percent={enginePerformance.twrReductionPercent}
            decimals={3}
            higherIsBetter={true}
          />
          <ValueComparison
            label="Estimated Acceleration"
            original={enginePerformance.originalAcceleration}
            adjusted={enginePerformance.adjustedAcceleration}
            percent={enginePerformance.twrReductionPercent}
            unit="m/s²"
            decimals={2}
            higherIsBetter={true}
          />
        </div>

        {/* Top Speeds (when available) */}
        {hasTopSpeedData && (
          <div className="pt-4 border-t border-gray-200">
            <h4 className="text-sm font-semibold text-gray-700 mb-2">Top Speeds by Flight Mode</h4>
            <div className="space-y-1">
              {enginePerformance.topSpeed != null && enginePerformance.topSpeedAdjusted != null && (
                <ValueComparison
                  label="Normal Flight"
                  original={enginePerformance.topSpeed}
                  adjusted={enginePerformance.topSpeedAdjusted}
                  percent={((enginePerformance.topSpeedAdjusted - enginePerformance.topSpeed) / enginePerformance.topSpeed) * 100}
                  unit="m/s"
                  decimals={1}
                  higherIsBetter={true}
                />
              )}
              {enginePerformance.topSpeedReverse != null && (
                <div className="flex justify-between py-1 px-2 bg-gray-50 rounded text-sm">
                  <span className="text-gray-600">Reverse</span>
                  <span className="font-medium text-gray-900">
                    {enginePerformance.topSpeedReverse.toFixed(1)} m/s
                  </span>
                </div>
              )}
              {enginePerformance.topSpeedBoost != null && (
                <div className="flex justify-between py-1 px-2 bg-gray-50 rounded text-sm">
                  <span className="text-gray-600">Boost</span>
                  <span className="font-medium text-gray-900">
                    {enginePerformance.topSpeedBoost.toFixed(1)} m/s
                  </span>
                </div>
              )}
              {enginePerformance.topSpeedTravel != null && (
                <div className="flex justify-between py-1 px-2 bg-gray-50 rounded text-sm">
                  <span className="text-gray-600">Travel</span>
                  <span className="font-medium text-gray-900">
                    {enginePerformance.topSpeedTravel.toFixed(1)} m/s
                  </span>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
        <div className="text-xs text-yellow-800">
          <strong>Note:</strong> TWR reduction of{' '}
          <span className="font-semibold">{Math.abs(enginePerformance.twrReductionPercent).toFixed(1)}%</span> means the ship
          will accelerate more slowly when fully loaded. Adjust physics parameters to compensate.
        </div>
      </div>
    </Card>
  );
}
