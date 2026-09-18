// Mock data for the Fynix dashboard UI kit. Figures copied from the source case-study screens.
const MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

const FX = {
  user: { name: "Jenny Wilson", role: "Personal Account" },
  nav: [
    { title: "Main Menu", items: [
      { id: "dash", label: "Dashboard", icon: "house" },
      { id: "ai", label: "AI Fynix", icon: "wand-sparkles" },
      { id: "tx", label: "Transactions", icon: "receipt" },
      { id: "wallet", label: "My Wallet", icon: "wallet" },
      { id: "inv", label: "Invoices", icon: "clipboard-list" },
      { id: "rep", label: "Reports", icon: "chart-pie" },
    ] },
    { title: "Preference", items: [
      { id: "set", label: "Settings", icon: "settings" },
      { id: "help", label: "Help Center", icon: "circle-help" },
    ] },
  ],
  cashflow: MONTHS.map((m, i) => ({
    label: m,
    income: [5200,4100,4800,5600,5000,6100,5400,6400,7000,5800,4900,6600][i],
    expense: [3600,4200,3900,4400,3700,4100,3400,4600,3900,4300,3500,4800][i],
  })),
  enhancements: [
    { icon: "arrow-down-left", label: "Income", value: "$14,480.24", chip: "Guide" },
    { icon: "arrow-up-right", label: "Expense", value: "$14,480.24", chip: "Guide" },
    { icon: "piggy-bank", label: "Savings", value: "$14,480.24", chip: "Guide" },
  ],
  expenseSegments: [
    { label: "Rent & Living", value: 2100, color: "var(--chart-1)", pct: "60%" },
    { label: "Investment", value: 525, color: "var(--chart-2)", pct: "15%" },
    { label: "Education", value: 420, color: "var(--chart-4)", pct: "12%" },
    { label: "Food & Drinks", value: 455, color: "var(--chart-5)", pct: "13%" },
  ],
  transactions: [
    { name: "Dividend Payout", cat: "Investments", icon: "trending-up", tone: "income", acct: "Platinum Plus Visa", brand: "VISA", id: "456789008", date: "2024-09-25", time: "10:00", amt: "+$200.00", status: "Completed", note: "Quarterly stock dividend" },
    { name: "Grocery Shopping", cat: "Food & Dining", icon: "shopping-basket", tone: "expense", acct: "Platinum Plus Visa", brand: "VISA", id: "456789016", date: "2024-09-24", time: "14:30", amt: "-$184.20", status: "Completed", note: "Weekly household groceries" },
    { name: "Freelance Payment", cat: "Income", icon: "circle-dollar-sign", tone: "income", acct: "Freedom Mastercard", brand: "MC", id: "456789023", date: "2024-09-23", time: "15:00", amt: "+$850.00", status: "Completed", note: "Payment for design work" },
    { name: "Electricity Bill", cat: "Utilities", icon: "zap", tone: "expense", acct: "Freedom Mastercard", brand: "MC", id: "456789031", date: "2024-09-22", time: "09:15", amt: "-$120.75", status: "Completed", note: "Monthly utility bill" },
    { name: "Online Subscription", cat: "Services", icon: "repeat", tone: "expense", acct: "Platinum Plus Visa", brand: "VISA", id: "456789040", date: "2024-09-18", time: "08:00", amt: "-$12.99", status: "Pending", note: "Streaming service renewal" },
    { name: "Stock Dividend", cat: "Investments", icon: "chart-line", tone: "income", acct: "Freedom Mastercard", brand: "MC", id: "456789058", date: "2024-09-17", time: "11:20", amt: "+$300.00", status: "Completed", note: "Quarterly stock dividend" },
    { name: "Armani Exchange", cat: "Shopping", icon: "shopping-bag", tone: "expense", acct: "Platinum Plus Visa", brand: "VISA", id: "456789065", date: "2024-09-16", time: "18:45", amt: "-$96.11", status: "Pending", note: "Purchased clothing" },
  ],
  cards: [
    { label: "Personal", brand: "VISA", last4: "8744", tone: "blue" },
    { label: "Business", brand: "VISA", last4: "5641", tone: "gray" },
    { label: "Business", brand: "GB", last4: "9007", tone: "ink" },
  ],
  walletBalance: [22,26,24,31,29,38,34,44,40,52,48,61,57,68,64,74,70,82,78,88,84,92,88,96],
  cashflowLine: [30,42,36,52,44,60,54,68,62,76,70,84],
  cashflowLine2: [24,30,28,38,34,44,40,50,46,58,52,64],
  aiPrompts: [
    { icon: "activity", title: "Track Cash Flow", body: "View income, spending, and savings in real time with AI insights for smarter financial management." },
    { icon: "shield-alert", title: "Detect Unusual Transactions", body: "Get instant alerts on suspicious charges or duplicate payments, keeping your finances fully protected." },
    { icon: "target", title: "Plan a Savings Goal", body: "Set smart AI-recommended savings targets to securely fund your emergency needs or dream vacation goals." },
    { icon: "gauge", title: "Financial Health Score", body: "Receive a personalised AI-powered score that evaluates your spending, saving, and investments for financial wellness." },
  ],
  expenseTotals: [
    { label: "Daily", value: "Rp31.000" },
    { label: "Weekly", value: "Rp251.000" },
    { label: "Monthly", value: "Rp915.200" },
  ],
};

window.FX = FX;
window.MONTHS = MONTHS;
