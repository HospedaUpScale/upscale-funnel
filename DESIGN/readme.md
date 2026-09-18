# Upscale Hub Design System — Fynix

The design system for **Fynix**, the AI-powered finance dashboard designed by **Upscale Hub**. Everything here is derived from the Fynix case-study deck the team supplied; nothing was invented from an outside brand.

> "Fynix is an AI-powered finance dashboard that simplifies income, expenses, and savings tracking with smart insights."
> — Arlene A., Co-Founder

## Sources given to me

| Source | What it contained |
| --- | --- |
| `uploads/01.webp` | Laptop hero of the Dashboard/Home screen, project overview table (Fynix · Finance/Technology · New York, US · 2025), app-icon artwork |
| `uploads/02.webp` | Balance-card detail, iOS home-screen icon in context, three metric cards, logo construction grid (14/16/85/24 units) |
| `uploads/03.webp` | "Case Structure" — Research 2 weeks, Design 8 weeks, Testing 1 week, Presentation 1 week; angled monitor of Home |
| `uploads/04.webp` | **The brand plate**: typography (Space Grotesk, Urbanist, Plus Jakarta Sans), the four colour values, the icon grid, Statistic card, AI assistant screen |
| `uploads/05.webp` | Impact quotes, strategy cards, Problem & Solution, Transactions screen, mobile-responsive phone-in-hand |
| `uploads/06.webp` | **Landing page** hero ("Get paid early. Access your earnings sooner with Fynix"), Wallet screen, card list + wallet chart details, two workplace photographs, mobile wallet |
| `uploads/07.webp` | Three feature sections: "Your Finance Simplified" (Home), "Smart AI Assistant" (AI Fynix), "All Transactions Tracked" (Transactions) |
| `uploads/08.webp` | "Smart Digital Wallet" (Wallet screen), the Fynix logo lockup in a lit room, roadmap timeline, results (4 sprints · 12 weeks · 29 screens) |

No codebase, Figma file, or font binaries were provided — the case study images are the single source of truth. Where a value could not be measured from them it is flagged in **Caveats** at the bottom.

## Products represented

1. **Fynix Admin Dashboard** (web app, the primary surface) — Dashboard/Home, AI Fynix assistant, Transactions, My Wallet, plus Invoices, Reports, Settings and Help Center in the navigation but never shown in the source. → `ui_kits/dashboard/`
2. **Fynix marketing site** — one hero section shown in `06.webp`. → `ui_kits/website/` *(not yet built — see Caveats)*
3. **Fynix mobile** — the dashboard reflowed to one column on iPhone; same components, no separate kit.

---

## Content fundamentals

**Voice.** Plain, confident, second person. The product talks to *you* ("Track finances easily with AI insights and recommendations", "How Can I Assist You With Your Finances?", "Securely store, track, and manage your money"). The company talks as *we* only in case-study prose ("During 8 weeks, we conducted in-depth market analysis", "We're excited to collaborate further").

**Casing.**
- Page and card titles: Title Case — "Recent Transactions", "Finance Score", "AI Enhancements", "Your Cards".
- Section eyebrows in the sidebar: ALL CAPS with wide tracking — "MAIN MENU", "PREFERENCE".
- Sentence case for body copy, hints and captions.
- Marketing headlines use a full stop: "Get paid early. Access your earnings sooner with Fynix".

**The quotation-mark habit.** Descriptive subtitles are wrapped in curly quotes, both in the product and the deck: `"Track finances easily with AI insights and recommendations."`, `"View, track, and manage all expenses with ease."` `TopBar` and `UpgradeCard` render these quotes for you — don't type them into the string.

**Length.** Page subtitles are one clause, max ~60 characters. Suggestion-card bodies are exactly one sentence. Table notes are a short phrase ("Quarterly stock dividend", "Monthly internet and TV bill"). Nothing in the UI runs past two lines.

**Numbers.** Always formatted, always tabular: `$20,670`, `$14,480.24`, `−$184.20`, `92%`, `+1.9%`. Currency code follows the hero amount as a separate small label (`$20,670 USD`). Negative amounts carry a minus and turn red; positives carry a plus and stay ink. Dates are ISO-ish with a time beneath: `2024-09-25 / 10:00`.

**Labels to reuse verbatim:** Total Balance · Deposit · Send · Cashflow · Recent Transactions · Transaction Name · Date & Time · Amount · Status · Completed · Pending · This Month · This Year · All Category · All Account · Download · Export · Get Plus · Upgrade your Plan · Search anything · Ask anything… · Learn more → · Manage Balance · Spending Limits · Available.

**Emoji.** None in the product UI. The only pictographs are country flags inside currency selectors (🇺🇸 USD, 🇬🇧 GBP). Never use emoji as icons.

**Vibe.** Calm competence. The interface is quiet and dense; the green does the talking. Copy never hypes ("AI-powered" is stated once, then dropped), never apologises, and never uses exclamation marks except the assistant's one greeting: "Good to see you!".

---

## Visual foundations

**Colour.** The primary is **`#0229C4`**, the blue from the upScale logo — it owns every brand fill, active state, focus ring and the first data series. The lime `#9FE870` from the Fynix case study is kept as the **secondary accent** and carries all positive/success states. Ink is a deep navy `#05153F` derived from the blue; neutrals are the original greys (`#787878` and the `--gray-*` ramp); white `#FFFFFF`. The page sits on `#EDEDED` canvas grey; cards are pure white. Two background colours per screen, maximum: canvas grey plus white cards, with exactly one blue hero surface.

**Contrast rule that matters:** text on the brand blue is always **white** (`--text-on-brand`); text on the lime accent is always **ink** (`--text-on-accent`), never white. Inverse panels use the navy ink gradient with white text and `--ink-300` for secondary lines.

**Type.** Three families, each with a job.
- **Urbanist** — display. Marketing headlines and case-study scale (40–72px), Light 300 at the biggest sizes, SemiBold 600 for section heads. Geometric, single-storey `a`, very open.
- **Plus Jakarta Sans** — the product. Every label, cell, title and button (11–32px). Card titles 16/600, body 14/400, table cells 13/400, labels 12/500, captions 11/400.
- **Space Grotesk** — accent. Tabular money, masked card digits, keycaps, transaction IDs.

Letter-spacing tightens as size grows (−0.01em at 24px, −0.02em at 32px+). The sidebar eyebrow is the only tracked-out, uppercased text (+0.09em).

**Spacing & layout.** 216px sidebar on canvas grey with a hairline right border; 56px translucent top bar; 16px page padding; **12px gutters between cards** — tight, deliberately dense. Cards carry 16px internal padding (20px on hero/feature cards). Table rows are 52px (44px dense), header rows 34px. The dashboard is a 3-column grid that changes weights per row rather than a rigid 12-column grid: `252px / 1fr / 280px` on the top row, `1.62fr / 1fr` for charts, `1.9fr / 1fr / 1fr` at the bottom.

**Corners.** 16px on cards, 20px on outer panels and hero surfaces, 12px on nested blocks, 10px on inputs, 8px on nav chips and inset rows, 6px on checkboxes and mini card faces, fully round on buttons, avatars, search, icon buttons and badges. The app icon is a 28%-radius squircle. Nothing in the system is a hard 0px corner.

**Cards.** White fill, `1px solid #E6E6E6` hairline, 16px radius, and a very quiet **navy-tinted** shadow: `0 1px 2px rgba(5,21,63,.04), 0 8px 24px -14px rgba(5,21,63,.16)`. Shadows are never neutral black and never heavy — the hairline does most of the separation work. The blue hero card is the exception: no border, a blue glow (`--shadow-brand`).

**Backgrounds.** Flat colour, never photographic behind UI. The one decorative motif is the **swirl** on the balance card — concentric translucent white arcs curling through the blue surface. The AI orb is a soft radial gradient (pale blue → brand blue → navy) with no face, mark or icon inside it. Marketing sections use a barely-there vertical wash (`--gradient-page`) and a bottom protection gradient over long scrolls.

**Transparency & blur.** Used in exactly one place: the sticky top bar, `rgba(255,255,255,.72)` with `saturate(150%) blur(18px)`. Modal scrims are `rgba(5,21,63,.36)` — a navy-black, not grey. Nothing else is translucent; charts, cards and badges are all opaque.

**Animation.** Fast and understated. 140ms for control state changes, 220ms for entrances and toggles, 360ms for bar/area growth, on `cubic-bezier(.32,.72,0,1)` (a decelerating standard curve). The only springy easing is the Switch knob. No bounces, no parallax, no auto-playing motion. `prefers-reduced-motion` zeroes all durations.

**Interaction states.**
- *Hover*: one step darker fill for solid buttons (`--ink-800`, `--blue-500`); a `--gray-50` tint for white/ghost controls; table rows tint `--gray-50`; suggestion cards swap their hairline to `--blue-300` and lift to `--shadow-sm`, and their arrow nudges 2px right.
- *Press*: `scale(0.975)` — shrink, not colour change.
- *Focus*: blue border plus a 3px `rgba(2,41,196,.38)` halo. Never a browser outline.
- *Selected*: blue tint fill (`--blue-50`/`--blue-100`) plus a blue hairline (card rows, menu items) or a white chip with a hairline (active nav item — **not** a coloured fill).
- *Disabled*: 42% opacity, no colour change.

**Borders.** Hairlines everywhere: `--border-subtle #E6E6E6` for card edges and dividers, `--border-default #DEDEDE` for inputs, `--border-strong #CFCFCF` for unchecked control rings. 1px only; 1.5px appears solely on radio rings.

**Data visualisation.** Brand blue is series 1, lime is series 2 — in that order, always. Cashflow bars grow up (income, blue) and down (expense, lime) from a centre axis, 1.6px radius, dashed `#E6E6E6` gridlines, month labels in 11px grey. Donuts have rounded, 3°-gapped segments and a centred total. Area charts use a smooth cubic curve with a blue-to-transparent fill and an optional dot-plus-dropline marker. Axis labels are always 11px `--chart-axis` grey.

**Imagery.** Daylight workplace photography, cool neutral grade, real people at real desks with Fynix visibly on screen — no stock-y tinting, no duotone, no grain. Photos are cropped to 16px-radius rectangles. Product shots sit in realistic device mockups (MacBook, iMac, iPhone) on a light grey studio ground with a soft contact shadow.

---

## Iconography

- **System:** a thin-stroke, rounded-terminal outline family — the case-study icon grid shows 12 glyphs (wand/sparkle, ⌘, search, mic, home, help, paperclip, wallet, settings, clipboard, pie chart, dollar-circle) each in a white circular chip.
- **No icon binaries were supplied.** ⚠️ **Substitution flagged:** the system uses **Lucide** (pinned `lucide-static@0.544.0` on jsDelivr) as the closest CDN match — same outline style, rounded caps, 24px grid. `Icon` fetches each glyph once, caches it, and inlines the SVG at `stroke-width: 1.75` to match the source's thin stroke; inlining also means icons survive screenshots, PDF and PPTX export, which a CSS mask does not. If the original set exists as SVG, drop it into `assets/icons/` and point `components/core/Icon.jsx` at it — nothing else needs to change.
- **How to use it:** always `<Icon name="…" />`. It inlines the glyph so it inherits `color` natively, which means one component covers every tint and every export path. Never paste inline SVG, never use an emoji or a Unicode dingbat as an icon.
- **Sizes:** 11–13px inside badges and captions, 14–16px inside buttons and table cells, 16–18px in navigation, 17px in the 38px circular chip treatment.
- **The chip treatment:** the source's signature icon presentation is a glyph centred in a white circle with a hairline border (`IconButton variant="plain"`, `ActionTile`, `StatTile`'s leading chip). Use it whenever an icon needs to stand alone.
- **Unicode:** used for masked card digits (`•••• 8744`), the currency minus sign (`−`), and arrows in copy (`Learn more →`). Country flag emoji appear only in currency pickers.
- **Brand mark:** `assets/logo-mark.png` stands in for an icon at brand scale (sidebar, upsell card, empty states) — it is the bird, never a letter F on its own.

---

## Index

### Root
- `styles.css` — the single entry point consumers link. `@import` lines only.
- `tokens/` — `fonts.css`, `colors.css`, `typography.css`, `spacing.css`, `radius.css`, `elevation.css`, `motion.css`, `base.css`.
- `assets/` — **Fynix (product):** `logo-lockup.png`, `logo-lockup-white.png`, `logo-mark.png`, `logo-mark-white.png`, `app-icon-512.png`, `app-icon-180.png` (blue squircle). **upScale (agency):** `logo-upscale.svg` (blue `#0229C4` wordmark with a lime smile), `logo-upscale-white.svg` (reversed). **Photography:** `photo-team-meeting.jpg`, `photo-ops-floor.jpg`. The Fynix lockups and marks were extracted programmatically from the supplied case study and alpha-keyed to transparency; the upScale wordmark is the SVG you supplied.
- `thumbnail.html` — homepage tile.
- `SKILL.md` — Agent-Skills wrapper so this folder works as a downloadable skill.

### Components (`components/<group>/`)
| Group | Components |
| --- | --- |
| `core/` | Icon, Button, IconButton, Badge, Avatar, ProgressBar, Tabs, Switch |
| `forms/` | Input, SearchField, Select, Checkbox, Radio |
| `surfaces/` | Card (+ CardFooterLink), StatTile, BalanceCard, MetricCard, UpgradeCard |
| `data/` | DataTable (+ TransactionCell, Amount), BarChart (+ ChartLegend), DonutChart (+ CategoryLegend), AreaChart (+ Sparkline) |
| `navigation/` | SidebarNav (+ AccountSwitcher), TopBar |
| `finance/` | CreditCardItem (+ ActionTile), ExchangePanel, SuggestionCard, AssistantComposer (+ AssistantOrb) |

Each has a `.d.ts` props contract, a `.prompt.md` usage note, and one `@dsCard` showcase per directory.

**Intentional additions** (not in the source, added with a reason):
- `Switch` — the navigation includes Settings but no settings screen was designed; a toggle was unavoidable for that surface. Built from the system's own control language.
- `Icon` — a wrapper so the substituted glyph set can be swapped in one place.
- `MetricCard` — the big-percentage card from the case-study deck (`02.webp`), included for presentation use, not dashboards.

### Guidelines (`guidelines/*.card.html`)
21 specimen cards across **Colors** (brand, green ramp, evergreen ramp, neutrals, status pairs, chart palette, gradients), **Type** (display, UI, accent, figures in use), **Spacing** (scale, layout constants, radii, elevation, motion) and **Brand** (logo, app icon, iconography, photography, card anatomy).

### UI kits
- `ui_kits/dashboard/` — the Fynix Admin Dashboard: `index.html` (click-through), `AppShell.jsx`, `DashboardHome.jsx`, `AiAssistant.jsx`, `Transactions.jsx`, `Wallet.jsx`, `data.js`, `README.md`.

---

## Caveats

1. **Font binaries are missing.** Space Grotesk, Urbanist and Plus Jakarta Sans all load from Google Fonts — these are the genuine families named on the brand plate, not lookalikes, but they are not self-hosted. Send licensed files if you need offline or CDN-free builds.
2. **Icon set is a substitution** (Lucide for the source's thinner custom outline family) — see Iconography.
3. **Screenshot-only source.** Every value here was measured off 8 case-study images. Paddings, radii and type sizes are careful reads, not exported values; if the Figma file exists, a pass against it would tighten them.
4. **Warning/amber is inferred.** The source shows "Pending" in two different renderings; `--amber-600 #E8A33D` is my reading and the one value in the palette without a printed hex.
5. **Invoices, Reports, Settings and Help Center were never designed.** The dashboard kit renders an explicit "not in the source" placeholder for them rather than inventing screens.
6. **The primary colour is now `#0229C4`** (the upScale logo blue), applied across tokens, components, specimen cards, the dashboard kit and the app icon. The Fynix lime `#9FE870` was demoted to secondary accent and success colour rather than dropped, so charts keep two clearly distinct series and the brand heritage survives. The wordmark's underline smile is lime — tell me if it should be another colour. The supplied SVG itself carried no colour values (empty `<defs>`), so these fills are mine.
7. **The marketing site kit is not built yet** — only one hero section exists in `06.webp`, which is thin ground for a kit. Say the word and I'll build it from that hero plus the photography.
