/**
 * Multi-select box for table rows and filters.
 */
export interface CheckboxProps {
  checked?: boolean;
  indeterminate?: boolean;
  onChange?: (next: boolean) => void;
  label?: string;
  disabled?: boolean;
  style?: React.CSSProperties;
}
export function Checkbox(props: CheckboxProps): JSX.Element;
