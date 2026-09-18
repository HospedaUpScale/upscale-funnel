import React from "react";
import { Icon } from "../core/Icon.jsx";

export function CreditCardItem({
  label = "Personal", brand = "VISA", last4 = "8744", selected = false,
  tone = "blue", onSelect, onMenu, style, ...rest
}) {
  const faces = {
    blue: "linear-gradient(135deg,#4A63E8 0%,#0229C4 100%)",
    gray: "linear-gradient(135deg,#D8D8D8 0%,#B4B4B4 100%)",
    ink: "linear-gradient(135deg,#0A2469 0%,#05153F 100%)",
  };
  return (
    <div
      onClick={onSelect}
      style={{
        display: "flex", alignItems: "center", gap: 10, padding: "8px 10px", cursor: "pointer",
        background: selected ? "var(--blue-50)" : "var(--white)",
        border: `1px solid ${selected ? "var(--blue-200)" : "var(--border-subtle)"}`,
        borderRadius: "var(--radius-md)", transition: "var(--transition-control)", ...style,
      }}
      {...rest}
    >
      <span style={{
        display: "grid", placeItems: "center", flex: "none", width: 15, height: 15,
        borderRadius: "var(--radius-pill)", background: "var(--white)",
        border: `1.5px solid ${selected ? "var(--blue-600)" : "var(--border-strong)"}`,
      }}>
        {selected ? <span style={{ width: 7, height: 7, borderRadius: "var(--radius-pill)", background: "var(--blue-600)" }} /> : null}
      </span>
      <span style={{ display: "grid", placeItems: "start", width: 36, height: 24, flex: "none", padding: 4, borderRadius: "var(--radius-xs)", background: faces[tone] }}>
        <span style={{ font: "var(--weight-bold) 6px/1 var(--font-ui)", color: tone === "gray" ? "var(--ink-900)" : "var(--white)", letterSpacing: "0.06em" }}>{brand}</span>
      </span>
      <span style={{ display: "grid", gap: 1, flex: 1, minWidth: 0 }}>
        <span style={{ font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>{label}</span>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)", fontFamily: "var(--font-accent)" }}>&bull;&bull;&bull;&bull; {last4}</span>
      </span>
      <button type="button" aria-label="Card options" onClick={(e) => { e.stopPropagation(); onMenu && onMenu(); }} style={{ border: "none", background: "transparent", cursor: "pointer", padding: 4, display: "grid", placeItems: "center" }}>
        <Icon name="more-horizontal" size={15} color="var(--gray-400)" />
      </button>
    </div>
  );
}

export function ActionTile({ icon = "plus", label, onClick, style, ...rest }) {
  const [hover, setHover] = React.useState(false);
  return (
    <button
      type="button" onClick={onClick}
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
      style={{
        display: "grid", justifyItems: "center", gap: 5, padding: "10px 8px", minWidth: 64,
        background: hover ? "var(--surface-hover)" : "var(--white)",
        border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-md)",
        cursor: "pointer", transition: "var(--transition-control)", ...style,
      }}
      {...rest}
    >
      <span style={{ display: "grid", placeItems: "center", width: 26, height: 26, borderRadius: "var(--radius-pill)", background: "var(--gray-50)" }}>
        <Icon name={icon} size={14} color="var(--ink-900)" />
      </span>
      <span style={{ font: "var(--type-caption)", color: "var(--gray-600)" }}>{label}</span>
    </button>
  );
}
