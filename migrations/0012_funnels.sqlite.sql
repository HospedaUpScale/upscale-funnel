CREATE TABLE IF NOT EXISTS funnels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_id INTEGER NOT NULL,
    custom_domain_id INTEGER NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    html_content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published')),
    is_home INTEGER NOT NULL DEFAULT 0,
    published_at TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(workspace_id, slug),
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (custom_domain_id) REFERENCES custom_domains(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_funnels_workspace ON funnels(workspace_id);
CREATE INDEX IF NOT EXISTS idx_funnels_domain ON funnels(custom_domain_id);
CREATE INDEX IF NOT EXISTS idx_funnels_status ON funnels(status);
