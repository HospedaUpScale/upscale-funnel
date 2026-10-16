-- Canal de origem da venda (para rastrear todas as vendas, inclusive o link exclusivo do upsell)
ALTER TABLE orders ADD COLUMN source TEXT NULL;
ALTER TABLE orders ADD COLUMN source_ref TEXT NULL;
CREATE INDEX IF NOT EXISTS idx_orders_source ON orders (workspace_id, source, created_at DESC);
