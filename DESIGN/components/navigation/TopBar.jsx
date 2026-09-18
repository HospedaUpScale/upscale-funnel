import React from "react";
import { IconButton } from "../core/IconButton.jsx";
import { Avatar } from "../core/Avatar.jsx";
import { SearchField } from "../forms/SearchField.jsx";

export function TopBar({
  title, subtitle, actions, searchWidth = 300, onSearch, avatar, userName = "Jenny Wilson",
  sticky = true, style, ...rest
}) {
  return (
    <header
      style={{
        position: sticky ? "sticky" : "static", top: 0, zIndex: 30,
        display: "flex", alignItems: "center", gap: 16, minHeight: "var(--topbar-height)",
        padding: "0 16px", background: "var(--surface-glass)",
        backdropFilter: "var(--blur-glass)", WebkitBackdropFilter: "var(--blur-glass)",
        borderBottom: "1px solid var(--border-subtle)", ...style,
      }}
      {...rest}
    >
      <div style={{ display: "grid", gap: 1, minWidth: 0, flex: 1 }}>
        <span style={{ font: "var(--weight-semibold) var(--text-sm)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>{title}</span>
        {subtitle ? <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>&ldquo;{subtitle}&rdquo;</span> : null}
      </div>
      <div style={{ display: "flex", alignItems: "center", gap: 8, flex: "none" }}>
        {actions}
        <SearchField width={searchWidth} onChange={onSearch} />
        <IconButton icon="settings" label="Settings" variant="bare" />
        <IconButton icon="circle-help" label="Help" variant="bare" />
        <Avatar src={avatar} name={userName} size="md" ring />
      </div>
    </header>
  );
}
