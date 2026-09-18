import React from "react";

export function MetricCard({ eyebrow = "Metric", value, description, filled = 0, total = 12, tone = "neutral", style, ...rest }) {
  const brand = tone === "brand";
  return (
    <div
      style={{
        display: "grid", gap: 14, justifyItems: "center", textAlign: "center", padding: "26px 24px",
        borderRadius: "var(--radius-3xl)",
        background: brand ? "var(--surface-brand)" : "linear-gradient(160deg,#F2F2F2 0%,#E4E4E4 100%)",
        boxShadow: brand ? "var(--shadow-brand)" : "var(--shadow-xs)", ...style,
      }}
      {...rest}
    >
      <span style={{ font: "var(--weight-medium) var(--text-sm)/1 var(--font-ui)", color: brand ? "rgba(255,255,255,.82)" : "var(--gray-600)" }}>{eyebrow}</span>
      <span style={{ font: "var(--weight-bold) var(--text-5xl)/1 var(--font-display)", color: brand ? "var(--white)" : "var(--ink-900)", letterSpacing: "var(--tracking-tight)" }}>{value}</span>
      <p style={{ margin: 0, maxWidth: 260, font: "var(--type-body-sm)", color: brand ? "rgba(255,255,255,.82)" : "var(--text-muted)" }}>{description}</p>
      <span style={{ display: "flex", gap: 5 }}>
        {Array.from({ length: total }).map((_, i) => (
          <span key={i} style={{
            width: 14, height: 14, borderRadius: "var(--radius-pill)",
            border: `2.5px solid ${i < filled ? (brand ? "var(--white)" : "var(--blue-600)") : (brand ? "rgba(255,255,255,.34)" : "var(--white)")}`,
            background: "transparent",
          }} />
        ))}
      </span>
    </div>
  );
}
