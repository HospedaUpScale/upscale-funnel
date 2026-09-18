/**
 * AI starter prompt tile with a "Learn more →" affordance.
 */
export interface SuggestionCardProps {
  title: string;
  body: string;
  cta?: string;
  icon?: string;
  onClick?: () => void;
  style?: React.CSSProperties;
}
export function SuggestionCard(props: SuggestionCardProps): JSX.Element;
