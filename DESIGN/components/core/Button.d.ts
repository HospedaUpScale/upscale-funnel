/**
 * Primary action control.
 */
export interface ButtonProps {
  children?: React.ReactNode;
  /** primary = ink pill (default) · brand = green pill · secondary = white outline · soft = green tint · ghost = text only · danger = red tint */
  variant?: "primary" | "brand" | "secondary" | "soft" | "ghost" | "danger";
  size?: "sm" | "md" | "lg";
  /** Lucide icon name rendered before the label. */
  icon?: string;
  /** Lucide icon name rendered after the label — the house style for directional actions. */
  iconRight?: string;
  /** Fully rounded. Default true; set false for the wide in-card confirm buttons. */
  pill?: boolean;
  disabled?: boolean;
  fullWidth?: boolean;
  onClick?: (e: React.MouseEvent) => void;
  style?: React.CSSProperties;
}
export function Button(props: ButtonProps): JSX.Element;
