/**
 * Currency conversion panel with rate, fee breakdown and confirm.
 */
export interface ExchangeCurrency { flag: string; code: string }
export interface ExchangePanelProps {
  from?: ExchangeCurrency;
  to?: ExchangeCurrency;
  rate?: string;
  amount?: string;
  available?: string;
  rows?: Array<{ label: string; value: string }>;
  cta?: string;
  onExchange?: () => void;
  onSwap?: () => void;
  style?: React.CSSProperties;
}
export function ExchangePanel(props: ExchangePanelProps): JSX.Element;
