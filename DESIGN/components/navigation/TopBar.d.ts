/**
 * Sticky page header: title, quoted subtitle, search and utilities.
 */
export interface TopBarProps {
  title: string;
  /** Rendered in quotes, muted — the house pattern for page descriptions. */
  subtitle?: string;
  /** Nodes inserted before the search field (Export, Get Plus, …). */
  actions?: React.ReactNode;
  searchWidth?: number;
  onSearch?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  avatar?: string;
  userName?: string;
  sticky?: boolean;
  style?: React.CSSProperties;
}
export function TopBar(props: TopBarProps): JSX.Element;
