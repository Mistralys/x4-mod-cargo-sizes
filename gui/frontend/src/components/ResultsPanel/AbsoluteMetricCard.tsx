/**
 * AbsoluteMetricCard - Displays a single absolute metric with original → adjusted comparison.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

interface AbsoluteMetricCardProps {
  label: string;
  originalValue: number;
  adjustedValue: number;
  unit: string;
  contextPhrase?: string;
  /**
   * When true, an increase is shown as green (good) and a decrease as red (bad).
   * Default (false): increase = red, decrease = green (suitable for mass/drag metrics).
   */
  higherIsBetter?: boolean;
}

/**
 * Shows absolute metric with original → adjusted comparison and delta percentage.
 */
export function AbsoluteMetricCard({
  label,
  originalValue,
  adjustedValue,
  unit,
  contextPhrase,
  higherIsBetter = false,
}: AbsoluteMetricCardProps) {
  // Calculate delta percentage
  const delta = originalValue !== 0 ? ((adjustedValue - originalValue) / originalValue) * 100 : 0;
  const deltaSign = delta > 0 ? '+' : '';

  // Color logic: green = good, red = bad.
  // higherIsBetter=true: increase (delta>0) is green, decrease is red (e.g. speed, acceleration)
  // higherIsBetter=false: increase is red, decrease is green (e.g. mass, drag)
  const isPositiveChange = higherIsBetter ? delta > 0 : delta < 0;
  const isNegativeChange = higherIsBetter ? delta < 0 : delta > 0;
  const deltaColor = isPositiveChange ? 'text-green-600' : isNegativeChange ? 'text-red-600' : 'text-gray-600';

  const formatValue = (value: number): string => {
    return `${value.toFixed(1)}${unit}`;
  };

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-3">
        <h4 className="text-sm font-semibold text-gray-800">{label}</h4>
        <span className={`text-sm font-medium ${deltaColor}`}>
          {deltaSign}{delta.toFixed(1)}%
        </span>
      </div>

      {/* Values */}
      <div className="flex items-center gap-3 mb-2">
        <div className="flex-1">
          <div className="text-xs text-gray-500 mb-1">Original</div>
          <div className="text-lg font-bold text-gray-900">{formatValue(originalValue)}</div>
        </div>

        <svg
          className="w-5 h-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>

        <div className="flex-1">
          <div className="text-xs text-gray-500 mb-1">Adjusted</div>
          <div className="text-lg font-bold text-gray-900">{formatValue(adjustedValue)}</div>
        </div>
      </div>

      {/* Context phrase */}
      {contextPhrase && (
        <p className="text-xs text-gray-600 italic mt-2">{contextPhrase}</p>
      )}
    </div>
  );
}
