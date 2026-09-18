Recent Transactions table: 34px grey header, 52px rows, hairline dividers, hover tint.

<DataTable columns={cols} rows={rows} selectable selected={sel} onSelect={setSel} />

Compose cells with `TransactionCell` (icon chip + title + category meta), `Amount` (red when negative) and `Badge` for status. Header labels are 11px medium grey with a chevrons-up-down affordance when sortable.
