import React from "react";
import { Button } from "../core/Button.jsx";

export function UpgradeCard({
  title = "Upgrade plan",
  body = "Upgrade Fynix today to unlock smarter insights and financial control.",
  cta = "Upgrade your Plan", onCta, style, ...rest
}) {
  return (
    <div
      style={{
        display: "grid", gap: 8, justifyItems: "center", textAlign: "center", padding: "16px 14px 14px",
        background: "var(--white)", border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-lg)", boxShadow: "var(--shadow-card)", ...style,
      }}
      {...rest}
    >
      <img src="../../assets/logo-mark.png" alt="" style={{ width: 22, opacity: 0.9 }} />
      <span style={{ font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>{title}</span>
      <p style={{ margin: 0, font: "var(--type-caption)", color: "var(--text-muted)" }}>&ldquo;{body}&rdquo;</p>
      <Button size="sm" iconRight="arrow-up-right" fullWidth onClick={onCta} style={{ marginTop: 4 }}>{cta}</Button>
    </div>
  );
}
