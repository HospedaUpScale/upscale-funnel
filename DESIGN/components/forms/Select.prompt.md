Small dropdown that sits in card headers ("This Month ⌄", "All Category ⌄").

<Select options={["This Month","This Year","All time"]} value={range} onChange={setRange} />

`quiet` (white pill, 32px) is the in-card default; `field` is the form variant. The chevron rotates 180° when open; the menu is a white 12px-radius popover with the selected row tinted green.
