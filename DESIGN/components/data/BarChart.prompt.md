The Cashflow chart: ink income bars up, green expense bars down, dashed gridlines, month labels.

<BarChart data={months} height={200} formatTick={(v) => (v / 1000) + "K"} />
<ChartLegend items={[{label:"Income",color:"var(--chart-1)"},{label:"Expense",color:"var(--chart-2)"}]} />

Series colours are always --chart-1 (ink) and --chart-2 (green) in that order. Bars have a 1.6px radius and a small gap, never full-width.
