/**
 * Brand icon glyph. Renders a Lucide outline icon as a CSS mask so it inherits colour.
 */
export interface IconProps {
  /** Lucide icon name, kebab-case, e.g. "trending-up". */
  name: string;
  /** Square size in px. 14–16 inside controls, 18–20 in nav. Default 18. */
  size?: number;
  /** Any CSS colour; defaults to currentColor. */
  color?: string;
  style?: React.CSSProperties;
}
export function Icon(props: IconProps): JSX.Element;
