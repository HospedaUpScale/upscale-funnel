CREATE TABLE IF NOT EXISTS conversion_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_id INTEGER NOT NULL,
    order_id INTEGER NULL,
    event_name TEXT NOT NULL,
    metadata TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_events_workspace_name_created ON conversion_events (workspace_id, event_name, created_at DESC);
