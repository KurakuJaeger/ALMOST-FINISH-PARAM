-- Select the target database in phpMyAdmin before importing this migration.

UPDATE permissions
SET description = 'Manage own account'
WHERE permission_key = 'account.manage_own';

INSERT IGNORE INTO permissions (permission_key, description) VALUES
('account.view_own', 'View own account'),
('account.change_password', 'Change own password');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('account.view_own', 'account.manage_own', 'account.change_password')
WHERE r.role_name IN ('Customer', 'Customer Service', 'Delivery', 'Administrator');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key = 'products.view'
WHERE r.role_name IN ('Customer', 'Customer Service', 'Administrator');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('support.view', 'orders.view_support', 'customers.view_support_info')
WHERE r.role_name IN ('Customer Service', 'Administrator');
