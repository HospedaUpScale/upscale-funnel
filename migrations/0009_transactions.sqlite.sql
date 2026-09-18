CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'upsell_order' CHECK(type IN ('main_order','upsell_order','consolidated_order')),
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','paid','expired','failed')),
    gateway TEXT NOT NULL DEFAULT 'mock',
    gateway_tx_id TEXT NULL,
    pix_copy_paste TEXT NULL,
    pix_qr_base64 TEXT NULL,
    amount NUMERIC(10,2) NOT NULL DEFAULT 0,
    split_details TEXT NULL,
    mock_auto_confirm_at TEXT NULL,
    expires_at TEXT NULL,
    paid_at TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_tx_gateway ON transactions (gateway, gateway_tx_id);
