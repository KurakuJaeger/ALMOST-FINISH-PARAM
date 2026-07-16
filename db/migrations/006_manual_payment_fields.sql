-- Select the target database in phpMyAdmin before importing this migration.

-- Records the arithmetic result of the classroom/manual payment simulation.
-- Electronic submissions remain explicitly unverified because no payment API is used.
SET @submitted_amount_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE payments ADD COLUMN submitted_amount DECIMAL(10,2) NULL AFTER amount',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'submitted_amount'
);
PREPARE submitted_amount_statement FROM @submitted_amount_sql;
EXECUTE submitted_amount_statement;
DEALLOCATE PREPARE submitted_amount_statement;

SET @change_amount_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE payments ADD COLUMN change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER submitted_amount',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'change_amount'
);
PREPARE change_amount_statement FROM @change_amount_sql;
EXECUTE change_amount_statement;
DEALLOCATE PREPARE change_amount_statement;

SET @submitted_at_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE payments ADD COLUMN submitted_at DATETIME NULL AFTER reference_number',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'submitted_at'
);
PREPARE submitted_at_statement FROM @submitted_at_sql;
EXECUTE submitted_at_statement;
DEALLOCATE PREPARE submitted_at_statement;
