/**
 * Left product navigation with grouped sections and a collapsed rail.
 */
export interface NavItem { id: string; label: string; icon: string; badge?: string }
export interface NavSection { title?: string; items: NavItem[] }
export interface SidebarNavProps {
  sections: NavSection[];
  active?: string;
  onNavigate?: (id: string) => void;
  /** Node under the brand row — typically AccountSwitcher. */
  header?: React.ReactNode;
  /** Node pinned to the bottom — typically UpgradeCard. */
  footer?: React.ReactNode;
  collapsed?: boolean;
  brand?: boolean;
  style?: React.CSSProperties;
}
export function SidebarNav(props: SidebarNavProps): JSX.Element;
export interface AccountSwitcherProps { name?: string; role?: string; avatar?: string; onClick?: () => void; style?: React.CSSProperties }
export function AccountSwitcher(props: AccountSwitcherProps): JSX.Element;
