Every panel on every screen is a Card — 16px radius, 16px padding, 1px --border-subtle, --shadow-card.

<Card title="Cashflow" action={<Select options={["This Year"]} />} menu>
  <BarChart data={months} />
</Card>

Header is 16px semibold title with an optional caption subtitle; actions and the ⋯ menu sit right. Tones: default, sunken (inset regions), inverse (ink gradient), brand (green gradient), quiet (no border/shadow, for nesting).
