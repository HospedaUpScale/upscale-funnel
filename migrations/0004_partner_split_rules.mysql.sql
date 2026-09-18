CREATE TABLE IF NOT EXISTS partner_split_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_workspace_id INT UNSIGNED NOT NULL,
    merchant_workspace_id INT UNSIGNED NULL,
    saas_rate_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    partner_rate_percentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    merchant_rate_percentage DECIMAL(5,2) NOT NULL DEFAULT 85.00,
    absorb_gateway_fees VARCHAR(20) NOT NULL DEFAULT 'merchant',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_split_rule (partner_workspace_id, merchant_workspace_id),
    CONSTRAINT fk_psr_partner FOREIGN KEY (partner_workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    CONSTRAINT fk_psr_merchant FOREIGN KEY (merchant_workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
