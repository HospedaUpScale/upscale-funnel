CREATE TABLE IF NOT EXISTS order_access_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT NOT NULL UNIQUE,
    workspace_id INTEGER NOT NULL,
    external_order_id TEXT NULL,
    customer_name TEXT NULL,
    customer_phone TEXT NULL,
    customer_cpf TEXT NULL,
    base_amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    is_paid INTEGER NOT NULL DEFAULT 1,
    carro_a TEXT NULL,
    carro_b TEXT NULL,
    extra_bonus NUMERIC(10,2) NOT NULL DEFAULT 50000.00,
    min_order NUMERIC(10,2) NOT NULL DEFAULT 30.00,
    bonus_entitled INTEGER NOT NULL DEFAULT 1,
    upsell_converted INTEGER NOT NULL DEFAULT 0,
    upsell_order_id INTEGER NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_order_tokens_token ON order_access_tokens (token);
CREATE INDEX IF NOT EXISTS idx_order_tokens_ws_order ON order_access_tokens (workspace_id, external_order_id);
