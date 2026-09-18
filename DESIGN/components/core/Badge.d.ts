/**
 * Status pill for table cells, card chips and labels.
 */
export interface BadgeProps {
  children?: React.ReactNode;
  tone?: "success" | "danger" | "warning" | "neutral" | "brand" | "inverse" | "outline";
  /** Leading Lucide icon. */
  icon?: string;
  /** Leading 5px dot in the current colour. */
  dot?: boolean;
  size?: "sm" | "md";
  style?: React.CSSProperties;
}
export function Badge(props: BadgeProps): JSX.Element;
