import React from "react";

export function Radio({ checked = false, onChange, label, description, disabled = false, style, ...rest }) {
  return (
    <label
      onClick={() => { if (!disabled && onChange) onChange(true); }}
      style={{ display: "inline-flex", alignItems: description ? "flex-start" : "center", gap: 10, cursor: disabled ? "not-allowed" : "pointer", opacity: disabled ? 0.45 : 1, ...style }}
      {...rest}
    >
      <span
        style={{
          display: "grid", placeItems: "center", flex: "none", width: 16, height: 16, marginTop: description ? 2 : 0,
          borderRadius: "var(--radius-pill)", background: "var(--white)",
          border: `1.5px solid ${checked ? "var(--blue-600)" : "var(--border-strong)"}`,
          transition: "var(--transition-control)",
        }}
      >
        {checked ? <span style={{ width: 8, height: 8, borderRadius: "var(--radius-pill)", background: "var(--blue-600)" }} /> : null}
      </span>
      {label ? (
        <span style={{ display: "grid", gap: 2 }}>
          <span style={{ font: "var(--weight-medium) var(--text-sm)/1.3 var(--font-ui)", color: "var(--ink-900)" }}>{label}</span>
          {description ? <span style={{ font: "var(--type-caption)", color: "var(--text-muted)" }}>{description}</span> : null}
        </span>
      ) : null}
    </label>
  );
}
