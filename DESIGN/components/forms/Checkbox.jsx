import React from "react";
import { Icon } from "../core/Icon.jsx";

export function Checkbox({ checked = false, indeterminate = false, onChange, label, disabled = false, style, ...rest }) {
  const on = checked || indeterminate;
  const box = (
    <span
      style={{
        display: "grid", placeItems: "center", flex: "none", width: 16, height: 16,
        borderRadius: "var(--radius-xs)",
        background: on ? "var(--ink-900)" : "var(--white)",
        border: `1px solid ${on ? "var(--ink-900)" : "var(--border-strong)"}`,
        transition: "var(--transition-control)",
      }}
    >
      {indeterminate ? <span style={{ width: 8, height: 1.5, background: "var(--white)", borderRadius: 1 }} />
        : checked ? <Icon name="check" size={11} color="var(--white)" /> : null}
    </span>
  );
  return (
    <label
      onClick={(e) => { if (disabled) return; e.preventDefault(); onChange && onChange(!checked); }}
      style={{ display: "inline-flex", alignItems: "center", gap: 8, cursor: disabled ? "not-allowed" : "pointer", opacity: disabled ? 0.45 : 1, ...style }}
      {...rest}
    >
      {box}
      {label ? <span style={{ font: "var(--type-body-sm)", color: "var(--ink-900)" }}>{label}</span> : null}
    </label>
  );
}
