/**
 * Smoothed line/area chart for wallet balance and cash-flow trends.
 */
export interface AreaSeries { points: number[]; color?: string; width?: number; fill?: boolean }
export interface AreaChartProps {
  series: AreaSeries[];
  height?: number;
  yTicks?: string[];
  xLabels?: string[];
  /** Index of the point to highlight with a dot and drop line. */
  marker?: number;
  fill?: boolean;
  style?: React.CSSProperties;
}
export function AreaChart(props: AreaChartProps): JSX.Element;
export interface SparklineProps { points: number[]; width?: number; height?: number; color?: string; style?: React.CSSProperties }
export function Sparkline(props: SparklineProps): JSX.Element;
