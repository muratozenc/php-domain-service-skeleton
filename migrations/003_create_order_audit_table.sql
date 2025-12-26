CREATE TABLE IF NOT EXISTS order_audit (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    action VARCHAR(50) NOT NULL,
    reason TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

