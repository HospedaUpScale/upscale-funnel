import React from "react";

/* Cashflow bars: income above the axis (ink), expense below (green) — as in the source. */
export function BarChart({
  data = [], height = 200, gap = 0.34, showAxis = true, formatTick = (v) => v,
  positiveColor = "var(--chart-1)", negativeColor = "var(--chart-2)", style, ...rest
}) {
  const max = Math.max(1, ...data.map((d) => Math.max(Math.abs(d.income || 0), Math.abs(d.expense || 0))));
  const step = 100 / Math.max(1, data.length);
  const bw = step * (1 - gap);
  const ticks = [max, max / 2, 0, -max / 2, -max];
  return (
    <div style={{ display: "flex", gap: 10, ...style }} {...rest}>
      {showAxis ? (
        <div style={{ display: "flex", flexDirection: "column", justifyContent: "space-between", height, font: "var(--type-caption)", color: "var(--chart-axis)", textAlign: "right", flex: "none" }}>
          {ticks.map((t, i) => <span key={i}>{formatTick(Math.round(t))}</span>)}
        </div>
      ) : null}
      <div style={{ position: "relative", flex: 1, minWidth: 0 }}>
        <svg viewBox={`0 0 100 ${height}`} preserveAspectRatio="none" style={{ width: "100%", height, display: "block", overflow: "visible" }}>
          {ticks.map((t, i) => (
            <line key={i} x1="0" x2="100" y1={(i * height) / (ticks.length - 1)} y2={(i * height) / (ticks.length - 1)} stroke="var(--chart-grid)" strokeWidth="1" vectorEffect="non-scaling-stroke" strokeDasharray={i === 2 ? "0" : "3 4"} />
          ))}
          {data.map((d, i) => {
            const x = i * step + (step - bw) / 2;
            const mid = height / 2;
            const ih = ((d.income || 0) / max) * (height / 2);
            const eh = ((d.expense || 0) / max) * (height / 2);
            return (
              <g key={i}>
                <rect x={x} y={mid - ih} width={bw} height={Math.max(2, ih)} rx="1.6" fill={positiveColor} />
                <rect x={x} y={mid} width={bw} height={Math.max(2, eh)} rx="1.6" fill={negativeColor} />
              </g>
            );
          })}
        </svg>
        <div style={{ display: "flex", marginTop: 8 }}>
          {data.map((d, i) => (
            <span key={i} style={{ flex: 1, textAlign: "center", font: "var(--type-caption)", color: "var(--chart-axis)" }}>{d.label}</span>
          ))}
        </div>
      </div>
    </div>
  );
}

export function ChartLegend({ items = [], style }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 14, ...style }}>
      {items.map((it) => (
        <span key={it.label} style={{ display: "inline-flex", alignItems: "center", gap: 6, font: "var(--type-caption)", color: "var(--text-muted)" }}>
          <span style={{ width: 8, height: 8, borderRadius: 2, background: it.color }} />
          {it.label}
        </span>
      ))}
    </div>
  );
}
