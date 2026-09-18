/**
 * Selectable payment-card row plus the quick-action tile.
 */
export interface CreditCardItemProps {
  label?: string;
  brand?: string;
  last4?: string;
  selected?: boolean;
  tone?: "green" | "gray" | "ink";
  onSelect?: () => void;
  onMenu?: () => void;
  style?: React.CSSProperties;
}
export function CreditCardItem(props: CreditCardItemProps): JSX.Element;
export interface ActionTileProps { icon?: string; label: string; onClick?: () => void; style?: React.CSSProperties }
export function ActionTile(props: ActionTileProps): JSX.Element;
