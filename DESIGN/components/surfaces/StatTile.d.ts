/**
 * Small labelled figure with optional delta chip — the "AI Enhancements" row.
 */
export interface StatTileProps {
  label: string;
  value: React.ReactNode;
  /** Lucide icon shown in a grey rounded chip before the label. */
  icon?: string;
  /** e.g. "+12%" */
  delta?: string;
  deltaTone?: "success" | "danger";
  caption?: string;
  /** Small green badge at top-right, e.g. "Guide". */
  chip?: string;
  style?: React.CSSProperties;
}
export function StatTile(props: StatTileProps): JSX.Element;
