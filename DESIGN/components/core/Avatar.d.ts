/**
 * User image with the brand gradient as its fallback.
 */
export interface AvatarProps {
  src?: string;
  /** Used for initials fallback and alt text. */
  name?: string;
  size?: "xs" | "sm" | "md" | "lg" | "xl";
  /** White + green halo, used for the signed-in user in the top bar. */
  ring?: boolean;
  status?: "online" | "busy" | "away";
  style?: React.CSSProperties;
}
export function Avatar(props: AvatarProps): JSX.Element;
