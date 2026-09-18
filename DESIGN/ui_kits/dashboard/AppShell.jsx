(() => {
const { SidebarNav, AccountSwitcher, TopBar, UpgradeCard, Button, IconButton } = window.UpscaleHubDesignSystem_b4eac1;

const SHELL_TITLES = {
  dash: { title: "Home", subtitle: "Track finances easily with AI insights and recommendations." },
  ai: { title: "Fynix AI Assistant", subtitle: "Get smart financial insights, forecasts, and tips." },
  tx: { title: "Transactions", subtitle: "View, track, and manage all expenses with ease." },
  wallet: { title: "Wallet", subtitle: "Securely store, track, and manage your money." },
  inv: { title: "Invoices", subtitle: "Issue, send, and reconcile invoices in one place." },
  rep: { title: "Reports", subtitle: "Build and export reports across every account." },
  set: { title: "Settings", subtitle: "Manage your profile, security, and preferences." },
  help: { title: "Help Center", subtitle: "Search guides or reach the Fynix team." },
};

function AppShell({ page, onNavigate, actions, children }) {
  const [collapsed, setCollapsed] = React.useState(false);
  const meta = SHELL_TITLES[page] || SHELL_TITLES.dash;
  return (
    <div style={{ display: "flex", minHeight: "100vh", background: "var(--surface-canvas)" }}>
      <SidebarNav
        sections={window.FX.nav}
        active={page}
        onNavigate={onNavigate}
        collapsed={collapsed}
        header={!collapsed ? <AccountSwitcher name={window.FX.user.name} role={window.FX.user.role} /> : null}
        footer={!collapsed ? <UpgradeCard /> : null}
        style={{ position: "sticky", top: 0, height: "100vh" }}
      />
      <div style={{ flex: 1, minWidth: 0, display: "flex", flexDirection: "column" }}>
        <TopBar
          title={meta.title}
          subtitle={meta.subtitle}
          userName={window.FX.user.name}
          actions={
            <>
              <IconButton icon={collapsed ? "panel-left-open" : "panel-left-close"} label="Toggle sidebar" variant="bare" onClick={() => setCollapsed((c) => !c)} />
              {actions}
            </>
          }
        />
        <main style={{ flex: 1, minWidth: 0, padding: "var(--page-padding)" }}>{children}</main>
      </div>
    </div>
  );
}

function Placeholder({ title }) {
  return (
    <div style={{ display: "grid", placeItems: "center", minHeight: 420, gap: 10, textAlign: "center" }}>
      <img src="../../assets/logo-mark.png" alt="" style={{ width: 34, opacity: 0.35 }} />
      <span style={{ font: "var(--type-card-title)", color: "var(--ink-900)" }}>{title}</span>
      <span style={{ font: "var(--type-body-sm)", color: "var(--text-muted)", maxWidth: 380 }}>
        Not present in the supplied case study &mdash; intentionally left blank rather than invented.
      </span>
    </div>
  );
}

Object.assign(window, { AppShell, Placeholder });

})();
