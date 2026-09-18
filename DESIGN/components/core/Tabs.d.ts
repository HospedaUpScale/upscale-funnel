/**
 * In-card view switcher.
 */
export interface TabItem { id: string; label: string; meta?: string }
export interface TabsProps {
  items: Array<TabItem | string>;
  value?: string;
  onChange?: (id: string) => void;
  /** underline = green 2px rule (default, in-card) · pill = grey track with white thumb */
  variant?: "underline" | "pill";
  size?: "sm" | "md";
  style?: React.CSSProperties;
}
export function Tabs(props: TabsProps): JSX.Element;
