/**
 * Sidebar upsell block.
 */
export interface UpgradeCardProps {
  title?: string;
  body?: string;
  cta?: string;
  onCta?: () => void;
  style?: React.CSSProperties;
}
export function UpgradeCard(props: UpgradeCardProps): JSX.Element;
