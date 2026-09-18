import React from "react";
import { Icon } from "../core/Icon.jsx";

export function SearchField({
  placeholder = "Search anything", value, onChange, shortcut = ["\u2318", "F"],
  width = 300, size = "md", style, ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const h = size === "sm" ? "var(--control-height-sm)" : "var(--control-height)";
  return (
    <div
      style={{
        display: "flex", alignItems: "center", gap: 8, width, height: h, padding: "0 6px 0 12px",
        background: "var(--white)", borderRadius: "var(--radius-pill)",
        border: `1px solid ${focus ? "var(--border-brand)" : "var(--border-subtle)"}`,
        boxShadow: focus ? "var(--ring-focus)" : "var(--shadow-xs)",
        transition: "var(--transition-control)", ...style,
      }}
      {...rest}
    >
      <Icon name="search" size={15} color="var(--gray-400)" />
      <input
        value={value} onChange={onChange} placeholder={placeholder}
        onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
        style={{ flex: 1, minWidth: 0, border: "none", outline: "none", background: "transparent", font: "var(--type-body-sm)", color: "var(--ink-900)" }}
      />
      {shortcut ? (
        <span style={{ display: "flex", gap: 3 }}>
          {shortcut.map((k) => (
            <kbd key={k} style={{
              display: "grid", placeItems: "center", minWidth: 20, height: 20, padding: "0 4px",
              background: "var(--gray-50)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-xs)",
              font: "var(--weight-medium) var(--text-2xs)/1 var(--font-accent)", color: "var(--gray-500)",
            }}>{k}</kbd>
          ))}
        </span>
      ) : null}
    </div>
  );
}
