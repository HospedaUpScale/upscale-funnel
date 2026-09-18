import React from "react";
import { Icon } from "../core/Icon.jsx";

export function Select({
  options = [], value, onChange, label, size = "sm", variant = "quiet",
  leading, width, disabled = false, style, ...rest
}) {
  const [open, setOpen] = React.useState(false);
  const norm = options.map((o) => (typeof o === "string" ? { value: o, label: o } : o));
  const current = norm.find((o) => o.value === value) || norm[0] || { label: "" };
  const h = size === "sm" ? "var(--control-height-sm)" : "var(--control-height)";
  const quiet = variant === "quiet";
  return (
    <div style={{ position: "relative", display: "inline-grid", gap: 6, width, ...style }} {...rest}>
      {label ? <span style={{ font: "var(--type-label)", color: "var(--text-muted)" }}>{label}</span> : null}
      <button
        type="button" disabled={disabled} onClick={() => setOpen((o) => !o)}
        style={{
          display: "inline-flex", alignItems: "center", justifyContent: "space-between", gap: 8,
          height: h, padding: "0 10px 0 12px", width: width ? "100%" : undefined,
          background: quiet ? "var(--white)" : "var(--gray-50)",
          border: `1px solid ${open ? "var(--border-brand)" : "var(--border-subtle)"}`,
          borderRadius: quiet ? "var(--radius-pill)" : "var(--radius-control)",
          font: "var(--weight-medium) var(--text-xs)/1 var(--font-ui)", color: "var(--ink-900)",
          cursor: disabled ? "not-allowed" : "pointer", transition: "var(--transition-control)", whiteSpace: "nowrap",
        }}
      >
        <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
          {leading}
          {current.label}
        </span>
        <Icon name="chevron-down" size={14} color="var(--gray-400)" style={{ transform: open ? "rotate(180deg)" : "none", transition: "transform var(--duration-fast) var(--ease-standard)" }} />
      </button>
      {open ? (
        <div
          style={{
            position: "absolute", top: "calc(100% + 6px)", right: 0, zIndex: 40, minWidth: "100%",
            padding: 4, background: "var(--white)", border: "1px solid var(--border-subtle)",
            borderRadius: "var(--radius-md)", boxShadow: "var(--shadow-lg)",
          }}
        >
          {norm.map((o) => (
            <button
              key={o.value} type="button"
              onClick={() => { onChange && onChange(o.value); setOpen(false); }}
              style={{
                display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12, width: "100%",
                padding: "7px 10px", border: "none", background: o.value === current.value ? "var(--surface-brand-soft)" : "transparent",
                borderRadius: "var(--radius-sm)", cursor: "pointer", textAlign: "left",
                font: "var(--weight-medium) var(--text-xs)/1.2 var(--font-ui)", color: "var(--ink-900)",
              }}
            >
              {o.label}
              {o.value === current.value ? <Icon name="check" size={13} color="var(--ink-900)" /> : null}
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
