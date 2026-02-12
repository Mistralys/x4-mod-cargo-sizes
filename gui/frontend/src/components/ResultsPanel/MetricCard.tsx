/**
 * Metric card component for displaying a single metric value.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

interface MetricCardProps {
  label: string;
  value: string | number;
  unit?: string;
  variant?: 'neutral' | 'positive' | 'negative';
  className?: string;
}

export function MetricCard({ label, value, unit, variant = 'neutral', className = '' }: MetricCardProps) {
  const variantStyles = {
    neutral: 'bg-gray-50 border-gray-200',
    positive: 'bg-green-50 border-green-200',
    negative: 'bg-red-50 border-red-200',
  };

  const textStyles = {
    neutral: 'text-gray-900',
    positive: 'text-green-900',
    negative: 'text-red-900',
  };

  return (
    <div className={`border rounded-lg p-4 ${variantStyles[variant]} ${className}`}>
      <div className="text-sm font-medium text-gray-600 mb-1">{label}</div>
      <div className={`text-2xl font-bold ${textStyles[variant]}`}>
        {value}
        {unit && <span className="text-lg ml-1">{unit}</span>}
      </div>
    </div>
  );
}
