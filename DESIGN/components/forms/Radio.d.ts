/**
 * Single-choice control; also the selector inside CreditCardItem.
 */
export interface RadioProps {
  checked?: boolean;
  onChange?: (next: true) => void;
  label?: string;
  description?: string;
  disabled?: boolean;
  style?: React.CSSProperties;
}
export function Radio(props: RadioProps): JSX.Element;
