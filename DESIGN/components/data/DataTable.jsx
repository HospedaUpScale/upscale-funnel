import React from "react";
import { Icon } from "../core/Icon.jsx";
import { Checkbox } from "../forms/Checkbox.jsx";

export function DataTable({
  columns = [], rows = [], selectable = false, selected = [], onSelect,
  dense = false, emptyLabel = "Nothing here yet", style, ...rest
}) {
  const rowH = dense ? 44 : "var(--row-height)";
  const cell = { padding: "0 12px", verticalAlign: "middle", borderBottom: "1px solid var(--border-subtle)" };
  return (
    <div style={{ width: "100%", overflowX: "auto", ...style }} {...rest}>
      <table style={{ width: "100%", borderCollapse: "collapse", tableLayout: "auto" }}>
        <thead>
          <tr style={{ height: 34, background: "var(--surface-sunken)" }}>
            {selectable ? (
              <th style={{ ...cell, width: 36, borderBottomColor: "var(--border-default)" }}>
                <Checkbox checked={selected.length === rows.length && rows.length > 0} indeterminate={selected.length > 0 && selected.length < rows.length} onChange={() => onSelect && onSelect(selected.length === rows.length ? [] : rows.map((_, i) => i))} />
              </th>
            ) : null}
            {columns.map((c) => (
              <th key={c.key} style={{
                ...cell, textAlign: c.align || "left", borderBottomColor: "var(--border-default)",
                font: "var(--weight-medium) var(--text-2xs)/1 var(--font-ui)", color: "var(--text-subtle)",
                whiteSpace: "nowrap", width: c.width,
              }}>
                <span style={{ display: "inline-flex", alignItems: "center", gap: 4, justifyContent: c.align === "right" ? "flex-end" : "flex-start" }}>
                  {c.label}
                  {c.sortable ? <Icon name="chevrons-up-down" size={11} color="var(--gray-300)" /> : null}
                </span>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 ? (
            <tr><td colSpan={columns.length + (selectable ? 1 : 0)} style={{ ...cell, height: 88, textAlign: "center", font: "var(--type-body-sm)", color: "var(--text-subtle)" }}>{emptyLabel}</td></tr>
          ) : rows.map((row, i) => (
            <tr key={i} style={{ height: rowH, transition: "background-color var(--duration-fast) var(--ease-standard)" }}
              onMouseEnter={(e) => { e.currentTarget.style.background = "var(--surface-hover)"; }}
              onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}>
              {selectable ? (
                <td style={cell}>
                  <Checkbox checked={selected.includes(i)} onChange={() => onSelect && onSelect(selected.includes(i) ? selected.filter((x) => x !== i) : [...selected, i])} />
                </td>
              ) : null}
              {columns.map((c) => (
                <td key={c.key} style={{ ...cell, textAlign: c.align || "left", font: "var(--type-body-sm)", color: "var(--ink-900)" }}>
                  {c.render ? c.render(row) : row[c.key]}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export function TransactionCell({ icon = "receipt", title, meta, tone = "neutral" }) {
  const bg = { income: "var(--green-100)", expense: "var(--red-100)", neutral: "var(--gray-100)" }[tone];
  const fg = { income: "var(--status-success-fg)", expense: "var(--red-600)", neutral: "var(--gray-600)" }[tone];
  return (
    <span style={{ display: "inline-flex", alignItems: "center", gap: 10, minWidth: 0 }}>
      <span style={{ display: "grid", placeItems: "center", width: 28, height: 28, flex: "none", borderRadius: "var(--radius-pill)", background: bg }}>
        <Icon name={icon} size={14} color={fg} />
      </span>
      <span style={{ display: "grid", gap: 1, minWidth: 0 }}>
        <span style={{ font: "var(--weight-medium) var(--text-sm)/1.2 var(--font-ui)", color: "var(--ink-900)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{title}</span>
        {meta ? <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{meta}</span> : null}
      </span>
    </span>
  );
}

export function Amount({ value, positive }) {
  const pos = positive != null ? positive : !String(value).trim().startsWith("-");
  return <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums", color: pos ? "var(--ink-900)" : "var(--red-600)" }}>{value}</span>;
}
