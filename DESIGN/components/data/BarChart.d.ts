/**
 * Cashflow bars — income above the axis, expense below.
 */
export interface BarDatum { label: string; income?: number; expense?: number }
export interface BarChartProps {
  data: BarDatum[];
  height?: number;
  /** Fraction of each slot left empty, 0–1. Default 0.34. */
  gap?: number;
  showAxis?: boolean;
  formatTick?: (v: number) => string;
  positiveColor?: string;
  negativeColor?: string;
  style?: React.CSSProperties;
}
export function BarChart(props: BarChartProps): JSX.Element;
export interface ChartLegendProps { items: Array<{ label: string; color: string }>; style?: React.CSSProperties }
export function ChartLegend(props: ChartLegendProps): JSX.Element;
