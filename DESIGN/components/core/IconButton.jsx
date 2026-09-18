import React from "react";
import { Icon } from "./Icon.jsx";

const SIZES = { sm: 28, md: 32, lg: 40 };

export function IconButton({
  icon, label, size = "md", variant = "plain", active = false, badge = false,
  disabled = false, onClick, style, ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const px = SIZES[size] || SIZES.md;
  const base = {
    plain: { background: "var(--white)", color: "var(--gray-600)", border: "1px solid var(--border-subtle)" },
    bare: { background: "transparent", color: "var(--gray-500)", border: "1px solid transparent" },
    inverse: { background: "var(--ink-900)", color: "var(--white)", border: "1px solid var(--ink-900)" },
    brand: { background: "var(--surface-brand)", color: "var(--white)", border: "1px solid var(--surface-brand)" },
  }[variant];
  return (
    <button
      type="button" aria-label={label} title={label} disabled={disabled} onClick={onClick}
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
      style={{
        position: "relative", width: px, height: px, display: "inline-grid", placeItems: "center",
        borderRadius: "var(--radius-pill)", cursor: disabled ? "not-allowed" : "pointer",
        transition: "var(--transition-control)", opacity: disabled ? 0.42 : 1,
        ...base,
        ...(active ? { background: "var(--surface-brand-soft)", color: "var(--blue-700)", borderColor: "transparent" } : null),
        ...(hover && !disabled && !active ? { background: variant === "inverse" ? "var(--ink-800)" : variant === "brand" ? "var(--blue-500)" : "var(--surface-hover)", color: variant === "inverse" || variant === "brand" ? undefined : "var(--ink-900)" } : null),
        ...style,
      }}
      {...rest}
    >
      <Icon name={icon} size={Math.round(px * 0.5)} />
      {badge ? <span style={{ position: "absolute", top: 4, right: 4, width: 6, height: 6, borderRadius: "var(--radius-pill)", background: "var(--red-600)", boxShadow: "0 0 0 2px var(--white)" }} /> : null}
    </button>
  );
}
