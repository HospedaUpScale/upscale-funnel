/**
 * Compact dropdown used for card-level range and account filters.
 */
export interface SelectOption { value: string; label: string }
export interface SelectProps {
  options: Array<SelectOption | string>;
  value?: string;
  onChange?: (value: string) => void;
  label?: string;
  size?: "sm" | "md";
  /** quiet = white pill (in-card default) · field = grey rounded rectangle (forms) */
  variant?: "quiet" | "field";
  /** Node rendered before the label, e.g. a flag. */
  leading?: React.ReactNode;
  width?: number | string;
  disabled?: boolean;
  style?: React.CSSProperties;
}
export function Select(props: SelectProps): JSX.Element;
