My Wallets / Cash Flow trend chart: soft cubic curve, green-to-transparent fill, optional marker.

<AreaChart series={[{points: balance}]} yTicks={["$100k","$80k","$60k","$40k","$20k","0"]} marker={5} />

Two series max: green primary, ink secondary (`fill: false`). `Sparkline` is the same curve at row scale.
