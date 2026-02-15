/**
 * RangeBar - Horizontal bar showing min—median—max with labeled markers.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

interface RangeBarProps {
  min: number;
  max: number;
  median: number;
  unit: string;
  label: string;
  highlightValue?: number;
}

/**
 * Pure CSS/Tailwind horizontal range visualization.
 * Shows min, median, max markers along a gradient bar.
 */
export function RangeBar({ min, max, median, unit, label, highlightValue }: RangeBarProps) {
  // Calculate positions as percentages
  const range = max - min;
  const medianPercent = range === 0 ? 50 : ((median - min) / range) * 100;
  const highlightPercent = highlightValue !== undefined && range > 0
    ? ((highlightValue - min) / range) * 100
    : null;

  const formatValue = (value: number): string => {
    return `${value.toFixed(1)}${unit}`;
  };

  return (
    <div className="py-4">
      {/* Label */}
      <div className="flex justify-between items-center mb-2">
        <span className="text-sm font-medium text-gray-700">{label}</span>
        <span className="text-xs text-gray-500">
          {formatValue(min)} — {formatValue(max)}
        </span>
      </div>

      {/* Range bar container */}
      <div className="relative h-8 bg-gradient-to-r from-green-200 via-yellow-200 to-red-200 rounded-full">
        {/* Min marker */}
        <div className="absolute left-0 top-0 bottom-0 flex items-center pl-2">
          <div className="w-1 h-4 bg-green-600 rounded-full" />
        </div>

        {/* Median marker */}
        <div
          className="absolute top-0 bottom-0 flex items-center -translate-x-1/2"
          style={{ left: `${medianPercent}%` }}
        >
          <div className="flex flex-col items-center">
            <div className="w-1 h-6 bg-gray-800 rounded-full" />
            <span className="text-xs font-medium text-gray-800 mt-1">
              {formatValue(median)}
            </span>
          </div>
        </div>

        {/* Max marker */}
        <div className="absolute right-0 top-0 bottom-0 flex items-center pr-2">
          <div className="w-1 h-4 bg-red-600 rounded-full" />
        </div>

        {/* Highlight marker (optional - for selected ship's value) */}
        {highlightPercent !== null && (
          <div
            className="absolute top-0 bottom-0 flex items-center -translate-x-1/2"
            style={{ left: `${highlightPercent}%` }}
          >
            <div className="w-2 h-8 bg-blue-600 rounded-full border-2 border-white shadow-md" />
          </div>
        )}
      </div>
    </div>
  );
}
