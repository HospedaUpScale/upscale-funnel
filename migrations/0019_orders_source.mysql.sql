-- Canal de origem da venda (para rastrear todas as vendas, inclusive o link exclusivo do upsell)
ALTER TABLE orders ADD COLUMN source VARCHAR(40) NULL;
ALTER TABLE orders ADD COLUMN source_ref VARCHAR(190) NULL;
CREATE INDEX idx_orders_source ON orders (workspace_id, source, created_at);
