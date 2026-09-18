/**
 * Transaction table with sortable headers, selection and status cells.
 */
export interface DataColumn {
  key: string;
  label: string;
  align?: "left" | "right" | "center";
  width?: number | string;
  sortable?: boolean;
  render?: (row: any) => React.ReactNode;
}
export interface DataTableProps {
  columns: DataColumn[];
  rows: any[];
  selectable?: boolean;
  selected?: number[];
  onSelect?: (next: number[]) => void;
  dense?: boolean;
  emptyLabel?: string;
  style?: React.CSSProperties;
}
export function DataTable(props: DataTableProps): JSX.Element;
export interface TransactionCellProps { icon?: string; title: string; meta?: string; tone?: "income" | "expense" | "neutral" }
export function TransactionCell(props: TransactionCellProps): JSX.Element;
export interface AmountProps { value: string; positive?: boolean }
export function Amount(props: AmountProps): JSX.Element;
