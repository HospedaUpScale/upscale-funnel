CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT UNSIGNED NOT NULL,
    external_platform VARCHAR(50) NOT NULL DEFAULT 'sorteamos',
    external_campaign_id VARCHAR(100) NULL,
    title VARCHAR(190) NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('active','inactive','published','ended','draft') NOT NULL DEFAULT 'active',
    published_at TIMESTAMP NULL DEFAULT NULL,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_campaign (workspace_id, external_platform, external_campaign_id),
    CONSTRAINT fk_campaigns_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
