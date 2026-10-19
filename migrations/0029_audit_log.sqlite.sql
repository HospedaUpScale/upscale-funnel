-- Migration 0029: histórico de tudo que é criado/alterado no painel (visível só para o dono)
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    repeat_count INTEGER NOT NULL DEFAULT 1,
    actor_user_id INTEGER NULL,
    actor_name TEXT NULL,
    actor_email TEXT NULL,
    actor_role TEXT NULL,
    workspace_id INTEGER NULL,
    workspace_name TEXT NULL,
    entity TEXT NOT NULL,
    action TEXT NOT NULL,
    entity_id TEXT NULL,
    entity_label TEXT NULL,
    summary TEXT NOT NULL,
    details TEXT NULL,
    ip TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log (created_at);
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_log (entity, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_actor ON audit_log (actor_user_id, created_at);
