-- Migration 0029: histórico de tudo que é criado/alterado no painel (visível só para o dono)
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    repeat_count INT UNSIGNED NOT NULL DEFAULT 1,
    actor_user_id INT UNSIGNED NULL,
    actor_name VARCHAR(190) NULL,
    actor_email VARCHAR(190) NULL,
    actor_role VARCHAR(40) NULL,
    workspace_id INT UNSIGNED NULL,
    workspace_name VARCHAR(190) NULL,
    entity VARCHAR(60) NOT NULL,
    action VARCHAR(60) NOT NULL,
    entity_id VARCHAR(60) NULL,
    entity_label VARCHAR(255) NULL,
    summary VARCHAR(500) NOT NULL,
    details MEDIUMTEXT NULL,
    ip VARCHAR(64) NULL,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_entity (entity, created_at),
    INDEX idx_audit_actor (actor_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
