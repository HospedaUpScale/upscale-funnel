import React from "react";

export function Switch({ checked = false, onChange, label, description, disabled = false, size = "md", style, ...rest }) {
  const w = size === "sm" ? 34 : 42;
  const h = size === "sm" ? 20 : 24;
  const knob = h - 6;
  const control = (
    <button
      type="button" role="switch" aria-checked={checked} disabled={disabled}
      onClick={() => onChange && onChange(!checked)}
      style={{
        position: "relative", flex: "none", width: w, height: h, padding: 0, cursor: disabled ? "not-allowed" : "pointer",
        borderRadius: "var(--radius-pill)", border: "none", opacity: disabled ? 0.45 : 1,
        background: checked ? "var(--blue-600)" : "var(--gray-300)",
        transition: "background-color var(--duration-normal) var(--ease-standard)",
      }}
    >
      <span style={{
        position: "absolute", top: 3, left: checked ? w - knob - 3 : 3, width: knob, height: knob,
        borderRadius: "var(--radius-pill)", background: "var(--white)", boxShadow: "var(--shadow-sm)",
        transition: "left var(--duration-normal) var(--ease-spring)",
      }} />
    </button>
  );
  if (!label) return <span style={style} {...rest}>{control}</span>;
  return (
    <label style={{ display: "flex", alignItems: description ? "flex-start" : "center", gap: 10, cursor: disabled ? "not-allowed" : "pointer", ...style }} {...rest}>
      {control}
      <span style={{ display: "grid", gap: 2 }}>
        <span style={{ font: "var(--weight-medium) var(--text-sm)/1.3 var(--font-ui)", color: "var(--ink-900)" }}>{label}</span>
        {description ? <span style={{ font: "var(--type-caption)", color: "var(--text-muted)" }}>{description}</span> : null}
      </span>
    </label>
  );
}
