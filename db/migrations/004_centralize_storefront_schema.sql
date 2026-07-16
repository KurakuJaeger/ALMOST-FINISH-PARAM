-- Select the target database in phpMyAdmin before importing this migration.

-- Upgrade existing databases; fresh installs receive these definitions from database_schema.sql.
SET @profile_column_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN profile_image_path VARCHAR(255) NULL AFTER email_verified_at',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_image_path'
);
PREPARE profile_column_statement FROM @profile_column_sql;
EXECUTE profile_column_statement;
DEALLOCATE PREPARE profile_column_statement;

-- Preserve profile photos from the older storefront column when it exists.
SET @copy_legacy_profile_sql = (
    SELECT IF(COUNT(*) = 2,
        'UPDATE users SET profile_image_path = profile_pic WHERE profile_image_path IS NULL AND profile_pic IS NOT NULL',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME IN ('profile_pic', 'profile_image_path')
);
PREPARE copy_legacy_profile_statement FROM @copy_legacy_profile_sql;
EXECUTE copy_legacy_profile_statement;
DEALLOCATE PREPARE copy_legacy_profile_statement;

CREATE TABLE IF NOT EXISTS favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorites_user_product (user_id, product_id),
    INDEX idx_favorites_user_id (user_id),
    INDEX idx_favorites_product_id (product_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET @variant_unique_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE product_variants ADD UNIQUE KEY uq_product_variant_options (product_id, size, color)',
        'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants'
      AND INDEX_NAME = 'uq_product_variant_options'
);
PREPARE variant_unique_statement FROM @variant_unique_sql;
EXECUTE variant_unique_statement;
DEALLOCATE PREPARE variant_unique_statement;
