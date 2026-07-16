-- Select the target database in phpMyAdmin before importing this migration.

-- Give every existing order one delivery task. Future checkout transactions
-- create this row directly.
INSERT INTO deliveries (order_id, delivery_status)
SELECT orders.order_id, 'pending'
FROM orders
LEFT JOIN deliveries ON deliveries.order_id = orders.order_id
WHERE deliveries.delivery_id IS NULL;

-- One order represents one physical delivery job. Add the unique replacement
-- before dropping the old index because the old index may support the order
-- foreign key on upgraded databases.
SET @add_delivery_unique_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE deliveries ADD UNIQUE KEY uq_deliveries_order_id (order_id)',
        'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'deliveries'
      AND INDEX_NAME = 'uq_deliveries_order_id'
);
PREPARE add_delivery_unique_statement FROM @add_delivery_unique_sql;
EXECUTE add_delivery_unique_statement;
DEALLOCATE PREPARE add_delivery_unique_statement;

SET @drop_delivery_index_sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE deliveries DROP INDEX idx_deliveries_order_id',
        'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'deliveries'
      AND INDEX_NAME = 'idx_deliveries_order_id'
);
PREPARE drop_delivery_index_statement FROM @drop_delivery_index_sql;
EXECUTE drop_delivery_index_statement;
DEALLOCATE PREPARE drop_delivery_index_statement;
