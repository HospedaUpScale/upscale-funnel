import React from "react";
import { Icon } from "../core/Icon.jsx";
import { Button } from "../core/Button.jsx";
import { IconButton } from "../core/IconButton.jsx";

export function AssistantOrb({ size = 72, style }) {
  return <span style={{ display: "block", width: size, height: size, borderRadius: "var(--radius-pill)", background: "var(--gradient-orb)", filter: "blur(.2px)", boxShadow: "var(--shadow-brand)", ...style }} />;
}

export function AssistantComposer({
  placeholder = "Ask anything...", value, onChange, onSend,
  chips = [], models = ["Finance", "Choose Model"], compact = false, style, ...rest
}) {
  return (
    <div
      style={{
        display: "grid", gap: 10, padding: 12,
        background: "var(--white)", border: "1px solid var(--border-subtle)",
        borderRadius: "var(--radius-lg)", boxShadow: "var(--shadow-sm)", ...style,
      }}
      {...rest}
    >
      <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
        <Icon name="wand-sparkles" size={15} color="var(--gray-400)" />
        <input
          value={value} onChange={onChange} placeholder={placeholder}
          style={{ flex: 1, minWidth: 0, border: "none", outline: "none", background: "transparent", font: "var(--type-body-sm)", color: "var(--ink-900)" }}
        />
      </div>
      {chips.length ? (
        <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
          {chips.map((c) => (
            <span key={c} style={{ display: "inline-flex", alignItems: "center", gap: 5, height: 24, padding: "0 10px", background: "var(--gray-50)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-pill)", font: "var(--type-caption)", color: "var(--gray-600)" }}>
              <Icon name="sparkles" size={11} color="var(--blue-600)" />
              {c}
            </span>
          ))}
        </div>
      ) : null}
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 8 }}>
        <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <IconButton icon="paperclip" label="Attach" size="sm" variant="bare" />
          <IconButton icon="mic" label="Voice" size="sm" variant="bare" />
          <IconButton icon="image" label="Image" size="sm" variant="bare" />
          {!compact ? models.map((m) => (
            <span key={m} style={{ display: "inline-flex", alignItems: "center", gap: 4, height: 24, padding: "0 9px", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-pill)", font: "var(--type-caption)", color: "var(--gray-600)" }}>
              {m}
              <Icon name="chevron-down" size={11} color="var(--gray-400)" />
            </span>
          )) : null}
        </div>
        <Button variant="brand" size="sm" icon="arrow-up" onClick={onSend}>Send</Button>
      </div>
    </div>
  );
}
