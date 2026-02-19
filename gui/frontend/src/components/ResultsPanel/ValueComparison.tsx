/**
 * Value comparison component for displaying original vs adjusted values with percentage change.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

interface ValueComparisonProps {
  label: string;
  original: number | undefined;
  adjusted: number | undefined;
  percent: number | undefined;
  unit?: string;
  decimals?: number;
  /**
   * When true, an increase is shown as green (good) and a decrease as red (bad).
   * Default (false): decrease = green, increase = red (suitable for drag/mass metrics).
   */
  higherIsBetter?: boolean;
}

export function ValueComparison({ 
  label, 
  original, 
  adjusted, 
  percent, 
  unit = '', 
  decimals = 2,
  higherIsBetter = false,
}: ValueComparisonProps) {
  // Handle undefined values
  const origValue = original ?? 0;
  const adjValue = adjusted ?? 0;
  
  // Color logic: green = good, red = bad.
  // higherIsBetter=true: increase is green (e.g. speed, acceleration, TWR)
  // higherIsBetter=false: decrease is green (e.g. drag, mass)
  const isIncrease = adjValue > origValue;
  const isGood = higherIsBetter ? isIncrease : !isIncrease;
  const changeColor = adjValue === origValue ? 'text-gray-600' : isGood ? 'text-green-600' : 'text-red-600';
  const changeBg = adjValue === origValue ? 'bg-gray-50' : isGood ? 'bg-green-50' : 'bg-red-50';
  const changeSign = (percent ?? 0) >= 0 ? '+' : '';

  return (
    <div className="border-b border-gray-200 py-3 last:border-b-0">
      <div className="flex items-center justify-between">
        <div className="font-medium text-gray-700">{label}</div>
        {percent !== undefined && (
          <div className={`px-2 py-1 rounded text-sm font-semibold ${changeBg} ${changeColor}`}>
            {changeSign}{percent.toFixed(1)}%
          </div>
        )}
      </div>
      <div className="mt-2 grid grid-cols-2 gap-4 text-sm">
        <div>
          <div className="text-gray-500">Original</div>
          <div className="font-semibold text-gray-900">
            {origValue.toFixed(decimals)} {unit}
          </div>
        </div>
        <div>
          <div className="text-gray-500">Adjusted</div>
          <div className={`font-semibold ${changeColor}`}>
            {adjValue.toFixed(decimals)} {unit}
          </div>
        </div>
      </div>
    </div>
  );
}
