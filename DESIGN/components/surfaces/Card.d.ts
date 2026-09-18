/**
 * The dashboard container: white, 16px radius, hairline border, quiet green-tinted shadow.
 */
export interface CardProps {
  title?: React.ReactNode;
  subtitle?: React.ReactNode;
  /** Right-aligned header node, typically a Select or Button. */
  action?: React.ReactNode;
  /** Show the ⋯ overflow button in the header. */
  menu?: boolean;
  onMenu?: () => void;
  children?: React.ReactNode;
  padding?: number | string;
  /** default white · sunken grey · inverse ink gradient · brand green gradient · quiet borderless */
  tone?: "default" | "sunken" | "inverse" | "brand" | "quiet";
  radius?: string;
  style?: React.CSSProperties;
  bodyStyle?: React.CSSProperties;
}
export function Card(props: CardProps): JSX.Element;
export interface CardFooterLinkProps { children?: React.ReactNode; icon?: string; onClick?: () => void }
export function CardFooterLink(props: CardFooterLinkProps): JSX.Element;
