/**
 * Square-free circular icon-only control for toolbars and card headers.
 */
export interface IconButtonProps {
  /** Lucide icon name. */
  icon: string;
  /** Accessible label — required, becomes aria-label and title. */
  label: string;
  size?: "sm" | "md" | "lg";
  /** plain = white chip (default) · bare = transparent · inverse = ink · brand = green */
  variant?: "plain" | "bare" | "inverse" | "brand";
  /** Green-tint selected state. */
  active?: boolean;
  /** Red notification dot. */
  badge?: boolean;
  disabled?: boolean;
  onClick?: (e: React.MouseEvent) => void;
  style?: React.CSSProperties;
}
export function IconButton(props: IconButtonProps): JSX.Element;
