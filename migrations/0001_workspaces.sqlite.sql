CREATE TABLE IF NOT EXISTS workspaces (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER NULL,
    type TEXT NOT NULL DEFAULT 'merchant' CHECK(type IN ('super_admin','partner_whitelabel','merchant')),
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    custom_domain TEXT NULL UNIQUE,
    document TEXT NULL,
    api_token_hash TEXT NOT NULL,
    admin_token_hash TEXT NOT NULL,
    gateway_provider TEXT NOT NULL DEFAULT 'mock',
    gateway_credentials TEXT NULL,
    split_recipient_id TEXT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','suspended')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES workspaces(id) ON DELETE SET NULL
);
