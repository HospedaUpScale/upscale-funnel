(() => {
const { Card, Badge, Button, IconButton, Select, Tabs, ProgressBar, DonutChart, CategoryLegend,
  AreaChart, DataTable, TransactionCell, Amount, CreditCardItem, ActionTile, Icon, Input } = window.UpscaleHubDesignSystem_b4eac1;

function Wallet() {
  const [card, setCard] = React.useState(0);
  const [walletTab, setWalletTab] = React.useState("wallet");
  const active = window.FX.cards[card];
  const cols = [
    { key: "name", label: "Transaction Name", sortable: true, render: (r) => <TransactionCell icon={r.icon} title={r.name} meta={r.cat} tone={r.tone} /> },
    { key: "id", label: "Transaction ID", render: (r) => <span style={{ fontFamily: "var(--font-accent)", color: "var(--gray-600)" }}>{r.id}</span> },
    { key: "date", label: "Date & Time", render: (r) => (
      <span style={{ display: "grid", gap: 1 }}>
        <span style={{ fontVariantNumeric: "tabular-nums" }}>{r.date}</span>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{r.time}</span>
      </span>
    ) },
    { key: "amt", label: "Amount", align: "right", render: (r) => <Amount value={r.amt} /> },
    { key: "status", label: "Status", align: "right", render: (r) => <Badge tone={r.status === "Completed" ? "success" : "warning"}>{r.status}</Badge> },
  ];
  return (
    <div style={{ display: "grid", gap: "var(--card-gap)" }}>
      <div style={{ display: "flex", alignItems: "flex-end", justifyContent: "space-between", gap: 16, padding: "2px 2px 0" }}>
        <div style={{ display: "grid", gap: 3 }}>
          <span style={{ font: "var(--weight-semibold) var(--text-md)/1.2 var(--font-ui)", color: "var(--ink-900)" }}>
            Overview / <span style={{ fontStyle: "italic", fontWeight: 500 }}>Balance Details</span>
          </span>
          <span style={{ font: "var(--weight-bold) var(--text-2xl)/1.1 var(--font-ui)", fontVariantNumeric: "tabular-nums", color: "var(--ink-900)" }}>$542.25.00 USD</span>
          <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Your total balance estimate in USD at 2024-09-16 12:20</span>
        </div>
        <Button size="sm" variant="soft" icon="settings-2">Manage Balance</Button>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1fr) minmax(0,1fr) minmax(0,1.6fr)", gap: "var(--card-gap)", alignItems: "stretch" }}>
        <Card title="Your Cards" action={<IconButton icon="plus" label="Add card" size="sm" variant="bare" />}>
          <div style={{ display: "grid", gap: 8 }}>
            {window.FX.cards.map((c, i) => (
              <CreditCardItem key={i} {...c} selected={i === card} onSelect={() => setCard(i)} />
            ))}
          </div>
        </Card>

        <Card>
          <div style={{ display: "flex", gap: 8, marginBottom: 14 }}>
            <ActionTile icon="plus" label="Top Up" style={{ flex: 1 }} />
            <ActionTile icon="circle-dollar-sign" label="Transfer" style={{ flex: 1 }} />
            <ActionTile icon="credit-card" label="Payment" style={{ flex: 1 }} />
          </div>
          <div style={{ display: "grid", gap: 4, marginBottom: 12 }}>
            <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Card Number</span>
            <span style={{ font: "var(--weight-medium) var(--text-base)/1 var(--font-accent)", color: "var(--ink-900)" }}>5582 5574 8376 5487</span>
          </div>
          <div style={{ display: "flex", gap: 18, marginBottom: 14 }}>
            {[["Expiry Date", "08/25"], ["CVC", "40"]].map(([l, v]) => (
              <span key={l} style={{ display: "grid", gap: 3 }}>
                <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{l}</span>
                <span style={{ font: "var(--weight-medium) var(--text-sm)/1 var(--font-accent)" }}>{v}</span>
              </span>
            ))}
            <span style={{ display: "grid", gap: 3 }}>
              <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Status</span>
              <Badge tone="inverse" size="sm">Active</Badge>
            </span>
          </div>
          <div style={{ paddingTop: 12, borderTop: "1px solid var(--border-subtle)" }}>
            <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 8 }}>
              <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)" }}>Spending Limits</span>
              <IconButton icon="more-vertical" label="Limit options" size="sm" variant="bare" />
            </div>
            <ProgressBar height={10} value={45} label="$4,500.00 spent of $10,000.00" valueLabel="45%" />
          </div>
        </Card>

        <Card title="My Wallets" action={<Select options={["Monthly", "Weekly", "Daily"]} value="Monthly" />}>
          <Tabs style={{ marginBottom: 14 }} items={[{ id: "wallet", label: "Wallet" }, { id: "card", label: "Card Transaction" }, { id: "inv", label: "Investment" }]} value={walletTab} onChange={setWalletTab} />
          <AreaChart height={176} series={[{ points: window.FX.walletBalance, color: "var(--chart-1)" }]}
            yTicks={["$100k", "$80k", "$60k", "$40k", "$20k", "0"]} xLabels={["1", "2", "3", "4", "5", "6", "7", "8"]} marker={16} />
        </Card>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr)", gap: "var(--card-gap)", alignItems: "stretch" }}>
        <Card title="Transactions" padding={0} style={{ padding: "16px 0 0" }} bodyStyle={{ padding: 0 }}
          action={<div style={{ paddingRight: 16 }}><Select options={["This Month", "This Year"]} value="This Month" /></div>}>
          <DataTable columns={cols} rows={window.FX.transactions.slice(0, 5)} dense />
        </Card>

        <Card title="All Expenses" action={<Select options={["This Month", "This Year"]} value="This Month" />}>
          <div style={{ display: "flex", gap: 10, marginBottom: 14 }}>
            {window.FX.expenseTotals.map((t) => (
              <span key={t.label} style={{ display: "grid", gap: 3 }}>
                <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{t.label}</span>
                <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums" }}>{t.value}</span>
              </span>
            ))}
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
            <DonutChart size={118} thickness={17} label="Platform" value="Rp225.000"
              segments={[{ value: 45, color: "var(--chart-2)" }, { value: 25, color: "var(--chart-1)" }, { value: 18, color: "var(--chart-4)" }, { value: 12, color: "var(--chart-5)" }]} />
            <CategoryLegend style={{ flex: 1 }} items={[
              { label: "Shopping", value: "45%", color: "var(--chart-2)" },
              { label: "Platform", value: "25%", color: "var(--chart-1)" },
              { label: "Food & Drinks", value: "18%", color: "var(--chart-4)" },
              { label: "Other Expenses", value: "12%", color: "var(--chart-5)" },
            ]} />
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 10, marginTop: 14, paddingTop: 12, borderTop: "1px solid var(--border-subtle)" }}>
            <span style={{ display: "grid", placeItems: "center", width: 30, height: 30, borderRadius: "var(--radius-pill)", background: "var(--gray-50)", border: "1px solid var(--border-subtle)" }}>
              <Icon name="coins" size={14} color="var(--ink-900)" />
            </span>
            <span style={{ display: "grid", gap: 2 }}>
              <span style={{ font: "var(--weight-semibold) var(--text-sm)/1 var(--font-ui)" }}>Rp70.000 &ndash; Rp100.000</span>
              <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Spending limit near</span>
            </span>
          </div>
        </Card>

        <Card title="Convert" menu>
          <div style={{ display: "grid", gap: 10 }}>
            <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <Input style={{ flex: 1 }} label="You send" value="$200.00" />
              <Select style={{ marginTop: 18 }} options={["USD", "EUR", "GBP"]} value="USD" leading={<span>&#127482;&#127480;</span>} />
            </div>
            <div style={{ display: "grid", gap: 7, padding: 12, background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-md)" }}>
              <span style={{ font: "var(--type-caption)", color: "var(--gray-600)" }}>You&rsquo;ve <b style={{ color: "var(--ink-900)" }}>$35,478.00</b> available balance</span>
              {[["\u2212 $2.23", "Our fees"], ["= $197.77", "Amount converted"], ["\u00D7 0.778786", "Live rate"]].map(([a, b]) => (
                <span key={b} style={{ display: "flex", justifyContent: "space-between", font: "var(--type-caption)", color: "var(--gray-600)" }}>
                  <b style={{ color: "var(--ink-900)", fontFamily: "var(--font-accent)" }}>{a}</b>{b}
                </span>
              ))}
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <Input style={{ flex: 1 }} label="They get" value="£154.02" />
              <Select style={{ marginTop: 18 }} options={["GBP", "EUR", "USD"]} value="GBP" leading={<span>&#127468;&#127463;</span>} />
            </div>
            <Button variant="brand" pill={false} fullWidth>Continue</Button>
          </div>
        </Card>
      </div>
    </div>
  );
}

Object.assign(window, { Wallet });

})();
