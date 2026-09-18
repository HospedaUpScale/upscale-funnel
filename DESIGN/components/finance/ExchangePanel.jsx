import React from "react";
import { Icon } from "../core/Icon.jsx";
import { Button } from "../core/Button.jsx";

function FlagSelect({ flag, code, onClick }) {
  return (
    <button type="button" onClick={onClick} style={{ display: "inline-flex", alignItems: "center", gap: 6, height: 30, padding: "0 8px", background: "var(--white)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-pill)", cursor: "pointer", font: "var(--weight-medium) var(--text-xs)/1 var(--font-ui)", color: "var(--ink-900)" }}>
      <span style={{ fontSize: 13 }}>{flag}</span>
      {code}
      <Icon name="chevron-down" size={13} color="var(--gray-400)" />
    </button>
  );
}

export function ExchangePanel({
  from = { flag: "\u{1F1FA}\u{1F1F8}", code: "USD" },
  to = { flag: "\u{1F1EC}\u{1F1E7}", code: "GBP" },
  rate = "1 USD = 0.77 GBP", amount = "$100.00", available = "$1600.86",
  rows = [{ label: "Tax (2%)", value: "$2.00" }, { label: "Exchange fee (1%)", value: "$1.00" }, { label: "Total amount", value: "\u20AC90.7" }],
  cta = "Exchange", onExchange, onSwap, style, ...rest
}) {
  return (
    <div style={{ display: "grid", gap: 12, ...style }} {...rest}>
      <div style={{ display: "grid", gap: 10, padding: 12, background: "var(--surface-sunken)", borderRadius: "var(--radius-md)", border: "1px solid var(--border-subtle)", justifyItems: "center" }}>
        <span style={{ font: "var(--type-caption)", color: "var(--text-muted)" }}>{rate}</span>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <FlagSelect {...from} />
          <button type="button" aria-label="Swap currencies" onClick={onSwap} style={{ display: "grid", placeItems: "center", width: 30, height: 30, borderRadius: "var(--radius-pill)", background: "var(--white)", border: "1px solid var(--border-subtle)", cursor: "pointer" }}>
            <Icon name="arrow-left-right" size={14} color="var(--blue-600)" />
          </button>
          <FlagSelect {...to} />
        </div>
        <div style={{ display: "grid", gap: 2, justifyItems: "center" }}>
          <span style={{ font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)", color: "var(--ink-900)", fontVariantNumeric: "tabular-nums" }}>{amount}</span>
          <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Available: {available}</span>
        </div>
      </div>
      <div style={{ display: "flex", gap: 8 }}>
        {rows.map((r) => (
          <div key={r.label} style={{ flex: 1, display: "grid", gap: 3, padding: "8px 10px", background: "var(--white)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-sm)" }}>
            <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{r.label}</span>
            <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)", color: "var(--ink-900)", fontVariantNumeric: "tabular-nums" }}>{r.value}</span>
          </div>
        ))}
      </div>
      <Button variant="brand" fullWidth pill={false} onClick={onExchange}>{cta}</Button>
    </div>
  );
}
