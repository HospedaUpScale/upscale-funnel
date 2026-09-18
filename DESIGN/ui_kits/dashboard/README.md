# Fynix Admin Dashboard — UI kit

A click-through recreation of the four views designed in the case study. Open `index.html`.

## Screens

| View | Source | What works |
| --- | --- | --- |
| **Dashboard / Home** (`DashboardHome.jsx`) | `01.webp`, `03.webp`, `07.webp` | Balance hero, AI Enhancements tiles, Finance Score, Cashflow bars with range select, AI Assistant panel with prompt chips, Recent Transactions, Statistic donut with Income/Expense tabs, Exchange |
| **AI Fynix** (`AiAssistant.jsx`) | `04.webp`, `07.webp` | Orb + greeting, composer that actually sends (renders a canned reply thread), four suggestion cards that load their title into the composer |
| **Transactions** (`Transactions.jsx`) | `05.webp`, `07.webp` | Balance summary, card list with quick actions, Cash Flow area chart, searchable + category-filtered table with row selection and a bulk action bar |
| **My Wallet** (`Wallet.jsx`) | `06.webp`, `08.webp` | Balance details header, card switcher driving the detail panel, spending limits meter, My Wallets tabs + area chart, transactions, All Expenses donut, Convert panel |

Invoices, Reports, Settings and Help Center render a deliberate "not in the source" placeholder — those screens were never designed.

## Notes

- Everything is composed from the design system's components via `window.UpscaleHubDesignSystem_b4eac1`; no primitive is re-implemented here.
- `window.FYNIX_ASSET_BASE` is set in `index.html` so components that reference the brand mark resolve it from this folder.
- Mock data lives in `data.js`, with figures copied from the source screens.
- The selected page persists in `localStorage` under `fynix.page`.
- Design width is 1440px. The layout reflows down to roughly 1100px before the three-column rows get tight; the source's mobile treatment stacks these same cards in one column.
