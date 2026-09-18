import React from "react";
import { Icon } from "../core/Icon.jsx";

const ASSETS = () => (typeof window !== "undefined" && window.FYNIX_ASSET_BASE) || "";

export function AccountSwitcher({ name = "Jenny Wilson", role = "Personal Account", avatar, onClick, style, ...rest }) {
  return (
    <button
      type="button" onClick={onClick}
      style={{
        display: "flex", alignItems: "center", gap: 9, width: "100%", padding: "7px 10px",
        background: "var(--white)", border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-md)", boxShadow: "var(--shadow-xs)", cursor: "pointer", textAlign: "left",
        transition: "var(--transition-control)", ...style,
      }}
      {...rest}
    >
      <span style={{ width: 28, height: 28, flex: "none", borderRadius: "var(--radius-pill)", overflow: "hidden", background: "var(--gradient-orb)" }}>
        {avatar ? <img src={avatar} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} /> : null}
      </span>
      <span style={{ display: "grid", gap: 1, minWidth: 0, flex: 1 }}>
        <span style={{ font: "var(--weight-semibold) var(--text-xs)/1.2 var(--font-ui)", color: "var(--ink-900)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{name}</span>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{role}</span>
      </span>
      <Icon name="chevron-down" size={14} color="var(--gray-400)" />
    </button>
  );
}

export function SidebarNav({
  sections = [], active, onNavigate, header, footer, collapsed = false,
  brand = true, style, ...rest
}) {
  return (
    <nav
      style={{
        display: "flex", flexDirection: "column", gap: 14, flex: "none",
        width: collapsed ? "var(--sidebar-width-collapsed)" : "var(--sidebar-width)",
        padding: "14px 12px", background: "var(--surface-canvas)",
        borderRight: "1px solid var(--border-subtle)", overflow: "hidden",
        transition: "width var(--duration-normal) var(--ease-standard)", ...style,
      }}
      {...rest}
    >
      {brand ? (
        <div style={{ display: "flex", alignItems: "center", gap: 8, padding: "2px 6px 0" }}>
          <img src={ASSETS() + "assets/logo-mark.png"} alt="Fynix" style={{ width: 20, flex: "none" }} />
          {!collapsed ? <span style={{ font: "var(--weight-semibold) var(--text-md)/1 var(--font-display)", color: "var(--ink-900)", letterSpacing: "var(--tracking-snug)" }}>Fynix</span> : null}
        </div>
      ) : null}
      {header}
      <div style={{ display: "grid", gap: 14, flex: 1, minHeight: 0, overflowY: "auto" }}>
        {sections.map((sec) => (
          <div key={sec.title || "main"} style={{ display: "grid", gap: 2 }}>
            {sec.title && !collapsed ? (
              <span style={{ padding: "6px 8px 4px", font: "var(--type-eyebrow)", letterSpacing: "var(--tracking-caps)", textTransform: "uppercase", color: "var(--text-subtle)" }}>{sec.title}</span>
            ) : null}
            {sec.items.map((item) => {
              const on = item.id === active;
              return (
                <button
                  key={item.id} type="button" onClick={() => onNavigate && onNavigate(item.id)}
                  title={collapsed ? item.label : undefined}
                  style={{
                    display: "flex", alignItems: "center", gap: 10, height: 34, padding: collapsed ? 0 : "0 10px",
                    justifyContent: collapsed ? "center" : "flex-start",
                    background: on ? "var(--white)" : "transparent",
                    boxShadow: on ? "var(--shadow-xs)" : "none",
                    border: "1px solid " + (on ? "var(--border-subtle)" : "transparent"),
                    borderRadius: "var(--radius-sm)", cursor: "pointer", width: "100%",
                    font: `${on ? "var(--weight-semibold)" : "var(--weight-medium)"} var(--text-sm)/1 var(--font-ui)`,
                    color: on ? "var(--ink-900)" : "var(--gray-600)",
                    transition: "var(--transition-control)",
                  }}
                  onMouseEnter={(e) => { if (!on) e.currentTarget.style.background = "var(--gray-150)"; }}
                  onMouseLeave={(e) => { if (!on) e.currentTarget.style.background = "transparent"; }}
                >
                  <Icon name={item.icon} size={16} color={on ? "var(--ink-900)" : "var(--gray-500)"} />
                  {!collapsed ? <span style={{ flex: 1, textAlign: "left" }}>{item.label}</span> : null}
                  {!collapsed && item.badge ? (
                    <span style={{ padding: "1px 6px", borderRadius: "var(--radius-pill)", background: "var(--surface-brand)", font: "var(--weight-semibold) var(--text-2xs)/1.6 var(--font-ui)", color: "var(--white)" }}>{item.badge}</span>
                  ) : null}
                </button>
              );
            })}
          </div>
        ))}
      </div>
      {footer}
    </nav>
  );
}
