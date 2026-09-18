Total-expense donut with rounded, gapped segments and a centred figure.

<DonutChart segments={[{value:2100,color:"var(--chart-1)"},{value:525,color:"var(--chart-2)"}]} label="Total Expense" value="$3,500" />
<CategoryLegend items={[{pct:"60%",label:"Rent & Living",value:"$2,100",color:"var(--chart-1)",pctColor:"var(--white)"}]} />

Pair it with CategoryLegend, whose percentage chips repeat the segment colours.
