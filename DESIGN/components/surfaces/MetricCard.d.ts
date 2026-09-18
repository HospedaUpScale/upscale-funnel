/**
 * Case-study style big-percentage card with a dot scale.
 */
export interface MetricCardProps {
  eyebrow?: string;
  value: string;
  description?: string;
  /** Filled dots out of total. */
  filled?: number;
  total?: number;
  tone?: "neutral" | "brand";
  style?: React.CSSProperties;
}
export function MetricCard(props: MetricCardProps): JSX.Element;
