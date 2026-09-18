import React from "react";
import { Button } from "../core/Button.jsx";
import { IconButton } from "../core/IconButton.jsx";

export function BalanceCard({
  label = "Total Balance", amount, currency = "USD", onAdd,
  primaryAction = "Deposit", secondaryAction = "Send", onPrimary, onSecondary,
  style, ...rest
}) {
  return (
    <div
      style={{
        position: "relative", overflow: "hidden", display: "flex", flexDirection: "column",
        justifyContent: "space-between", gap: 18, padding: 16, minHeight: 148,
        borderRadius: "var(--radius-card)", background: "var(--gradient-brand)",
        boxShadow: "var(--shadow-brand)", ...style,
      }}
      {...rest}
    >
      {/* swirl motif: concentric translucent arcs, as on the source hero card */}
      <span style={{ position: "absolute", right: -70, top: -40, width: 260, height: 260, borderRadius: "50%", border: "18px solid rgba(255,255,255,.14)", pointerEvents: "none" }} />
      <span style={{ position: "absolute", right: 10, bottom: -110, width: 220, height: 220, borderRadius: "50%", border: "14px solid rgba(255,255,255,.11)", pointerEvents: "none" }} />
      <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 12, position: "relative" }}>
        <div style={{ display: "grid", gap: 4 }}>
          <span style={{ font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)", color: "rgba(255,255,255,.86)" }}>{label}</span>
          <span style={{ display: "flex", alignItems: "baseline", gap: 6 }}>
            <span style={{ font: "var(--weight-bold) var(--text-4xl)/1 var(--font-ui)", color: "var(--white)", letterSpacing: "var(--tracking-tight)", fontVariantNumeric: "tabular-nums" }}>{amount}</span>
            <span style={{ font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)", color: "rgba(255,255,255,.74)" }}>{currency}</span>
          </span>
        </div>
        <IconButton icon="plus" label="Add account" variant="plain" onClick={onAdd} style={{ background: "var(--white)", borderColor: "transparent", boxShadow: "var(--shadow-sm)" }} />
      </div>
      <div style={{ display: "flex", gap: 8, position: "relative" }}>
        <Button variant="secondary" iconRight="arrow-down" onClick={onPrimary} style={{ borderColor: "transparent", flex: 1 }}>{primaryAction}</Button>
        <Button variant="primary" iconRight="arrow-up-right" onClick={onSecondary} style={{ flex: 1 }}>{secondaryAction}</Button>
      </div>
    </div>
  );
}
