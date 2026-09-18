import React from "react";

export function Tabs({ items = [], value, onChange, variant = "underline", size = "md", style, ...rest }) {
  const active = value != null ? value : items[0] && (items[0].id || items[0]);
  const norm = items.map((it) => (typeof it === "string" ? { id: it, label: it } : it));
  const isPill = variant === "pill";
  return (
    <div
      role="tablist"
      style={{
        display: "inline-flex", alignItems: "center", gap: isPill ? 4 : 18,
        padding: isPill ? 3 : 0, background: isPill ? "var(--gray-100)" : "transparent",
        borderRadius: isPill ? "var(--radius-pill)" : 0,
        borderBottom: isPill ? "none" : "1px solid var(--border-subtle)",
        ...style,
      }}
      {...rest}
    >
      {norm.map((it) => {
        const on = it.id === active;
        return (
          <button
            key={it.id} role="tab" aria-selected={on} onClick={() => onChange && onChange(it.id)}
            style={{
              position: "relative", display: "inline-flex", alignItems: "center", gap: 6,
              border: "none", cursor: "pointer", background: isPill ? (on ? "var(--white)" : "transparent") : "transparent",
              padding: isPill ? "0 14px" : "0 0 9px", height: isPill ? (size === "sm" ? 26 : 30) : "auto",
              borderRadius: isPill ? "var(--radius-pill)" : 0,
              boxShadow: isPill && on ? "var(--shadow-xs)" : "none",
              font: `${on ? "var(--weight-semibold)" : "var(--weight-medium)"} ${size === "sm" ? "var(--text-xs)" : "var(--text-sm)"}/1 var(--font-ui)`,
              color: on ? "var(--ink-900)" : "var(--text-muted)",
              transition: "var(--transition-control)",
            }}
          >
            {it.label}
            {it.meta ? <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{it.meta}</span> : null}
            {!isPill && on ? (
              <span style={{ position: "absolute", left: 0, right: 0, bottom: -1, height: 2, borderRadius: "var(--radius-pill)", background: "var(--blue-600)" }} />
            ) : null}
          </button>
        );
      })}
    </div>
  );
}
