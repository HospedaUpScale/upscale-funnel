import React from "react";

const SIZES = { xs: 20, sm: 26, md: 32, lg: 40, xl: 56 };

export function Avatar({ src, name = "", size = "md", ring = false, status, style, ...rest }) {
  const px = SIZES[size] || SIZES.md;
  const initials = name.split(" ").filter(Boolean).slice(0, 2).map((w) => w[0]).join("").toUpperCase();
  return (
    <span style={{ position: "relative", display: "inline-block", flex: "none", ...style }} {...rest}>
      <span
        style={{
          display: "grid", placeItems: "center", width: px, height: px, overflow: "hidden",
          borderRadius: "var(--radius-pill)", background: "var(--gradient-orb)",
          color: "var(--white)", font: `var(--weight-semibold) ${Math.round(px * 0.36)}px/1 var(--font-ui)`,
          boxShadow: ring ? "0 0 0 2px var(--white), 0 0 0 3.5px var(--blue-600)" : "none",
        }}
      >
        {src ? <img src={src} alt={name} style={{ width: "100%", height: "100%", objectFit: "cover" }} /> : initials}
      </span>
      {status ? (
        <span style={{
          position: "absolute", right: -1, bottom: -1, width: Math.max(7, px * 0.26), height: Math.max(7, px * 0.26),
          borderRadius: "var(--radius-pill)", boxShadow: "0 0 0 2px var(--white)",
          background: status === "online" ? "var(--green-600)" : status === "busy" ? "var(--red-600)" : "var(--gray-400)",
        }} />
      ) : null}
    </span>
  );
}
