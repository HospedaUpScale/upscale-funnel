/**
 * Expense breakdown ring with a centred total.
 */
export interface DonutSegment { label?: string; value: number; color: string }
export interface DonutChartProps {
  segments: DonutSegment[];
  size?: number;
  thickness?: number;
  label?: string;
  value?: string;
  /** Degrees of gap between segments. Default 3. */
  gapDeg?: number;
  style?: React.CSSProperties;
}
export function DonutChart(props: DonutChartProps): JSX.Element;
export interface CategoryLegendProps {
  items: Array<{ label: string; value: string; color: string; pct?: string; pctColor?: string }>;
  style?: React.CSSProperties;
}
export function CategoryLegend(props: CategoryLegendProps): JSX.Element;
