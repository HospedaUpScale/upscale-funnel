import React from "react";
import { Icon } from "./Icon.jsx";

const TONES = {
  success: { background: "var(--status-success-bg)", color: "var(--status-success-fg)" },
  danger: { background: "var(--status-danger-bg)", color: "var(--status-danger-fg)" },
  warning: { background: "var(--status-warning-bg)", color: "var(--status-warning-fg)" },
  neutral: { background: "var(--status-neutral-bg)", color: "var(--status-neutral-fg)" },
  brand: { background: "var(--surface-brand)", color: "var(--text-on-brand)" },
  accent: { background: "var(--surface-accent)", color: "var(--text-on-accent)" },
  inverse: { background: "var(--ink-900)", color: "var(--white)" },
  outline: { background: "var(--white)", color: "var(--gray-600)", boxShadow: "inset 0 0 0 1px var(--border-default)" },
};

export function Badge({ children, tone = "neutral", icon, dot = false, size = "md", style, ...rest }) {
  const t = TONES[tone] || TONES.neutral;
  const small = size === "sm";
  return (
    <span
      style={{
        display: "inline-flex", alignItems: "center", gap: small ? 4 : 5,
        height: small ? 18 : 22, padding: small ? "0 7px" : "0 9px",
        borderRadius: "var(--radius-pill)",
        font: `var(--weight-medium) ${small ? "var(--text-2xs)" : "var(--text-xs)"}/1 var(--font-ui)`,
        letterSpacing: "var(--tracking-snug)", whiteSpace: "nowrap", ...t, ...style,
      }}
      {...rest}
    >
      {dot ? <span style={{ width: 5, height: 5, borderRadius: "var(--radius-pill)", background: "currentColor" }} /> : null}
      {icon ? <Icon name={icon} size={small ? 10 : 12} /> : null}
      {children}
    </span>
  );
}
