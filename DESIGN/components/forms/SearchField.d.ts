/**
 * Global search pill with keyboard-shortcut hint.
 */
export interface SearchFieldProps {
  placeholder?: string;
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  /** Keycap labels shown at the right. Pass null to hide. */
  shortcut?: string[] | null;
  width?: number | string;
  size?: "sm" | "md";
  style?: React.CSSProperties;
}
export function SearchField(props: SearchFieldProps): JSX.Element;
