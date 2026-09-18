import React from "react";
import { Icon } from "../core/Icon.jsx";
import { IconButton } from "../core/IconButton.jsx";

export function Card({
  title, subtitle, action, menu = false, onMenu, children, padding,
  tone = "default", radius, style, bodyStyle, ...rest
}) {
  const tones = {
    default: { background: "var(--surface-card)", border: "1px solid var(--border-subtle)", boxShadow: "var(--shadow-card)" },
    sunken: { background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", boxShadow: "none" },
    inverse: { background: "var(--gradient-ink)", border: "1px solid var(--ink-800)", boxShadow: "var(--shadow-md)", color: "var(--white)" },
    brand: { background: "var(--gradient-brand)", border: "1px solid var(--blue-700)", boxShadow: "var(--shadow-brand)", color: "var(--white)" },
    quiet: { background: "var(--surface-card)", border: "1px solid transparent", boxShadow: "none" },
  };
  const inverse = tone === "inverse";
  return (
    <section
      style={{
        display: "flex", flexDirection: "column", minWidth: 0,
        padding: padding != null ? padding : "var(--card-padding)",
        borderRadius: radius || "var(--radius-card)", ...tones[tone], ...style,
      }}
      {...rest}
    >
      {title || action || menu ? (
        <header style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 12, marginBottom: 12 }}>
          <div style={{ display: "grid", gap: 2, minWidth: 0 }}>
            {title ? <h3 style={{ font: "var(--type-card-title)", color: inverse ? "var(--white)" : "var(--text-heading)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{title}</h3> : null}
            {subtitle ? <p style={{ margin: 0, font: "var(--type-caption)", color: inverse ? "var(--ink-300)" : "var(--text-muted)" }}>{subtitle}</p> : null}
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 6, flex: "none" }}>
            {action}
            {menu ? <IconButton icon="more-horizontal" label="More" size="sm" variant="bare" onClick={onMenu} /> : null}
          </div>
        </header>
      ) : null}
      <div style={{ display: "flex", flexDirection: "column", minWidth: 0, flex: 1, ...bodyStyle }}>{children}</div>
    </section>
  );
}

export function CardFooterLink({ children, icon = "arrow-right", onClick }) {
  return (
    <button type="button" onClick={onClick} style={{ display: "inline-flex", alignItems: "center", gap: 6, marginTop: 12, padding: 0, border: "none", background: "transparent", cursor: "pointer", font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)", color: "var(--ink-900)" }}>
      {children}
      <Icon name={icon} size={13} />
    </button>
  );
}
