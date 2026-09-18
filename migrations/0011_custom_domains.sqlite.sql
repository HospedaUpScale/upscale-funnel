CREATE TABLE IF NOT EXISTS custom_domains (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_id INTEGER NOT NULL,
    domain TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','active','failed')),
    verification_token TEXT NOT NULL,
    is_primary INTEGER NOT NULL DEFAULT 0,
    last_checked_at TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_custom_domains_workspace ON custom_domains(workspace_id);
CREATE INDEX IF NOT EXISTS idx_custom_domains_status ON custom_domains(status);
