(() => {
const { Card, CardFooterLink, StatTile, BalanceCard, Button, IconButton, Badge, Select, Tabs, ProgressBar,
  BarChart, ChartLegend, DonutChart, CategoryLegend, DataTable, TransactionCell, Amount,
  ExchangePanel, AssistantComposer, AssistantOrb, Icon } = window.UpscaleHubDesignSystem_b4eac1;

function FinanceScore() {
  return (
    <Card title="Finance Score" menu>
      <div style={{ display: "grid", gap: 10 }}>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Finance Quality</span>
        <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", gap: 10 }}>
          <span style={{ font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)", color: "var(--ink-900)" }}>Excellent</span>
          <span style={{ font: "var(--weight-bold) var(--text-xl)/1 var(--font-ui)", color: "var(--ink-900)" }}>92%</span>
        </div>
        <ProgressBar height={16} segments={[{ value: 70, color: "var(--chart-1)" }, { value: 22, color: "var(--chart-2)" }]} />
        <CardFooterLink>Improve my score</CardFooterLink>
      </div>
    </Card>
  );
}

function DashboardHome({ onNavigate }) {
  const [range, setRange] = React.useState("This Year");
  const [txRange, setTxRange] = React.useState("This Month");
  const [statTab, setStatTab] = React.useState("expense");
  const cols = [
    { key: "name", label: "Transaction Name", sortable: true, render: (r) => <TransactionCell icon={r.icon} title={r.name} meta={r.cat} tone={r.tone} /> },
    { key: "acct", label: "Account", sortable: true, render: (r) => (
      <span style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
        <span style={{ display: "grid", placeItems: "center", width: 26, height: 17, borderRadius: 3, background: r.brand === "VISA" ? "var(--blue-600)" : "var(--gray-200)", font: "var(--weight-bold) 6px/1 var(--font-ui)", color: r.brand === "VISA" ? "var(--white)" : "var(--ink-900)" }}>{r.brand}</span>
        <span style={{ color: "var(--gray-600)" }}>{r.acct}</span>
      </span>
    ) },
    { key: "date", label: "Date & Time", sortable: true, render: (r) => (
      <span style={{ display: "grid", gap: 1 }}>
        <span style={{ fontVariantNumeric: "tabular-nums" }}>{r.date}</span>
        <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>{r.time}</span>
      </span>
    ) },
    { key: "amt", label: "Amount", align: "right", sortable: true, render: (r) => <Amount value={r.amt} /> },
    { key: "status", label: "Status", align: "right", render: (r) => <Badge tone={r.status === "Completed" ? "success" : "warning"}>{r.status}</Badge> },
  ];
  return (
    <div style={{ display: "grid", gap: "var(--card-gap)" }}>
      <div style={{ display: "grid", gridTemplateColumns: "252px minmax(0,1fr) 280px", gap: "var(--card-gap)", alignItems: "stretch" }}>
        <BalanceCard amount="$20,670" currency="USD" onPrimary={() => onNavigate("wallet")} onSecondary={() => onNavigate("wallet")} />
        <Card title="AI Enhancements" action={<Button size="sm" variant="secondary" icon="plus" style={{ flex: "none" }}>Add</Button>}>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(3,minmax(0,1fr))", gap: 10 }}>
            {window.FX.enhancements.map((e) => <StatTile key={e.label} {...e} />)}
          </div>
        </Card>
        <FinanceScore />
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1.62fr) minmax(0,1fr)", gap: "var(--card-gap)" }}>
        <Card title="Cashflow" action={<Select options={["This Year", "This Month", "All time"]} value={range} onChange={setRange} />}>
          <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 16, marginBottom: 10 }}>
            <div style={{ display: "grid", gap: 2 }}>
              <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>Total Balance</span>
              <span style={{ font: "var(--weight-bold) var(--text-2xl)/1 var(--font-ui)", fontVariantNumeric: "tabular-nums", color: "var(--ink-900)" }}>$562,000</span>
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
              <div style={{ display: "grid", gap: 3, padding: "8px 10px", background: "var(--surface-sunken)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-sm)" }}>
                <span style={{ font: "var(--type-caption)", color: "var(--text-subtle)" }}>June 2029</span>
                <span style={{ display: "flex", gap: 10, font: "var(--type-caption)", color: "var(--gray-600)" }}><span>Income</span><b style={{ fontVariantNumeric: "tabular-nums" }}>$6,000</b></span>
                <span style={{ display: "flex", gap: 10, font: "var(--type-caption)", color: "var(--gray-600)" }}><span>Expense</span><b style={{ fontVariantNumeric: "tabular-nums" }}>$4,000</b></span>
              </div>
              <ChartLegend items={[{ label: "Income", color: "var(--chart-1)" }, { label: "Expense", color: "var(--chart-2)" }]} />
            </div>
          </div>
          <BarChart data={window.FX.cashflow} height={186} formatTick={(v) => (v === 0 ? "0" : (v / 1000).toFixed(0) + "K")} />
        </Card>

        <Card title="AI Assistant" menu>
          <div style={{ display: "grid", gap: 10, justifyItems: "center", textAlign: "center", padding: "6px 0 12px" }}>
            <AssistantOrb size={64} />
            <span style={{ font: "var(--type-card-title)", color: "var(--ink-900)" }}>What Can I help with?</span>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 6, justifyContent: "center" }}>
              {["Show me my cash flow", "Help me set a savings goal", "Forecast my balance", "Plan my monthly budget", "Detect unusual transactions", "Others"].map((c) => (
                <span key={c} style={{ display: "inline-flex", alignItems: "center", gap: 5, height: 24, padding: "0 10px", background: "var(--gray-50)", border: "1px solid var(--border-subtle)", borderRadius: "var(--radius-pill)", font: "var(--type-caption)", color: "var(--gray-600)" }}>
                  <Icon name="sparkles" size={11} color="var(--green-700)" />{c}
                </span>
              ))}
            </div>
          </div>
          <AssistantComposer style={{ marginTop: "auto" }} onSend={() => onNavigate("ai")} />
        </Card>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "minmax(0,1.9fr) minmax(0,1fr) minmax(0,1fr)", gap: "var(--card-gap)" }}>
        <Card title="Recent Transactions" padding={0} style={{ padding: "16px 0 0" }}
          action={<div style={{ display: "flex", alignItems: "center", gap: 6, paddingRight: 16 }}>
            <Select options={["This Month", "This Year"]} value={txRange} onChange={setTxRange} />
            <IconButton icon="sliders-horizontal" label="Filters" size="sm" variant="bare" />
          </div>}
          bodyStyle={{ padding: 0 }}>
          <div style={{ padding: "0 16px 12px", marginTop: -8 }} />
          <DataTable columns={cols} rows={window.FX.transactions.slice(0, 5)} dense />
        </Card>

        <Card title="Statistic" action={<Select options={["This Month", "This Year"]} value="This Month" />}>
          <Tabs style={{ marginBottom: 12 }}
            items={[{ id: "income", label: "Income", meta: "($4,800)" }, { id: "expense", label: "Expense", meta: "($3,500)" }]}
            value={statTab} onChange={setStatTab} />
          <div style={{ display: "grid", justifyItems: "center", gap: 14 }}>
            <DonutChart size={140} thickness={20} label="Total Expense" value="$3,500" segments={window.FX.expenseSegments} />
            <CategoryLegend style={{ width: "100%" }} items={window.FX.expenseSegments.map((s, i) => ({
              pct: s.pct, label: s.label, value: "$" + s.value.toLocaleString(), color: s.color,
              pctColor: i === 0 ? "var(--white)" : "var(--ink-900)",
            }))} />
          </div>
        </Card>

        <Card title="Exchange" action={<Badge tone="outline">Currencies</Badge>}>
          <ExchangePanel />
        </Card>
      </div>
    </div>
  );
}

Object.assign(window, { DashboardHome });

})();
