-- Select the target database in phpMyAdmin before importing this migration.
CREATE TABLE IF NOT EXISTS support_concerns (
    concern_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id INT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    response TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    assigned_to_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_support_customer (customer_id), INDEX idx_support_order (order_id), INDEX idx_support_assigned (assigned_to_user_id),
    CONSTRAINT fk_support_customer FOREIGN KEY (customer_id) REFERENCES users(user_id),
    CONSTRAINT fk_support_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    CONSTRAINT fk_support_assigned FOREIGN KEY (assigned_to_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
