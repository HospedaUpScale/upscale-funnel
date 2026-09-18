CREATE TABLE IF NOT EXISTS funnels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT UNSIGNED NOT NULL,
    custom_domain_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    html_content MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    is_home TINYINT(1) NOT NULL DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_funnels_workspace_slug (workspace_id, slug),
    KEY idx_funnels_workspace (workspace_id),
    KEY idx_funnels_domain (custom_domain_id),
    KEY idx_funnels_status (status),
    CONSTRAINT fk_funnels_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    CONSTRAINT fk_funnels_domain FOREIGN KEY (custom_domain_id) REFERENCES custom_domains(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
