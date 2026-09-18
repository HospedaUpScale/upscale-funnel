/**
 * The AI Fynix input: prompt line, tool tray, model chips and Send.
 */
export interface AssistantComposerProps {
  placeholder?: string;
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  onSend?: () => void;
  /** Suggested-prompt chips shown above the tray. */
  chips?: string[];
  /** Model selector labels. */
  models?: string[];
  /** Hide the model chips (narrow placements). */
  compact?: boolean;
  style?: React.CSSProperties;
}
export function AssistantComposer(props: AssistantComposerProps): JSX.Element;
export interface AssistantOrbProps { size?: number; style?: React.CSSProperties }
export function AssistantOrb(props: AssistantOrbProps): JSX.Element;
