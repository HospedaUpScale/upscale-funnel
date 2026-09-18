import React from "react";
import { Icon } from "../core/Icon.jsx";

export function Input({
  label, hint, error, icon, suffix, size = "md", value, onChange, placeholder,
  type = "text", disabled = false, style, inputStyle, ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const h = size === "sm" ? "var(--control-height-sm)" : size === "lg" ? "var(--control-height-lg)" : "var(--control-height)";
  return (
    <label style={{ display: "grid", gap: 6, ...style }}>
      {label ? <span style={{ font: "var(--type-label)", color: "var(--text-muted)" }}>{label}</span> : null}
      <span
        style={{
          display: "flex", alignItems: "center", gap: 8, height: h, padding: "0 12px",
          background: disabled ? "var(--gray-50)" : "var(--white)",
          border: `1px solid ${error ? "var(--red-600)" : focus ? "var(--border-brand)" : "var(--border-default)"}`,
          borderRadius: "var(--radius-control)",
          boxShadow: focus ? "var(--ring-focus)" : "none",
          transition: "var(--transition-control)",
        }}
      >
        {icon ? <Icon name={icon} size={15} color="var(--gray-400)" /> : null}
        <input
          type={type} value={value} onChange={onChange} placeholder={placeholder} disabled={disabled}
          onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
          style={{
            flex: 1, minWidth: 0, border: "none", outline: "none", background: "transparent",
            font: "var(--type-body-sm)", color: "var(--ink-900)", ...inputStyle,
          }}
          {...rest}
        />
        {suffix ? <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{suffix}</span> : null}
      </span>
      {error || hint ? (
        <span style={{ font: "var(--type-caption)", color: error ? "var(--red-600)" : "var(--text-muted)" }}>{error || hint}</span>
      ) : null}
    </label>
  );
}
