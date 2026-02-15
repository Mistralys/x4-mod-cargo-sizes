/**
 * WorstCaseCard - Displays worst-case and best-case ship identification.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ShipMetricSummary } from '../../types/physics';

interface WorstCaseCardProps {
  worstCase: ShipMetricSummary;
  bestCase: ShipMetricSummary;
  engineSelected: boolean;
}

/**
 * Shows worst-case (warning) and best-case (success) ships with key metrics.
 */
export function WorstCaseCard({ worstCase, bestCase, engineSelected }: WorstCaseCardProps) {
  const renderShipSummary = (ship: ShipMetricSummary, type: 'worst' | 'best') => {
    const bgColor = type === 'worst' ? 'bg-red-50' : 'bg-green-50';
    const borderColor = type === 'worst' ? 'border-red-200' : 'border-green-200';
    const textColor = type === 'worst' ? 'text-red-800' : 'text-green-800';
    const badgeColor = type === 'worst' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';

    return (
      <div className={`flex-1 ${bgColor} border ${borderColor} rounded-lg p-4`}>
        {/* Header */}
        <div className="flex items-center justify-between mb-3">
          <span className={`text-xs font-semibold uppercase ${textColor}`}>
            {type === 'worst' ? 'Worst Case' : 'Best Case'}
          </span>
          <span className={`text-xs font-medium px-2 py-1 rounded ${badgeColor}`}>
            {ship.size.toUpperCase()}
          </span>
        </div>

        {/* Ship name */}
        <h4 className="text-sm font-bold text-gray-900 mb-2">{ship.shipName}</h4>

        {/* Metrics */}
        <div className="space-y-2 text-xs">
          <div className="flex justify-between">
            <span className="text-gray-600">Mass Ratio:</span>
            <span className="font-semibold text-gray-900">{ship.massRatio.toFixed(2)}x</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">Drag Change:</span>
            <span className="font-semibold text-gray-900">
              {ship.dragChangePercent.toFixed(1)}%
            </span>
          </div>

          {/* Top speed (only if engine selected) */}
          {engineSelected && ship.topSpeed && (
            <div className="flex justify-between pt-2 border-t border-gray-200">
              <span className="text-gray-600">Top Speed:</span>
              <span className="font-semibold text-gray-900">
                {ship.topSpeed.original.toFixed(0)} → {ship.topSpeed.adjusted.toFixed(0)} m/s
              </span>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="flex gap-4">
      {renderShipSummary(worstCase, 'worst')}
      {renderShipSummary(bestCase, 'best')}
    </div>
  );
}
