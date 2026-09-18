import React from "react";
import { Icon } from "../core/Icon.jsx";

export function SuggestionCard({ title, body, cta = "Learn more", onClick, icon, style, ...rest }) {
  const [hover, setHover] = React.useState(false);
  return (
    <div
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
      style={{
        display: "grid", gap: 6, padding: 14, cursor: "pointer",
        background: "var(--white)",
        border: `1px solid ${hover ? "var(--blue-300)" : "var(--border-subtle)"}`,
        borderRadius: "var(--radius-md)",
        boxShadow: hover ? "var(--shadow-sm)" : "none",
        transition: "var(--transition-control)", ...style,
      }}
      onClick={onClick}
      {...rest}
    >
      <span style={{ display: "flex", alignItems: "center", gap: 7 }}>
        {icon ? <Icon name={icon} size={14} color="var(--blue-600)" /> : null}
        <span style={{ font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>{title}</span>
      </span>
      <p style={{ margin: 0, font: "var(--type-caption)", color: "var(--text-muted)" }}>{body}</p>
      <span style={{ display: "inline-flex", alignItems: "center", gap: 5, marginTop: 2, font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)", color: "var(--ink-900)" }}>
        {cta}
        <Icon name="arrow-right" size={12} style={{ transform: hover ? "translateX(2px)" : "none", transition: "transform var(--duration-fast) var(--ease-standard)" }} />
      </span>
    </div>
  );
}
