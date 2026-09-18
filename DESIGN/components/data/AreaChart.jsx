import React from "react";

function path(points, w, h, smooth = 0.22) {
  const max = Math.max(...points), min = Math.min(...points);
  const span = max - min || 1;
  const pts = points.map((p, i) => [(i / (points.length - 1)) * w, h - ((p - min) / span) * h]);
  let d = `M ${pts[0][0]},${pts[0][1]}`;
  for (let i = 1; i < pts.length; i++) {
    const [x0, y0] = pts[i - 1], [x1, y1] = pts[i];
    const dx = (x1 - x0) * smooth;
    d += ` C ${x0 + dx},${y0} ${x1 - dx},${y1} ${x1},${y1}`;
  }
  return { d, pts };
}

export function AreaChart({
  series = [], height = 180, yTicks = [], xLabels = [], marker, fill = true, style, ...rest
}) {
  const W = 600;
  return (
    <div style={{ display: "flex", gap: 10, minWidth: 0, ...style }} {...rest}>
      {yTicks.length ? (
        <div style={{ display: "flex", flexDirection: "column", justifyContent: "space-between", height, font: "var(--type-caption)", color: "var(--chart-axis)", flex: "none", textAlign: "right" }}>
          {yTicks.map((t) => <span key={t}>{t}</span>)}
        </div>
      ) : null}
      <div style={{ flex: 1, minWidth: 0 }}>
        <svg viewBox={`0 0 ${W} ${height}`} preserveAspectRatio="none" style={{ width: "100%", height, display: "block", overflow: "visible" }}>
          <defs>
            <linearGradient id="fxArea" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="var(--chart-1)" stopOpacity="0.26" />
              <stop offset="100%" stopColor="var(--chart-1)" stopOpacity="0" />
            </linearGradient>
          </defs>
          {yTicks.map((t, i) => (
            <line key={i} x1="0" x2={W} y1={(i * height) / Math.max(1, yTicks.length - 1)} y2={(i * height) / Math.max(1, yTicks.length - 1)} stroke="var(--chart-grid)" strokeWidth="1" vectorEffect="non-scaling-stroke" />
          ))}
          {series.map((s, si) => {
            const { d, pts } = path(s.points, W, height - 10);
            return (
              <g key={si}>
                {fill && s.fill !== false ? <path d={`${d} L ${W},${height} L 0,${height} Z`} fill="url(#fxArea)" /> : null}
                <path d={d} fill="none" stroke={s.color || "var(--chart-1)"} strokeWidth={s.width || 2} vectorEffect="non-scaling-stroke" strokeLinecap="round" />
                {marker != null && si === 0 ? (
                  <g>
                    <line x1={pts[marker][0]} x2={pts[marker][0]} y1={pts[marker][1]} y2={height} stroke="var(--chart-1)" strokeWidth="1" vectorEffect="non-scaling-stroke" strokeDasharray="3 3" />
                    <circle cx={pts[marker][0]} cy={pts[marker][1]} r="4.5" fill="var(--chart-1)" stroke="var(--white)" strokeWidth="2" />
                  </g>
                ) : null}
              </g>
            );
          })}
        </svg>
        {xLabels.length ? (
          <div style={{ display: "flex", marginTop: 6 }}>
            {xLabels.map((l, i) => <span key={i} style={{ flex: 1, textAlign: "center", font: "var(--type-caption)", color: "var(--chart-axis)" }}>{l}</span>)}
          </div>
        ) : null}
      </div>
    </div>
  );
}

export function Sparkline({ points = [], width = 96, height = 28, color = "var(--chart-1)", style }) {
  const { d } = path(points, width, height - 4);
  return (
    <svg width={width} height={height} style={{ display: "block", ...style }}>
      <path d={d} fill="none" stroke={color} strokeWidth="1.75" strokeLinecap="round" />
    </svg>
  );
}
