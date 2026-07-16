-- Select the target database in phpMyAdmin before importing this migration.

CREATE OR REPLACE VIEW publicly_applicable_roles AS
SELECT role_id, role_name, description FROM roles WHERE is_publicly_applicable = 1;

CREATE OR REPLACE VIEW role_permissions_readable AS
SELECT r.role_id, r.role_name, p.permission_id, p.permission_key, p.description AS permission_description
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.role_id
JOIN permissions p ON rp.permission_id = p.permission_id;

CREATE OR REPLACE VIEW users_readable AS
SELECT u.user_id, CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) AS complete_name,
       u.email, r.role_name, u.status, u.email_verified_at, u.created_at
FROM users u JOIN roles r ON u.role_id = r.role_id;

CREATE OR REPLACE VIEW staff_applications_readable AS
SELECT sa.application_id, CONCAT_WS(' ', sa.first_name, sa.middle_name, sa.last_name, sa.suffix) AS complete_name,
       sa.email, sa.phone, requested_role.role_name AS requested_role, sa.status,
       CONCAT_WS(' ', reviewer.first_name, reviewer.middle_name, reviewer.last_name, reviewer.suffix) AS reviewed_by,
       sa.reviewed_at, sa.created_at
FROM staff_applications sa
JOIN roles requested_role ON sa.requested_role_id = requested_role.role_id
LEFT JOIN users reviewer ON sa.reviewed_by = reviewer.user_id;

CREATE OR REPLACE VIEW delivery_assignments_readable AS
SELECT d.delivery_id, d.order_id, d.assigned_to_user_id,
       CONCAT_WS(' ', customer.first_name, customer.middle_name, customer.last_name, customer.suffix) AS customer_name,
       o.delivery_address_snapshot,
       CASE WHEN primary_contact.contact_number IS NULL THEN NULL
            WHEN CHAR_LENGTH(primary_contact.contact_number) <= 4 THEN primary_contact.contact_number
            ELSE CONCAT(REPEAT('*', CHAR_LENGTH(primary_contact.contact_number) - 4), RIGHT(primary_contact.contact_number, 4)) END AS masked_phone_number,
       d.delivery_status, d.delivery_notes, d.proof_image_path, d.assigned_at, d.delivered_at, d.created_at
FROM deliveries d
JOIN orders o ON d.order_id = o.order_id
JOIN users customer ON o.user_id = customer.user_id
LEFT JOIN user_contacts primary_contact ON primary_contact.user_id = customer.user_id AND primary_contact.is_primary = 1;
