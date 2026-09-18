CREATE TABLE IF NOT EXISTS transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    type ENUM('main_order','upsell_order','consolidated_order') NOT NULL DEFAULT 'upsell_order',
    status ENUM('pending','paid','expired','failed') NOT NULL DEFAULT 'pending',
    gateway VARCHAR(30) NOT NULL DEFAULT 'mock',
    gateway_tx_id VARCHAR(100) NULL,
    pix_copy_paste TEXT NULL,
    pix_qr_base64 MEDIUMTEXT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    split_details JSON NULL,
    mock_auto_confirm_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tx_gateway (gateway, gateway_tx_id),
    CONSTRAINT fk_tx_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
