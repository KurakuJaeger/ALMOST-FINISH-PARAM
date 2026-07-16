-- Read-only deployment check. Select the target database in phpMyAdmin first.
-- This file does not create, alter, update, or delete anything.

SELECT DATABASE() AS selected_database;

SELECT '001' AS migration,
       'account permissions' AS schema_change,
       IF(COUNT(*) = 3, 'PASS', 'MISSING') AS result,
       CONCAT(COUNT(*), '/3') AS found
FROM permissions
WHERE permission_key IN (
    'account.view_own',
    'account.manage_own',
    'account.change_password'
)
UNION ALL
SELECT '002', 'readable views', IF(COUNT(*) = 5, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/5')
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'publicly_applicable_roles',
      'role_permissions_readable',
      'users_readable',
      'staff_applications_readable',
      'delivery_assignments_readable'
  )
UNION ALL
SELECT '003', 'support concerns table', IF(COUNT(*) = 1, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/1')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'support_concerns'
  AND TABLE_TYPE = 'BASE TABLE'
UNION ALL
SELECT '004', 'central storefront objects', IF(COUNT(*) = 3, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/3')
FROM (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_image_path'
    UNION ALL
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND TABLE_TYPE = 'BASE TABLE'
    UNION ALL
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants'
      AND INDEX_NAME = 'uq_product_variant_options' AND SEQ_IN_INDEX = 1
) AS migration_004_objects
UNION ALL
SELECT '005', 'normalized address objects', IF(COUNT(*) = 9, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/9')
FROM (
    SELECT TABLE_NAME AS object_name
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('ph_regions', 'ph_provinces', 'ph_localities', 'ph_barangays', 'application_addresses')
      AND TABLE_TYPE = 'BASE TABLE'
    UNION ALL
    SELECT COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_addresses'
      AND COLUMN_NAME IN ('region_code', 'province_id', 'locality_id', 'barangay_id')
) AS migration_005_objects
UNION ALL
SELECT '006', 'manual payment columns', IF(COUNT(*) = 3, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/3')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'payments'
  AND COLUMN_NAME IN ('submitted_amount', 'change_amount', 'submitted_at')
UNION ALL
SELECT '007', 'one-delivery-per-order index', IF(COUNT(*) = 1, 'PASS', 'MISSING'), CONCAT(COUNT(*), '/1')
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'deliveries'
  AND INDEX_NAME = 'uq_deliveries_order_id'
  AND NON_UNIQUE = 0
  AND SEQ_IN_INDEX = 1;

SELECT 'ph_regions' AS reference_table, COUNT(*) AS row_count, 17 AS expected_rows,
       IF(COUNT(*) = 17, 'PASS', 'LOAD LOCATION DATA') AS result
FROM ph_regions
UNION ALL
SELECT 'ph_provinces', COUNT(*), 86, IF(COUNT(*) = 86, 'PASS', 'LOAD LOCATION DATA')
FROM ph_provinces
UNION ALL
SELECT 'ph_localities', COUNT(*), 1647, IF(COUNT(*) = 1647, 'PASS', 'LOAD LOCATION DATA')
FROM ph_localities
UNION ALL
SELECT 'ph_barangays', COUNT(*), 42042, IF(COUNT(*) = 42042, 'PASS', 'LOAD LOCATION DATA')
FROM ph_barangays;

SELECT COUNT(*) AS orders_without_delivery,
       IF(COUNT(*) = 0, 'PASS', 'RUN MIGRATION 007') AS result
FROM orders
LEFT JOIN deliveries ON deliveries.order_id = orders.order_id
WHERE deliveries.delivery_id IS NULL;

-- These conflicts must be reviewed manually before migrations 004 or 007 can
-- add their unique indexes. The verifier never deletes business data.
SELECT 'duplicate product variants' AS conflict_check,
       COALESCE(SUM(duplicate_count - 1), 0) AS extra_rows,
       IF(COALESCE(SUM(duplicate_count - 1), 0) = 0, 'PASS', 'REVIEW BEFORE MIGRATION 004') AS result
FROM (
    SELECT COUNT(*) AS duplicate_count
    FROM product_variants
    GROUP BY product_id, size, color
    HAVING COUNT(*) > 1
) AS duplicate_variants
UNION ALL
SELECT 'duplicate deliveries per order',
       COALESCE(SUM(duplicate_count - 1), 0),
       IF(COALESCE(SUM(duplicate_count - 1), 0) = 0, 'PASS', 'REVIEW BEFORE MIGRATION 007')
FROM (
    SELECT COUNT(*) AS duplicate_count
    FROM deliveries
    GROUP BY order_id
    HAVING COUNT(*) > 1
) AS duplicate_deliveries;
