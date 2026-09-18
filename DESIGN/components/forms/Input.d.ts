/**
 * Labelled single-line text field.
 */
export interface InputProps {
  label?: string;
  hint?: string;
  error?: string;
  /** Leading Lucide icon name. */
  icon?: string;
  /** Trailing static text (currency, unit). */
  suffix?: string;
  size?: "sm" | "md" | "lg";
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  placeholder?: string;
  type?: string;
  disabled?: boolean;
  style?: React.CSSProperties;
  inputStyle?: React.CSSProperties;
}
export function Input(props: InputProps): JSX.Element;
