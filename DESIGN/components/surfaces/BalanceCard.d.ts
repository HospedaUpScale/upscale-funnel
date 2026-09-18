/**
 * The green hero balance surface with Deposit / Send actions.
 */
export interface BalanceCardProps {
  label?: string;
  amount: string;
  currency?: string;
  onAdd?: () => void;
  primaryAction?: string;
  secondaryAction?: string;
  onPrimary?: () => void;
  onSecondary?: () => void;
  style?: React.CSSProperties;
}
export function BalanceCard(props: BalanceCardProps): JSX.Element;
