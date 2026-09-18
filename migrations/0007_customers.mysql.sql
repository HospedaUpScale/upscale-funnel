CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    email VARCHAR(190) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_customer_cpf (workspace_id, cpf),
    KEY idx_customer_phone (workspace_id, phone),
    CONSTRAINT fk_customers_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
