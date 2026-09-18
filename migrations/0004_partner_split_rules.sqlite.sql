CREATE TABLE IF NOT EXISTS partner_split_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    partner_workspace_id INTEGER NOT NULL,
    merchant_workspace_id INTEGER NULL,
    saas_rate_percentage NUMERIC(5,2) NOT NULL DEFAULT 10.00,
    partner_rate_percentage NUMERIC(5,2) NOT NULL DEFAULT 5.00,
    merchant_rate_percentage NUMERIC(5,2) NOT NULL DEFAULT 85.00,
    absorb_gateway_fees TEXT NOT NULL DEFAULT 'merchant',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (partner_workspace_id, merchant_workspace_id),
    FOREIGN KEY (partner_workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (merchant_workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);
