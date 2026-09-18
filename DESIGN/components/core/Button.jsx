import React from "react";
import { Icon } from "./Icon.jsx";

const SIZES = {
  sm: { height: "var(--control-height-sm)", padding: "0 12px", font: "var(--weight-semibold) var(--text-xs)/1 var(--font-ui)", gap: 6, icon: 14 },
  md: { height: "var(--control-height)", padding: "0 16px", font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)", gap: 8, icon: 16 },
  lg: { height: "var(--control-height-lg)", padding: "0 22px", font: "var(--weight-semibold) var(--text-base)/1 var(--font-ui)", gap: 8, icon: 18 },
};

const VARIANTS = {
  primary: { background: "var(--ink-900)", color: "var(--text-on-inverse)", border: "1px solid var(--ink-900)", boxShadow: "var(--shadow-sm)" },
  brand: { background: "var(--surface-brand)", color: "var(--text-on-brand)", border: "1px solid var(--surface-brand)", boxShadow: "var(--shadow-brand)" },
  secondary: { background: "var(--white)", color: "var(--ink-900)", border: "1px solid var(--border-default)", boxShadow: "var(--shadow-xs)" },
  soft: { background: "var(--surface-brand-soft)", color: "var(--blue-700)", border: "1px solid transparent", boxShadow: "none" },
  ghost: { background: "transparent", color: "var(--text-muted)", border: "1px solid transparent", boxShadow: "none" },
  danger: { background: "var(--red-100)", color: "var(--red-600)", border: "1px solid transparent", boxShadow: "none" },
};

const HOVER = {
  primary: { background: "var(--ink-800)" },
  brand: { background: "var(--blue-500)" },
  secondary: { background: "var(--surface-hover)" },
  soft: { background: "var(--blue-200)" },
  ghost: { background: "var(--surface-hover)", color: "var(--ink-900)" },
  danger: { background: "#FBDDDE" },
};

export function Button({
  children, variant = "primary", size = "md", icon, iconRight, pill = true,
  disabled = false, fullWidth = false, style, onClick, type = "button", ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const [press, setPress] = React.useState(false);
  const s = SIZES[size] || SIZES.md;
  const v = VARIANTS[variant] || VARIANTS.primary;
  return (
    <button
      type={type}
      disabled={disabled}
      onClick={onClick}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => { setHover(false); setPress(false); }}
      onMouseDown={() => setPress(true)}
      onMouseUp={() => setPress(false)}
      style={{
        display: "inline-flex", alignItems: "center", justifyContent: "center",
        gap: s.gap, height: s.height, padding: s.padding, font: s.font,
        letterSpacing: "var(--tracking-snug)", whiteSpace: "nowrap",
        borderRadius: pill ? "var(--radius-pill)" : "var(--radius-control)",
        cursor: disabled ? "not-allowed" : "pointer",
        transition: "var(--transition-control)",
        width: fullWidth ? "100%" : undefined,
        opacity: disabled ? 0.42 : 1,
        transform: press && !disabled ? "scale(.975)" : "scale(1)",
        ...v,
        ...(hover && !disabled ? HOVER[variant] : null),
        ...style,
      }}
      {...rest}
    >
      {icon ? <Icon name={icon} size={s.icon} /> : null}
      {children}
      {iconRight ? <Icon name={iconRight} size={s.icon} /> : null}
    </button>
  );
}
