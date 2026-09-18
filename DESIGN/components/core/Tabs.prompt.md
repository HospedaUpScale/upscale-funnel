Tab switcher for card-level views (Income/Expense, Wallet/Card Transaction/Investment).

<Tabs items={[{id:"income",label:"Income",meta:"($4,800)"},{id:"expense",label:"Expense",meta:"($3,500)"}]} value={tab} onChange={setTab} />

Underline is the product default: green 2px rule under the active label, muted grey for the rest. `meta` renders the parenthetical amount in caption grey. Use `variant="pill"` only for segmented filters.
