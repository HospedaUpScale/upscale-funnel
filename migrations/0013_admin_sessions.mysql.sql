CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    workspace_id INT UNSIGNED NOT NULL,
    role VARCHAR(30) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_admin_sessions_token (token_hash),
    KEY idx_admin_sessions_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
