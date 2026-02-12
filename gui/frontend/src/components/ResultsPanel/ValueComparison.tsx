/**
 * Value comparison component for displaying original vs adjusted values with percentage change.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

interface ValueComparisonProps {
  label: string;
  original: number;
  adjusted: number;
  percent: number;
  unit?: string;
  decimals?: number;
}

export function ValueComparison({ 
  label, 
  original, 
  adjusted, 
  percent, 
  unit = '', 
  decimals = 2 
}: ValueComparisonProps) {
  // Green if value decreased (reduction is good), red if increased
  const isReduction = adjusted < original;
  const changeColor = isReduction ? 'text-green-600' : 'text-red-600';
  const changeBg = isReduction ? 'bg-green-50' : 'bg-red-50';
  const changeSign = percent >= 0 ? '+' : '';

  return (
    <div className="border-b border-gray-200 py-3 last:border-b-0">
      <div className="flex items-center justify-between">
        <div className="font-medium text-gray-700">{label}</div>
        <div className={`px-2 py-1 rounded text-sm font-semibold ${changeBg} ${changeColor}`}>
          {changeSign}{percent.toFixed(1)}%
        </div>
      </div>
      <div className="mt-2 grid grid-cols-2 gap-4 text-sm">
        <div>
          <div className="text-gray-500">Original</div>
          <div className="font-semibold text-gray-900">
            {original.toFixed(decimals)} {unit}
          </div>
        </div>
        <div>
          <div className="text-gray-500">Adjusted</div>
          <div className={`font-semibold ${changeColor}`}>
            {adjusted.toFixed(decimals)} {unit}
          </div>
        </div>
      </div>
    </div>
  );
}
