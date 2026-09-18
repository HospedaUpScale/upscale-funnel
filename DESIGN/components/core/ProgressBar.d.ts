/**
 * Horizontal meter for finance score, spending limits and budget usage.
 */
export interface ProgressBarProps {
  value?: number;
  max?: number;
  tone?: "brand" | "ink" | "danger";
  height?: number;
  label?: string;
  valueLabel?: string;
  /** Multi-part bar: [{ value, color }] — renders gapped segments instead of one fill. */
  segments?: Array<{ value: number; color?: string }>;
  style?: React.CSSProperties;
}
export function ProgressBar(props: ProgressBarProps): JSX.Element;
