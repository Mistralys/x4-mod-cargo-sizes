/**
 * Diagnostics panel showing active tier and configuration impact.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { PhysicsConfig } from '../../types/physics';
import { Card } from '../UI/Card';

interface DiagnosticsPanelProps {
  activeTier: string;
  config: PhysicsConfig;
}

export function DiagnosticsPanel({ activeTier, config }: DiagnosticsPanelProps) {
  // Parse active tier to find matching tier info
  const dragTier = config.dragReductionTiers.find(
    (t) => activeTier.includes(`up to ${t.maxMultiplier}x`)
  );
  const jerkTier = config.jerkReductionTiers.find(
    (t) => activeTier.includes(`up to ${t.maxMultiplier}x`)
  );

  return (
    <Card title="Diagnostics">
      <div className="space-y-4">
        {/* Active Tier */}
        <div>
          <div className="text-sm font-medium text-gray-700 mb-2">Active Tier</div>
          <div className="p-3 bg-indigo-50 border-2 border-indigo-300 rounded-lg">
            <div className="flex items-center">
              <svg
                className="w-5 h-5 text-indigo-600 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clipRule="evenodd"
                />
              </svg>
              <span className="text-sm font-semibold text-indigo-900">{activeTier}</span>
            </div>
            {dragTier && (
              <div className="mt-2 text-xs text-indigo-700">
                Drag Reduction: {(dragTier.reductionPercent * 100).toFixed(0)}% at this tier
              </div>
            )}
            {jerkTier && (
              <div className="text-xs text-indigo-700">
                Jerk Reduction: {(jerkTier.reductionPercent * 100).toFixed(0)}% at this tier
              </div>
            )}
          </div>
        </div>

        {/* Configuration Impact */}
        <div>
          <div className="text-sm font-medium text-gray-700 mb-2">Configuration Impact</div>
          <div className="space-y-2 text-xs">
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Cargo Multiplier</span>
              <span className="font-semibold text-gray-900">{config.cargoMultiplier}x</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Drag Reduction Factor</span>
              <span className="font-semibold text-gray-900">{config.dragReductionFactor.toFixed(2)}</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Inertia Impact</span>
              <span className="font-semibold text-gray-900">{(config.inertiaImpactFactor * 100).toFixed(0)}%</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Acceleration Responsiveness</span>
              <span className="font-semibold text-gray-900">{config.accelerationResponsiveness.toFixed(2)}</span>
            </div>
            <div className="flex justify-between p-2 bg-gray-50 rounded">
              <span className="text-gray-600">Use Effective Ratio Cap</span>
              <span className="font-semibold text-gray-900">{config.useEffectiveRatioCap ? 'Yes' : 'No'}</span>
            </div>
          </div>
        </div>

        {/* Tier Summary */}
        <div>
          <div className="text-sm font-medium text-gray-700 mb-2">Tier Summary</div>
          <div className="space-y-2">
            <div>
              <div className="text-xs text-gray-600 mb-1">Drag Reduction Tiers:</div>
              <div className="space-y-1">
                {config.dragReductionTiers.map((tier, idx) => (
                  <div
                    key={idx}
                    className="flex justify-between text-xs p-2 bg-blue-50 rounded"
                  >
                    <span className="text-blue-700">Up to {tier.maxMultiplier}x</span>
                    <span className="font-semibold text-blue-900">
                      {(tier.reductionPercent * 100).toFixed(0)}% reduction
                    </span>
                  </div>
                ))}
              </div>
            </div>
            <div>
              <div className="text-xs text-gray-600 mb-1">Jerk Reduction Tiers:</div>
              <div className="space-y-1">
                {config.jerkReductionTiers.map((tier, idx) => (
                  <div
                    key={idx}
                    className="flex justify-between text-xs p-2 bg-purple-50 rounded"
                  >
                    <span className="text-purple-700">Up to {tier.maxMultiplier}x</span>
                    <span className="font-semibold text-purple-900">
                      {(tier.reductionPercent * 100).toFixed(0)}% reduction
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </Card>
  );
}
