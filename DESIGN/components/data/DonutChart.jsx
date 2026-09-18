import React from "react";

export function DonutChart({
  segments = [], size = 168, thickness = 22, label = "Total", value, gapDeg = 3, style, ...rest
}) {
  const total = segments.reduce((s, x) => s + x.value, 0) || 1;
  const r = (size - thickness) / 2;
  const c = 2 * Math.PI * r;
  let offset = 0;
  return (
    <div style={{ position: "relative", width: size, height: size, flex: "none", ...style }} {...rest}>
      <svg width={size} height={size} style={{ transform: "rotate(-90deg)" }}>
        <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="var(--gray-150)" strokeWidth={thickness} />
        {segments.map((s, i) => {
          const frac = s.value / total;
          const len = Math.max(0, frac * c - (gapDeg / 360) * c);
          const dash = `${len} ${c - len}`;
          const el = (
            <circle key={i} cx={size / 2} cy={size / 2} r={r} fill="none" stroke={s.color} strokeWidth={thickness}
              strokeDasharray={dash} strokeDashoffset={-offset} strokeLinecap="round" />
          );
          offset += frac * c;
          return el;
        })}
      </svg>
      <div style={{ position: "absolute", inset: 0, display: "grid", placeItems: "center", textAlign: "center", gap: 2 }}>
        <span style={{ display: "grid", gap: 2 }}>
          <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{label}</span>
          <span style={{ font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)", color: "var(--ink-900)", fontVariantNumeric: "tabular-nums" }}>{value}</span>
        </span>
      </div>
    </div>
  );
}

export function CategoryLegend({ items = [], style, ...rest }) {
  return (
    <div style={{ display: "grid", gap: 10, minWidth: 0, ...style }} {...rest}>
      {items.map((it) => (
        <div key={it.label} style={{ display: "flex", alignItems: "center", gap: 10, minWidth: 0 }}>
          {it.pct != null ? (
            <span style={{ display: "grid", placeItems: "center", width: 30, height: 20, flex: "none", borderRadius: "var(--radius-xs)", background: it.color, font: "var(--weight-semibold) var(--text-2xs)/1 var(--font-ui)", color: it.pctColor || "var(--ink-900)" }}>{it.pct}</span>
          ) : (
            <span style={{ width: 8, height: 8, flex: "none", borderRadius: "var(--radius-pill)", background: it.color }} />
          )}
          <span style={{ flex: 1, font: "var(--type-body-sm)", color: "var(--gray-600)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{it.label}</span>
          <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)", color: "var(--ink-900)", fontVariantNumeric: "tabular-nums", flex: "none" }}>{it.value}</span>
        </div>
      ))}
    </div>
  );
}
