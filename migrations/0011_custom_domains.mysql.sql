CREATE TABLE IF NOT EXISTS custom_domains (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT UNSIGNED NOT NULL,
    domain VARCHAR(190) NOT NULL,
    status ENUM('pending','active','failed') NOT NULL DEFAULT 'pending',
    verification_token VARCHAR(64) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    last_checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_custom_domains_domain (domain),
    KEY idx_custom_domains_workspace (workspace_id),
    KEY idx_custom_domains_status (status),
    CONSTRAINT fk_custom_domains_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
