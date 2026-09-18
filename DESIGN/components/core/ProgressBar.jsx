import React from "react";

export function ProgressBar({
  value = 0, max = 100, tone = "brand", height = 8, label, valueLabel,
  segments, style, ...rest
}) {
  const pct = Math.max(0, Math.min(100, (value / max) * 100));
  const fill = tone === "ink" ? "var(--ink-900)" : tone === "danger" ? "var(--red-600)" : tone === "accent" ? "var(--green-500)" : "var(--surface-brand)";
  return (
    <div style={{ display: "grid", gap: 6, ...style }} {...rest}>
      {label || valueLabel ? (
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", gap: 8 }}>
          {label ? <span style={{ font: "var(--type-caption)", color: "var(--text-muted)" }}>{label}</span> : <span />}
          {valueLabel ? <span style={{ font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)", color: "var(--ink-900)" }}>{valueLabel}</span> : null}
        </div>
      ) : null}
      <div style={{ display: "flex", gap: 4, height, borderRadius: "var(--radius-pill)", background: "var(--gray-150)", overflow: "hidden" }}>
        {segments ? (
          segments.map((seg, i) => (
            <span key={i} style={{ width: `${(seg.value / max) * 100}%`, background: seg.color || fill, borderRadius: "var(--radius-pill)", transition: `width var(--duration-slow) var(--ease-out)` }} />
          ))
        ) : (
          <span style={{ width: `${pct}%`, background: fill, borderRadius: "var(--radius-pill)", transition: `width var(--duration-slow) var(--ease-out)` }} />
        )}
      </div>
    </div>
  );
}
