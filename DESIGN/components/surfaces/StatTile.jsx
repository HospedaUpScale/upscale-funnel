import React from "react";
import { Icon } from "../core/Icon.jsx";
import { Badge } from "../core/Badge.jsx";

export function StatTile({
  label, value, icon, delta, deltaTone = "success", caption, chip, style, ...rest
}) {
  return (
    <div
      style={{
        display: "grid", gap: 10, padding: 14, minWidth: 0,
        background: "var(--surface-card)", border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-md)", boxShadow: "var(--shadow-xs)", ...style,
      }}
      {...rest}
    >
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 8 }}>
        <span style={{ display: "inline-flex", alignItems: "center", gap: 7, minWidth: 0 }}>
          {icon ? (
            <span style={{ display: "grid", placeItems: "center", width: 24, height: 24, flex: "none", borderRadius: "var(--radius-sm)", background: "var(--gray-50)", border: "1px solid var(--border-subtle)" }}>
              <Icon name={icon} size={13} color="var(--gray-600)" />
            </span>
          ) : null}
          <span style={{ font: "var(--type-label)", color: "var(--text-muted)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{label}</span>
        </span>
        {chip ? <Badge tone="success" size="sm">{chip}</Badge> : null}
      </div>
      <div style={{ display: "flex", alignItems: "baseline", gap: 8, flexWrap: "wrap" }}>
        <span style={{ font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)", color: "var(--ink-900)", letterSpacing: "var(--tracking-snug)", fontVariantNumeric: "tabular-nums" }}>{value}</span>
        {delta ? (
          <span style={{ display: "inline-flex", alignItems: "center", gap: 2, font: "var(--weight-semibold) var(--text-2xs)/1 var(--font-ui)", color: deltaTone === "danger" ? "var(--red-600)" : "var(--status-success-fg)" }}>
            <Icon name={deltaTone === "danger" ? "trending-down" : "trending-up"} size={12} />
            {delta}
          </span>
        ) : null}
      </div>
      {caption ? <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{caption}</span> : null}
    </div>
  );
}
