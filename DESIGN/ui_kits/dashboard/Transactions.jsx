(() => {
const { Card, StatTile, Badge, Button, IconButton, Select, Input, Checkbox, DataTable, TransactionCell, Amount,
  AreaChart, CreditCardItem, ActionTile, Icon } = window.UpscaleHubDesignSystem_b4eac1;

function Transactions() {
  const [selected, setSelected] = React.useState([]);
  const [query, setQuery] = React.useState("");
  const [cat, setCat] = React.useState("All Category");
  const rows = window.FX.transactions.filter((r) =>
    (cat === "All Category" || r.cat === cat) &&
    (query === "" || r.name.toLowerCase().includes(query.toLowerCase()))
  );
  const cols = [
    { key: "name", label: "Transaction Name", sortable: true, render: (r) => <TransactionCell icon={r.icon} title={r.name} meta={r.cat} tone={r.tone} /> },
    { key: "acct", label: "Account", sortable: true, render: (r) => (
      <span style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
        <span style={{ display: "grid", placeItems: "center", width: 26, height: 17, borderRadius: 3, background: r.brand === "VISA" ? "var(--blue-600)" : "var(--gray-200)", font: "var(--weight-bold) 6px/1 var(--font-ui)", color: r.brand === "VISA" ? "var(--white)" : "var(--ink-900)" }}>{r.brand}</span>
        <span style={{ color: "var(--gray-600)" }}>{r.acct}</span>
      </span>
    ) },
    { key: "id", label: "Transaction ID", sortable: true, render: (r) => <span style={{ fontFamily: "var(--font-accent)", color: "var(--gray-600)" }}>{r.id}</span> },
    { key: "date", label: "Date & Time", sortable: true, render: (r) => (
      <span style={{ display: "grid", gap: 1 }}>
        <span style={{ fontVariantNumeric: "tabular-nums" }}>{r.date}</span>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{r.time}</span>
      </span>
    ) },
    { key: "amt", label: "Amount", align: "right", sortable: true, render: (r) => <Amount value={r.amt} /> },
    { key: "note", label: "Note", render: (r) => <span style={{ color: "var(--gray-600)" }}>{r.note}</span> },
    { key: "status", label: "Status", align: "right", render: (r) => <Badge tone={r.status === "Completed" ? "success" : "warning"}>{r.status}</Badge> },
  ];
  return (
    <div style={{ display: "grid", gap: "var(--card-gap)" }}>
      <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1fr) minmax(0,1fr) minmax(0,1.5fr)", gap: "var(--card-gap)", alignItems: "stretch" }}>
        <Card title="Total Balance" action={<Badge tone="outline" icon="eye">Hidden</Badge>}>
          <div style={{ display: "flex", alignItems: "baseline", gap: 8 }}>
            <span style={{ font: "var(--weight-bold) var(--text-2xl)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>$1,750.82</span>
            <Badge tone="success" size="sm">Last 7 days</Badge>
          </div>
          <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
            <Button size="sm" variant="secondary" icon="arrow-left-right" style={{ flex: 1 }}>Transfer Funds</Button>
            <Button size="sm" variant="brand" icon="plus" style={{ flex: 1 }}>Fund Request</Button>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(3,1fr)", gap: 8, marginTop: 14 }}>
            {[["Total Expense", "$43,000", "danger"], ["Total Savings", "$56,000", "success"], ["Total Income", "$78,000", "success"]].map(([l, v, t]) => (
              <div key={l} style={{ display: "grid", gap: 4, padding: 10, background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-sm)" }}>
                <Icon name={t === "danger" ? "trending-down" : "trending-up"} size={13} color={t === "danger" ? "var(--red-600)" : "var(--status-success-fg)"} />
                <span style={{ font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>{v}</span>
                <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{l}</span>
              </div>
            ))}
          </div>
        </Card>

        <Card title="Your Cards" action={<IconButton icon="plus" label="Add card" size="sm" variant="bare" />}>
          <div style={{ display: "grid", gap: 8 }}>
            {window.FX.cards.map((c, i) => <CreditCardItem key={i} {...c} selected={i === 0} />)}
            <div style={{ display: "flex", gap: 8 }}>
              <ActionTile icon="plus" label="Top Up" style={{ flex: 1 }} />
              <ActionTile icon="circle-dollar-sign" label="Transfer" style={{ flex: 1 }} />
              <ActionTile icon="credit-card" label="Payment" style={{ flex: 1 }} />
            </div>
          </div>
        </Card>

        <Card title="Cash Flow" menu>
          <div style={{ display: "flex", gap: 22, marginBottom: 12 }}>
            <span style={{ display: "grid", gap: 2 }}>
              <span style={{ display: "inline-flex", alignItems: "center", gap: 6, font: "var(--type-caption)", color: "var(--text-muted)" }}>
                <Icon name="circle-arrow-down" size={13} color="var(--green-700)" />Income
              </span>
              <span style={{ font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>$5,772.13</span>
            </span>
            <span style={{ display: "grid", gap: 2 }}>
              <span style={{ display: "inline-flex", alignItems: "center", gap: 6, font: "var(--type-caption)", color: "var(--text-muted)" }}>
                <Icon name="circle-arrow-up" size={13} color="var(--ink-900)" />Expenses
              </span>
              <span style={{ font: "var(--weight-bold) var(--text-md)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>$881.90</span>
            </span>
            <span style={{ marginLeft: "auto", display: "grid", gap: 2, padding: "8px 10px", background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-sm)" }}>
              <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Monthly</span>
              <span style={{ display: "flex", alignItems: "baseline", gap: 6 }}>
                <b style={{ font: "var(--weight-bold) var(--text-sm)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>$8,903</b>
                <span style={{ font: "var(--type-caption)", color: "var(--status-success-fg)" }}>+1.9%</span>
              </span>
              <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Compared to $8,441 last month</span>
            </span>
          </div>
          <AreaChart height={150}
            series={[{ points: window.FX.cashflowLine, color: "var(--green-500)" }, { points: window.FX.cashflowLine2, color: "var(--ink-900)", fill: false, width: 1.6 }]}
            yTicks={["$50", "$40", "$30", "$20", "$10", "$0"]} xLabels={window.MONTHS} marker={6} />
        </Card>
      </div>

      <Card title="Recent Transactions" padding={0} style={{ padding: "16px 0 0" }} bodyStyle={{ padding: 0 }}
        action={<div style={{ display: "flex", alignItems: "center", gap: 8, paddingRight: 16 }}>
          <Select options={["1–30 September 2028", "1–31 August 2028"]} value="1–30 September 2028" />
          <Button size="sm" icon="download">Download</Button>
        </div>}>
        <div style={{ display: "flex", alignItems: "center", gap: 10, padding: "0 16px 14px" }}>
          <Input style={{ width: 240 }} icon="search" placeholder="Search transaction" value={query} onChange={(e) => setQuery(e.target.value)} />
          <Select variant="field" width={150} options={["All Category", "Investments", "Food & Dining", "Utilities", "Services", "Shopping", "Income"]} value={cat} onChange={setCat} />
          <Select variant="field" width={140} options={["All Account", "Platinum Plus Visa", "Freedom Mastercard"]} value="All Account" />
          {selected.length ? (
            <span style={{ marginLeft: "auto", display: "inline-flex", alignItems: "center", gap: 10 }}>
              <span style={{ font: "var(--type-caption)", color: "var(--text-muted)" }}>{selected.length} selected</span>
              <Button size="sm" variant="ghost" icon="tag">Categorise</Button>
              <Button size="sm" variant="danger" icon="trash-2">Delete</Button>
            </span>
          ) : null}
        </div>
        <DataTable columns={cols} rows={rows} selectable selected={selected} onSelect={setSelected} />
      </Card>
    </div>
  );
}

Object.assign(window, { Transactions });

})();
