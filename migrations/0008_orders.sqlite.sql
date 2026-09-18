CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_id INTEGER NOT NULL,
    customer_id INTEGER NULL,
    offer_id INTEGER NULL,
    flow_type TEXT NOT NULL DEFAULT 'post_pix_upsell',
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','paid','abandoned','expired','refunded')),
    external_order_id TEXT NULL,
    base_amount NUMERIC(10,2) NOT NULL DEFAULT 0,
    upsell_amount NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_amount NUMERIC(10,2) NOT NULL DEFAULT 0,
    utm_source TEXT NULL,
    utm_medium TEXT NULL,
    utm_campaign TEXT NULL,
    paid_at TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (offer_id) REFERENCES upsell_offers(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_orders_workspace_created ON orders (workspace_id, created_at DESC);
