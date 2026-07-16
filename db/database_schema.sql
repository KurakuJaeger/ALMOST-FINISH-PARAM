
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_publicly_applicable TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id),
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    suffix VARCHAR(20) NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    email_verified_at DATETIME NULL,
    profile_image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_id (role_id),
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- token_type examples: email_verification, account_setup, password_reset.
CREATE TABLE IF NOT EXISTS auth_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_type VARCHAR(50) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auth_tokens_user_id (user_id),
    INDEX idx_auth_tokens_type (token_type),
    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- App must reject or revoke sessions when users.status is not active.
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_sessions_user_id (user_id),
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_regions (
    region_code VARCHAR(10) PRIMARY KEY,
    region_name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    region_code VARCHAR(10) NOT NULL,
    province_name VARCHAR(150) NOT NULL,
    UNIQUE KEY uq_ph_province_region_name (region_code, province_name),
    CONSTRAINT fk_ph_provinces_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_localities (
    locality_id INT AUTO_INCREMENT PRIMARY KEY,
    province_id INT NOT NULL,
    locality_name VARCHAR(150) NOT NULL,
    UNIQUE KEY uq_ph_locality_province_name (province_id, locality_name),
    CONSTRAINT fk_ph_localities_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    locality_id INT NOT NULL,
    barangay_name VARCHAR(180) NOT NULL,
    UNIQUE KEY uq_ph_barangay_locality_name (locality_id, barangay_name),
    CONSTRAINT fk_ph_barangays_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_no VARCHAR(50) NULL,
    street VARCHAR(100) NOT NULL,
    region_code VARCHAR(10) NOT NULL,
    province_id INT NOT NULL,
    locality_id INT NOT NULL,
    barangay_id INT NOT NULL,
    postal_code VARCHAR(20) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_addresses_user_id (user_id),
    CONSTRAINT fk_user_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_addresses_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code),
    CONSTRAINT fk_user_addresses_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id),
    CONSTRAINT fk_user_addresses_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id),
    CONSTRAINT fk_user_addresses_barangay FOREIGN KEY (barangay_id) REFERENCES ph_barangays(barangay_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    contact_type VARCHAR(30) NOT NULL DEFAULT 'Mobile',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_contacts_user_id (user_id),
    CONSTRAINT fk_user_contacts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- App-layer rule: requested_role_id must point only to roles where is_publicly_applicable = 1.
-- Do not allow public applicants to request Administrator.
CREATE TABLE IF NOT EXISTS staff_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    suffix VARCHAR(20) NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    requested_role_id INT NOT NULL,
    reason TEXT NULL,
    experience TEXT NULL,
    availability VARCHAR(100) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_applications_requested_role_id (requested_role_id),
    INDEX idx_staff_applications_reviewed_by (reviewed_by),
    INDEX idx_staff_applications_created_user_id (created_user_id),
    CONSTRAINT fk_staff_applications_requested_role
        FOREIGN KEY (requested_role_id) REFERENCES roles(role_id),
    CONSTRAINT fk_staff_applications_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    CONSTRAINT fk_staff_applications_created_user
        FOREIGN KEY (created_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS application_addresses (
    application_address_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    house_no VARCHAR(50) NULL,
    street VARCHAR(100) NOT NULL,
    region_code VARCHAR(10) NOT NULL,
    province_id INT NOT NULL,
    locality_id INT NOT NULL,
    barangay_id INT NOT NULL,
    postal_code VARCHAR(20) NULL,
    CONSTRAINT fk_application_addresses_application
        FOREIGN KEY (application_id) REFERENCES staff_applications(application_id) ON DELETE CASCADE,
    CONSTRAINT fk_application_addresses_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code),
    CONSTRAINT fk_application_addresses_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id),
    CONSTRAINT fk_application_addresses_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id),
    CONSTRAINT fk_application_addresses_barangay FOREIGN KEY (barangay_id) REFERENCES ph_barangays(barangay_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_category_id (category_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(30) NOT NULL,
    color VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_variants_product_id (product_id),
    UNIQUE KEY uq_product_variant_options (product_id, size, color),
    CONSTRAINT fk_product_variants_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_carts_user_id (user_id),
    CONSTRAINT fk_carts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cart_items_cart_id (cart_id),
    INDEX idx_cart_items_variant_id (variant_id),
    CONSTRAINT fk_cart_items_cart
        FOREIGN KEY (cart_id) REFERENCES carts(cart_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_variant
        FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    delivery_address_id INT NULL,
    delivery_address_snapshot TEXT NOT NULL,
    order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_delivery_address_id (delivery_address_id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_orders_delivery_address
        FOREIGN KEY (delivery_address_id) REFERENCES user_addresses(address_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    variant_id INT NULL,
    product_name_snapshot VARCHAR(150) NOT NULL,
    size_snapshot VARCHAR(30) NOT NULL,
    color_snapshot VARCHAR(50) NOT NULL,
    price_snapshot DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_variant_id (variant_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_variant
        FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    submitted_amount DECIMAL(10,2) NULL,
    change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reference_number VARCHAR(100) NULL,
    submitted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payments_order_id (order_id),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    assigned_to_user_id INT NULL,
    delivery_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    delivery_notes TEXT NULL,
    proof_image_path VARCHAR(255) NULL,
    assigned_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_deliveries_order_id (order_id),
    INDEX idx_deliveries_assigned_to_user_id (assigned_to_user_id),
    CONSTRAINT fk_deliveries_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_deliveries_assigned_user
        FOREIGN KEY (assigned_to_user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

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
    INDEX idx_support_customer (customer_id),
    INDEX idx_support_order (order_id),
    INDEX idx_support_assigned (assigned_to_user_id),
    CONSTRAINT fk_support_customer FOREIGN KEY (customer_id) REFERENCES users(user_id),
    CONSTRAINT fk_support_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    CONSTRAINT fk_support_assigned FOREIGN KEY (assigned_to_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS refund_requests (
    refund_request_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    requested_by_user_id INT NOT NULL,
    reviewed_by_user_id INT NULL,
    executed_by_user_id INT NULL,
    reason TEXT NOT NULL,
    customer_service_notes TEXT NULL,
    admin_notes TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    executed_at DATETIME NULL,
    INDEX idx_refund_requests_order_id (order_id),
    INDEX idx_refund_requests_requested_by (requested_by_user_id),
    INDEX idx_refund_requests_reviewed_by (reviewed_by_user_id),
    INDEX idx_refund_requests_executed_by (executed_by_user_id),
    CONSTRAINT fk_refund_requests_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_refund_requests_requested_by
        FOREIGN KEY (requested_by_user_id) REFERENCES users(user_id),
    CONSTRAINT fk_refund_requests_reviewed_by
        FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id),
    CONSTRAINT fk_refund_requests_executed_by
        FOREIGN KEY (executed_by_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    audit_log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_name VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_user_id (user_id),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (role_name, description, is_publicly_applicable) VALUES
('Customer', 'Buyer account for shopping on the storefront', 0),
('Customer Service', 'Handles customer concerns and refund requests', 1),
('Delivery', 'Handles assigned deliveries', 1),
('Administrator', 'Manages users, products, inventory, reports, and approvals', 0);

-- Bootstrap administrator account.
-- Temporary login: admin@param.test / Admin@12345
-- The app should force this account to change password after first login.
INSERT INTO users (
    first_name,
    middle_name,
    last_name,
    suffix,
    email,
    password_hash,
    role_id,
    status,
    must_change_password,
    email_verified_at
)
SELECT
    'System',
    NULL,
    'Administrator',
    NULL,
    'admin@param.test',
    '$2y$10$EmgS6dFJ.ojxNsn5xb4yCu/sIRd4V0hd4V36G/tDODRibVCrxrOry',
    r.role_id,
    'active',
    1,
    NOW()
FROM roles r
WHERE r.role_name = 'Administrator'
AND NOT EXISTS (
    SELECT 1
    FROM users u
    WHERE u.email = 'admin@param.test'
);

INSERT IGNORE INTO permissions (permission_key, description) VALUES
('account.register', 'Register as a customer'),
('account.confirm_email', 'Confirm registered email address'),
('account.view_own', 'View own account'),
('account.manage_own', 'Manage own account'),
('account.change_password', 'Change own password'),
('cart.manage_own', 'Manage own cart'),
('products.view', 'View products in the store'),
('orders.create', 'Create checkout orders'),
('orders.view_own', 'View own orders'),
('checkout.use', 'Use checkout page'),
('payment.view', 'View payment page'),
('support.request', 'Create support request'),
('refunds.request_own', 'Request refund for own order'),
('support.view', 'View support concerns'),
('support.reply', 'Reply to support concerns'),
('orders.view_support', 'View order information needed for support'),
('customers.view_support_info', 'View limited customer information for support'),
('refunds.request', 'Flag or request a refund with notes'),
('deliveries.view_assigned', 'View assigned deliveries only'),
('deliveries.update_status', 'Update assigned delivery status'),
('deliveries.view_limited_customer_info', 'View limited customer delivery information'),
('users.manage', 'Add or modify users'),
('roles.assign', 'Assign user roles'),
('products.manage', 'Add or modify products'),
('inventory.manage', 'Add or modify stock quantities'),
('prices.manage', 'Change product prices'),
('orders.manage', 'Manage orders'),
('applications.review', 'Review staff applications'),
('refunds.review', 'Review refund requests'),
('refunds.execute', 'Execute approved refunds'),
('reports.inventory.view', 'View inventory reports'),
('reports.audit_logs.view', 'View audit log reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'account.register',
    'account.confirm_email',
    'account.view_own',
    'account.manage_own',
    'account.change_password',
    'cart.manage_own',
    'products.view',
    'orders.create',
    'orders.view_own',
    'checkout.use',
    'payment.view',
    'support.request',
    'refunds.request_own'
)
WHERE r.role_name = 'Customer';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'account.view_own',
    'account.manage_own',
    'account.change_password',
    'products.view',
    'support.view',
    'support.reply',
    'orders.view_support',
    'customers.view_support_info',
    'refunds.request'
)
WHERE r.role_name = 'Customer Service';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'account.view_own',
    'account.manage_own',
    'account.change_password',
    'deliveries.view_assigned',
    'deliveries.update_status',
    'deliveries.view_limited_customer_info'
)
WHERE r.role_name = 'Delivery';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN (
    'account.view_own',
    'account.manage_own',
    'account.change_password',
    'products.view',
    'support.view',
    'orders.view_support',
    'customers.view_support_info',
    'users.manage',
    'roles.assign',
    'products.manage',
    'inventory.manage',
    'prices.manage',
    'orders.manage',
    'applications.review',
    'refunds.review',
    'refunds.execute',
    'reports.inventory.view',
    'reports.audit_logs.view'
)
WHERE r.role_name = 'Administrator';

CREATE OR REPLACE VIEW publicly_applicable_roles AS
SELECT
    role_id,
    role_name,
    description
FROM roles
WHERE is_publicly_applicable = 1;

CREATE OR REPLACE VIEW role_permissions_readable AS
SELECT
    r.role_id,
    r.role_name,
    p.permission_id,
    p.permission_key,
    p.description AS permission_description
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.role_id
JOIN permissions p ON rp.permission_id = p.permission_id;

CREATE OR REPLACE VIEW users_readable AS
SELECT
    u.user_id,
    CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) AS complete_name,
    u.email,
    r.role_name,
    u.status,
    u.must_change_password,
    u.email_verified_at,
    u.created_at
FROM users u
JOIN roles r ON u.role_id = r.role_id;

CREATE OR REPLACE VIEW staff_applications_readable AS
SELECT
    sa.application_id,
    CONCAT_WS(' ', sa.first_name, sa.middle_name, sa.last_name, sa.suffix) AS complete_name,
    sa.email,
    sa.phone,
    requested_role.role_name AS requested_role,
    sa.status,
    CONCAT_WS(' ', reviewer.first_name, reviewer.middle_name, reviewer.last_name, reviewer.suffix) AS reviewed_by,
    sa.reviewed_at,
    sa.created_at
FROM staff_applications sa
JOIN roles requested_role ON sa.requested_role_id = requested_role.role_id
LEFT JOIN users reviewer ON sa.reviewed_by = reviewer.user_id;

-- Use this view for delivery pages so delivery users receive masked phone numbers only.
-- Permission names do not mask data by themselves; PHP queries must avoid raw contact numbers.
CREATE OR REPLACE VIEW delivery_assignments_readable AS
SELECT
    d.delivery_id,
    d.order_id,
    d.assigned_to_user_id,
    CONCAT_WS(' ', customer.first_name, customer.middle_name, customer.last_name, customer.suffix) AS customer_name,
    o.delivery_address_snapshot,
    CASE
        WHEN primary_contact.contact_number IS NULL THEN NULL
        WHEN CHAR_LENGTH(primary_contact.contact_number) <= 4 THEN primary_contact.contact_number
        ELSE CONCAT(REPEAT('*', CHAR_LENGTH(primary_contact.contact_number) - 4), RIGHT(primary_contact.contact_number, 4))
    END AS masked_phone_number,
    d.delivery_status,
    d.delivery_notes,
    d.proof_image_path,
    d.assigned_at,
    d.delivered_at,
    d.created_at
FROM deliveries d
JOIN orders o ON d.order_id = o.order_id
JOIN users customer ON o.user_id = customer.user_id
LEFT JOIN user_contacts primary_contact
    ON primary_contact.user_id = customer.user_id
    AND primary_contact.is_primary = 1;

-- Canonical catalog seed shared by the landing page, storefront, and administrator tools.
INSERT IGNORE INTO categories (category_id, category_name) VALUES 
(1, 'Kids'), (2, 'Women'), (3, 'Men'), (4, 'Unisex');

-- Products
INSERT IGNORE INTO products (product_id, category_id, product_name, image_path, status) VALUES 
-- Kids (1)
(1, 1, 'Kids Pocketable UV Protection Parka', 'assets/catalog/prod1.avif', 'active'),
(5, 1, 'KIDS AIRism Cotton Graphic Crew Neck T-Shirt', 'assets/catalog/prod5.avif', 'active'),
(6, 1, 'KIDS AIRism Cotton Crew Neck T-shirt | Long Sleeve', 'assets/catalog/prod6.avif', 'active'),
(7, 1, 'KIDS Baggy Cargo Half Pants', 'assets/catalog/prod7.avif', 'active'),
(8, 1, 'KIDS Wide Fit Straight Jeans', 'assets/catalog/prod8.avif', 'active'),
-- Women (2)
(3, 2, 'Washed Cotton Boxy T-Shirt', 'assets/catalog/prod3.avif', 'active'),
(9, 2, 'Graphic T-Shirt', 'assets/catalog/prod9.avif', 'active'),
(10, 2, 'Ribbed Henley Neck T-Shirt | Long Sleeve', 'assets/catalog/prod10.avif', 'active'),
(11, 2, 'Pleated Skort', 'assets/catalog/prod11.avif', 'active'),
(12, 2, 'AIRism Cotton Short Sleeve T Dress', 'assets/catalog/prod12.avif', 'active'),
-- Men (3)
(16, 3, 'Knitted V Neck Cardigan', 'assets/catalog/prod16.avif', 'active'),
(17, 3, 'Ultra Stretch Sweat Shorts', 'assets/catalog/prod17.avif', 'active'),
(18, 3, 'Straight Jeans Selvedge', 'assets/catalog/prod18.avif', 'active'),
(19, 3, 'Cargo Shorts', 'assets/catalog/prod19.avif', 'active'),
(20, 3, 'Tank Top', 'assets/catalog/prod20.avif', 'active'),
-- Unisex (4)
(2, 4, 'Nylon Culotte', 'assets/catalog/prod2.avif', 'active'),
(13, 4, 'Milano Ribbed Shirt Collar Cardigan', 'assets/catalog/prod13.avif', 'active'),
(14, 4, 'Ultra Stretch Active Shorts', 'assets/catalog/prod14.avif', 'active'),
(15, 4, 'AIRism Cotton Oversized Striped T-Shirt', 'assets/catalog/prod15.avif', 'active'),
(4, 4, 'Washable 3D Knit Polo', 'assets/catalog/prod4.avif', 'active');

-- Product options and inventory
INSERT IGNORE INTO product_variants (product_id, size, color, price, stock_quantity, status) VALUES 

-- ==========================================
-- KIDS CATEGORY
-- ==========================================
-- Product 1: KIDS Pocketable UV Protection Parka (1490.00)
(1, '7-8Years old (130cm)', 'Black', 1490.00, 50, 'active'),
(1, '7-8Years old (130cm)', 'Beige', 1490.00, 50, 'active'),
(1, '7-8Years old (130cm)', 'Green', 1490.00, 50, 'active'),
(1, '7-8Years old (130cm)', 'Purple', 1490.00, 50, 'active'),
(1, '9-10yrs old (140cm)', 'Black', 1490.00, 50, 'active'),
(1, '9-10yrs old (140cm)', 'Beige', 1490.00, 50, 'active'),
(1, '9-10yrs old (140cm)', 'Green', 1490.00, 50, 'active'),
(1, '9-10yrs old (140cm)', 'Purple', 1490.00, 50, 'active'),
(1, '11-12yrs old (150cm)', 'Black', 1490.00, 50, 'active'),
(1, '11-12yrs old (150cm)', 'Beige', 1490.00, 50, 'active'),
(1, '11-12yrs old (150cm)', 'Green', 1490.00, 50, 'active'),
(1, '11-12yrs old (150cm)', 'Purple', 1490.00, 50, 'active'),
(1, '13yrs old (160cm)', 'Black', 1490.00, 50, 'active'),
(1, '13yrs old (160cm)', 'Beige', 1490.00, 50, 'active'),
(1, '13yrs old (160cm)', 'Green', 1490.00, 50, 'active'),
(1, '13yrs old (160cm)', 'Purple', 1490.00, 50, 'active'),

-- Product 5: KIDS AIRism Cotton Graphic Crew Neck T-Shirt (490.00)
(5, '3-4yrs old (110cm)', 'Green', 490.00, 50, 'active'),
(5, '5-6yrs old (120cm)', 'Green', 490.00, 50, 'active'),
(5, '7-8Years old (130cm)', 'Green', 490.00, 50, 'active'),
(5, '9-10yrs old (140cm)', 'Green', 490.00, 50, 'active'),
(5, '11-12yrs old (150cm)', 'Green', 490.00, 50, 'active'),
(5, '13yrs old (160cm)', 'Green', 490.00, 50, 'active'),

-- Product 6: KIDS AIRism Cotton Crew Neck T-shirt | Long Sleeve (590.00)
(6, '3-4yrs old (110cm)', 'Black', 590.00, 50, 'active'),
(6, '3-4yrs old (110cm)', 'White', 590.00, 50, 'active'),
(6, '3-4yrs old (110cm)', 'Pink', 590.00, 50, 'active'),
(6, '5-6yrs old (120cm)', 'Black', 590.00, 50, 'active'),
(6, '5-6yrs old (120cm)', 'White', 590.00, 50, 'active'),
(6, '5-6yrs old (120cm)', 'Pink', 590.00, 50, 'active'),
(6, '7-8Years old (130cm)', 'Black', 590.00, 50, 'active'),
(6, '7-8Years old (130cm)', 'White', 590.00, 50, 'active'),
(6, '7-8Years old (130cm)', 'Pink', 590.00, 50, 'active'),
(6, '9-10yrs old (140cm)', 'Black', 590.00, 50, 'active'),
(6, '9-10yrs old (140cm)', 'White', 590.00, 50, 'active'),
(6, '9-10yrs old (140cm)', 'Pink', 590.00, 50, 'active'),
(6, '11-12yrs old (150cm)', 'Black', 590.00, 50, 'active'),
(6, '11-12yrs old (150cm)', 'White', 590.00, 50, 'active'),
(6, '11-12yrs old (150cm)', 'Pink', 590.00, 50, 'active'),
(6, '13yrs old (160cm)', 'Black', 590.00, 50, 'active'),
(6, '13yrs old (160cm)', 'White', 590.00, 50, 'active'),
(6, '13yrs old (160cm)', 'Pink', 590.00, 50, 'active'),

-- Product 7: KIDS Baggy Cargo Half Pants (790.00)
(7, '3-4yrs old (110cm)', 'Beige', 790.00, 50, 'active'),
(7, '3-4yrs old (110cm)', 'Olive Green', 790.00, 50, 'active'),
(7, '5-6yrs old (120cm)', 'Beige', 790.00, 50, 'active'),
(7, '5-6yrs old (120cm)', 'Olive Green', 790.00, 50, 'active'),
(7, '7-8Years old (130cm)', 'Beige', 790.00, 50, 'active'),
(7, '7-8Years old (130cm)', 'Olive Green', 790.00, 50, 'active'),
(7, '9-10yrs old (140cm)', 'Beige', 790.00, 50, 'active'),
(7, '9-10yrs old (140cm)', 'Olive Green', 790.00, 50, 'active'),
(7, '11-12yrs old (150cm)', 'Beige', 790.00, 50, 'active'),
(7, '11-12yrs old (150cm)', 'Olive Green', 790.00, 50, 'active'),
(7, '13yrs old (160cm)', 'Beige', 790.00, 50, 'active'),
(7, '13yrs old (160cm)', 'Olive Green', 790.00, 50, 'active'),

-- Product 8: KIDS Wide Fit Straight Jeans (1290.00)
(8, '7-8Years old (130cm)', 'Gray', 1290.00, 50, 'active'),
(8, '7-8Years old (130cm)', 'Light Blue', 1290.00, 50, 'active'),
(8, '7-8Years old (130cm)', 'Blue', 1290.00, 50, 'active'),
(8, '9-10yrs old (140cm)', 'Gray', 1290.00, 50, 'active'),
(8, '9-10yrs old (140cm)', 'Light Blue', 1290.00, 50, 'active'),
(8, '9-10yrs old (140cm)', 'Blue', 1290.00, 50, 'active'),
(8, '11-12yrs old (150cm)', 'Gray', 1290.00, 50, 'active'),
(8, '11-12yrs old (150cm)', 'Light Blue', 1290.00, 50, 'active'),
(8, '11-12yrs old (150cm)', 'Blue', 1290.00, 50, 'active'),
(8, '13yrs old (160cm)', 'Gray', 1290.00, 50, 'active'),
(8, '13yrs old (160cm)', 'Light Blue', 1290.00, 50, 'active'),
(8, '13yrs old (160cm)', 'Blue', 1290.00, 50, 'active'),

-- ==========================================
-- WOMEN CATEGORY
-- ==========================================
-- Product 3: Washed Cotton Boxy T-Shirt (590.00)
(3, 'XS', 'Dark Brown', 590.00, 50, 'active'),
(3, 'S', 'Dark Brown', 590.00, 50, 'active'),
(3, 'M', 'Dark Brown', 590.00, 50, 'active'),
(3, 'L', 'Dark Brown', 590.00, 50, 'active'),
(3, 'XL', 'Dark Brown', 590.00, 50, 'active'),
(3, 'XXL', 'Dark Brown', 590.00, 50, 'active'),

-- Product 9: Graphic T-Shirt (990.00)
(9, 'XS', 'White', 990.00, 50, 'active'),
(9, 'XS', 'Gray', 990.00, 50, 'active'),
(9, 'XS', 'Black', 990.00, 50, 'active'),
(9, 'S', 'White', 990.00, 50, 'active'),
(9, 'S', 'Gray', 990.00, 50, 'active'),
(9, 'S', 'Black', 990.00, 50, 'active'),
(9, 'M', 'White', 990.00, 50, 'active'),
(9, 'M', 'Gray', 990.00, 50, 'active'),
(9, 'M', 'Black', 990.00, 50, 'active'),
(9, 'L', 'White', 990.00, 50, 'active'),
(9, 'L', 'Gray', 990.00, 50, 'active'),
(9, 'L', 'Black', 990.00, 50, 'active'),
(9, 'XL', 'White', 990.00, 50, 'active'),
(9, 'XL', 'Gray', 990.00, 50, 'active'),
(9, 'XL', 'Black', 990.00, 50, 'active'),
(9, 'XXL', 'White', 990.00, 50, 'active'),
(9, 'XXL', 'Gray', 990.00, 50, 'active'),
(9, 'XXL', 'Black', 990.00, 50, 'active'),

-- Product 10: Ribbed Henley Neck T-Shirt | Long Sleeve (990.00)
(10, 'XS', 'Off White', 990.00, 50, 'active'),
(10, 'XS', 'Light Gray', 990.00, 50, 'active'),
(10, 'XS', 'Black', 990.00, 50, 'active'),
(10, 'XS', 'Red', 990.00, 50, 'active'),
(10, 'XS', 'Natural', 990.00, 50, 'active'),
(10, 'XS', 'Dark Green', 990.00, 50, 'active'),
(10, 'S', 'Off White', 990.00, 50, 'active'),
(10, 'S', 'Light Gray', 990.00, 50, 'active'),
(10, 'S', 'Black', 990.00, 50, 'active'),
(10, 'S', 'Red', 990.00, 50, 'active'),
(10, 'S', 'Natural', 990.00, 50, 'active'),
(10, 'S', 'Dark Green', 990.00, 50, 'active'),
(10, 'M', 'Off White', 990.00, 50, 'active'),
(10, 'M', 'Light Gray', 990.00, 50, 'active'),
(10, 'M', 'Black', 990.00, 50, 'active'),
(10, 'M', 'Red', 990.00, 50, 'active'),
(10, 'M', 'Natural', 990.00, 50, 'active'),
(10, 'M', 'Dark Green', 990.00, 50, 'active'),
(10, 'L', 'Off White', 990.00, 50, 'active'),
(10, 'L', 'Light Gray', 990.00, 50, 'active'),
(10, 'L', 'Black', 990.00, 50, 'active'),
(10, 'L', 'Red', 990.00, 50, 'active'),
(10, 'L', 'Natural', 990.00, 50, 'active'),
(10, 'L', 'Dark Green', 990.00, 50, 'active'),
(10, 'XL', 'Off White', 990.00, 50, 'active'),
(10, 'XL', 'Light Gray', 990.00, 50, 'active'),
(10, 'XL', 'Black', 990.00, 50, 'active'),
(10, 'XL', 'Red', 990.00, 50, 'active'),
(10, 'XL', 'Natural', 990.00, 50, 'active'),
(10, 'XL', 'Dark Green', 990.00, 50, 'active'),
(10, 'XXL', 'Off White', 990.00, 50, 'active'),
(10, 'XXL', 'Light Gray', 990.00, 50, 'active'),
(10, 'XXL', 'Black', 990.00, 50, 'active'),
(10, 'XXL', 'Red', 990.00, 50, 'active'),
(10, 'XXL', 'Natural', 990.00, 50, 'active'),
(10, 'XXL', 'Dark Green', 990.00, 50, 'active'),

-- Product 11: Pleated Skort (1290.00)
(11, 'XS', 'Off White', 1290.00, 50, 'active'),
(11, 'XS', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'XS', 'Black', 1290.00, 50, 'active'),
(11, 'XS', 'Beige', 1290.00, 50, 'active'),
(11, 'S', 'Off White', 1290.00, 50, 'active'),
(11, 'S', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'S', 'Black', 1290.00, 50, 'active'),
(11, 'S', 'Beige', 1290.00, 50, 'active'),
(11, 'M', 'Off White', 1290.00, 50, 'active'),
(11, 'M', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'M', 'Black', 1290.00, 50, 'active'),
(11, 'M', 'Beige', 1290.00, 50, 'active'),
(11, 'L', 'Off White', 1290.00, 50, 'active'),
(11, 'L', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'L', 'Black', 1290.00, 50, 'active'),
(11, 'L', 'Beige', 1290.00, 50, 'active'),
(11, 'XL', 'Off White', 1290.00, 50, 'active'),
(11, 'XL', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'XL', 'Black', 1290.00, 50, 'active'),
(11, 'XL', 'Beige', 1290.00, 50, 'active'),
(11, 'XXL', 'Off White', 1290.00, 50, 'active'),
(11, 'XXL', 'Dark Gray', 1290.00, 50, 'active'),
(11, 'XXL', 'Black', 1290.00, 50, 'active'),
(11, 'XXL', 'Beige', 1290.00, 50, 'active'),

-- Product 12: AIRism Cotton Short Sleeve T Dress (1290.00)
(12, 'XS', 'Black', 1290.00, 50, 'active'),
(12, 'XS', 'Red', 1290.00, 50, 'active'),
(12, 'XS', 'Beige', 1290.00, 50, 'active'),
(12, 'XS', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'XS', 'Navy', 1290.00, 50, 'active'),
(12, 'S', 'Black', 1290.00, 50, 'active'),
(12, 'S', 'Red', 1290.00, 50, 'active'),
(12, 'S', 'Beige', 1290.00, 50, 'active'),
(12, 'S', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'S', 'Navy', 1290.00, 50, 'active'),
(12, 'M', 'Black', 1290.00, 50, 'active'),
(12, 'M', 'Red', 1290.00, 50, 'active'),
(12, 'M', 'Beige', 1290.00, 50, 'active'),
(12, 'M', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'M', 'Navy', 1290.00, 50, 'active'),
(12, 'L', 'Black', 1290.00, 50, 'active'),
(12, 'L', 'Red', 1290.00, 50, 'active'),
(12, 'L', 'Beige', 1290.00, 50, 'active'),
(12, 'L', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'L', 'Navy', 1290.00, 50, 'active'),
(12, 'XL', 'Black', 1290.00, 50, 'active'),
(12, 'XL', 'Red', 1290.00, 50, 'active'),
(12, 'XL', 'Beige', 1290.00, 50, 'active'),
(12, 'XL', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'XL', 'Navy', 1290.00, 50, 'active'),
(12, 'XXL', 'Black', 1290.00, 50, 'active'),
(12, 'XXL', 'Red', 1290.00, 50, 'active'),
(12, 'XXL', 'Beige', 1290.00, 50, 'active'),
(12, 'XXL', 'Dark Brown', 1290.00, 50, 'active'),
(12, 'XXL', 'Navy', 1290.00, 50, 'active'),

-- ==========================================
-- MEN CATEGORY
-- ==========================================
-- Product 16: Knitted V Neck Cardigan (1990.00)
(16, 'XS', 'Light Gray', 1990.00, 50, 'active'),
(16, 'XS', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'XS', 'Navy', 1990.00, 50, 'active'),
(16, 'S', 'Light Gray', 1990.00, 50, 'active'),
(16, 'S', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'S', 'Navy', 1990.00, 50, 'active'),
(16, 'M', 'Light Gray', 1990.00, 50, 'active'),
(16, 'M', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'M', 'Navy', 1990.00, 50, 'active'),
(16, 'L', 'Light Gray', 1990.00, 50, 'active'),
(16, 'L', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'L', 'Navy', 1990.00, 50, 'active'),
(16, 'XL', 'Light Gray', 1990.00, 50, 'active'),
(16, 'XL', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'XL', 'Navy', 1990.00, 50, 'active'),
(16, 'XXL', 'Light Gray', 1990.00, 50, 'active'),
(16, 'XXL', 'Dark Brown', 1990.00, 50, 'active'),
(16, 'XXL', 'Navy', 1990.00, 50, 'active'),

-- Product 17: Ultra Stretch Sweat Shorts (790.00)
(17, 'XS', 'Off White', 790.00, 50, 'active'),
(17, 'XS', 'Gray', 790.00, 50, 'active'),
(17, 'XS', 'Black', 790.00, 50, 'active'),
(17, 'XS', 'Dark Green', 790.00, 50, 'active'),
(17, 'XS', 'Navy', 790.00, 50, 'active'),
(17, 'S', 'Off White', 790.00, 50, 'active'),
(17, 'S', 'Gray', 790.00, 50, 'active'),
(17, 'S', 'Black', 790.00, 50, 'active'),
(17, 'S', 'Dark Green', 790.00, 50, 'active'),
(17, 'S', 'Navy', 790.00, 50, 'active'),
(17, 'M', 'Off White', 790.00, 50, 'active'),
(17, 'M', 'Gray', 790.00, 50, 'active'),
(17, 'M', 'Black', 790.00, 50, 'active'),
(17, 'M', 'Dark Green', 790.00, 50, 'active'),
(17, 'M', 'Navy', 790.00, 50, 'active'),
(17, 'L', 'Off White', 790.00, 50, 'active'),
(17, 'L', 'Gray', 790.00, 50, 'active'),
(17, 'L', 'Black', 790.00, 50, 'active'),
(17, 'L', 'Dark Green', 790.00, 50, 'active'),
(17, 'L', 'Navy', 790.00, 50, 'active'),
(17, 'XL', 'Off White', 790.00, 50, 'active'),
(17, 'XL', 'Gray', 790.00, 50, 'active'),
(17, 'XL', 'Black', 790.00, 50, 'active'),
(17, 'XL', 'Dark Green', 790.00, 50, 'active'),
(17, 'XL', 'Navy', 790.00, 50, 'active'),
(17, 'XXL', 'Off White', 790.00, 50, 'active'),
(17, 'XXL', 'Gray', 790.00, 50, 'active'),
(17, 'XXL', 'Black', 790.00, 50, 'active'),
(17, 'XXL', 'Dark Green', 790.00, 50, 'active'),
(17, 'XXL', 'Navy', 790.00, 50, 'active'),

-- Product 18: Straight Jeans Selvedge (1990.00)
(18, '28inch', 'Navy', 1990.00, 50, 'active'),
(18, '29inch', 'Navy', 1990.00, 50, 'active'),
(18, '30inch', 'Navy', 1990.00, 50, 'active'),
(18, '31inch', 'Navy', 1990.00, 50, 'active'),
(18, '32inch', 'Navy', 1990.00, 50, 'active'),
(18, '33inch', 'Navy', 1990.00, 50, 'active'),
(18, '34inch', 'Navy', 1990.00, 50, 'active'),
(18, '35inch', 'Navy', 1990.00, 50, 'active'),
(18, '36inch', 'Navy', 1990.00, 50, 'active'),

-- Product 19: Cargo Shorts (1290.00)
(19, 'XS', 'Beige', 1290.00, 50, 'active'),
(19, 'XS', 'Brown', 1290.00, 50, 'active'),
(19, 'XS', 'Olive', 1290.00, 50, 'active'),
(19, 'XS', 'Navy', 1290.00, 50, 'active'),
(19, 'S', 'Beige', 1290.00, 50, 'active'),
(19, 'S', 'Brown', 1290.00, 50, 'active'),
(19, 'S', 'Olive', 1290.00, 50, 'active'),
(19, 'S', 'Navy', 1290.00, 50, 'active'),
(19, 'M', 'Beige', 1290.00, 50, 'active'),
(19, 'M', 'Brown', 1290.00, 50, 'active'),
(19, 'M', 'Olive', 1290.00, 50, 'active'),
(19, 'M', 'Navy', 1290.00, 50, 'active'),
(19, 'L', 'Beige', 1290.00, 50, 'active'),
(19, 'L', 'Brown', 1290.00, 50, 'active'),
(19, 'L', 'Olive', 1290.00, 50, 'active'),
(19, 'L', 'Navy', 1290.00, 50, 'active'),
(19, 'XL', 'Beige', 1290.00, 50, 'active'),
(19, 'XL', 'Brown', 1290.00, 50, 'active'),
(19, 'XL', 'Olive', 1290.00, 50, 'active'),
(19, 'XL', 'Navy', 1290.00, 50, 'active'),
(19, 'XXL', 'Beige', 1290.00, 50, 'active'),
(19, 'XXL', 'Brown', 1290.00, 50, 'active'),
(19, 'XXL', 'Olive', 1290.00, 50, 'active'),
(19, 'XXL', 'Navy', 1290.00, 50, 'active'),

-- Product 20: Tank Top (790.00)
(20, 'XS', 'White', 790.00, 50, 'active'),
(20, 'XS', 'Black', 790.00, 50, 'active'),
(20, 'XS', 'Blue', 790.00, 50, 'active'),
(20, 'S', 'White', 790.00, 50, 'active'),
(20, 'S', 'Black', 790.00, 50, 'active'),
(20, 'S', 'Blue', 790.00, 50, 'active'),
(20, 'M', 'White', 790.00, 50, 'active'),
(20, 'M', 'Black', 790.00, 50, 'active'),
(20, 'M', 'Blue', 790.00, 50, 'active'),
(20, 'L', 'White', 790.00, 50, 'active'),
(20, 'L', 'Black', 790.00, 50, 'active'),
(20, 'L', 'Blue', 790.00, 50, 'active'),
(20, 'XL', 'White', 790.00, 50, 'active'),
(20, 'XL', 'Black', 790.00, 50, 'active'),
(20, 'XL', 'Blue', 790.00, 50, 'active'),
(20, 'XXL', 'White', 790.00, 50, 'active'),
(20, 'XXL', 'Black', 790.00, 50, 'active'),
(20, 'XXL', 'Blue', 790.00, 50, 'active'),

-- ==========================================
-- UNISEX CATEGORY
-- ==========================================
-- Product 2: Nylon Culotte (1990.00)
(2, 'XS', 'Black', 1990.00, 50, 'active'),
(2, 'XS', 'Beige', 1990.00, 50, 'active'),
(2, 'XS', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'XS', 'Navy', 1990.00, 50, 'active'),
(2, 'S', 'Black', 1990.00, 50, 'active'),
(2, 'S', 'Beige', 1990.00, 50, 'active'),
(2, 'S', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'S', 'Navy', 1990.00, 50, 'active'),
(2, 'M', 'Black', 1990.00, 50, 'active'),
(2, 'M', 'Beige', 1990.00, 50, 'active'),
(2, 'M', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'M', 'Navy', 1990.00, 50, 'active'),
(2, 'L', 'Black', 1990.00, 50, 'active'),
(2, 'L', 'Beige', 1990.00, 50, 'active'),
(2, 'L', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'L', 'Navy', 1990.00, 50, 'active'),
(2, 'XL', 'Black', 1990.00, 50, 'active'),
(2, 'XL', 'Beige', 1990.00, 50, 'active'),
(2, 'XL', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'XL', 'Navy', 1990.00, 50, 'active'),
(2, 'XXL', 'Black', 1990.00, 50, 'active'),
(2, 'XXL', 'Beige', 1990.00, 50, 'active'),
(2, 'XXL', 'Dark Brown', 1990.00, 50, 'active'),
(2, 'XXL', 'Navy', 1990.00, 50, 'active'),

-- Product 13: Milano Ribbed Shirt Collar Cardigan (2490.00)
(13, 'XS', 'Light Gray', 2490.00, 50, 'active'),
(13, 'XS', 'Black', 2490.00, 50, 'active'),
(13, 'XS', 'Olive', 2490.00, 50, 'active'),
(13, 'XS', 'Navy', 2490.00, 50, 'active'),
(13, 'S', 'Light Gray', 2490.00, 50, 'active'),
(13, 'S', 'Black', 2490.00, 50, 'active'),
(13, 'S', 'Olive', 2490.00, 50, 'active'),
(13, 'S', 'Navy', 2490.00, 50, 'active'),
(13, 'M', 'Light Gray', 2490.00, 50, 'active'),
(13, 'M', 'Black', 2490.00, 50, 'active'),
(13, 'M', 'Olive', 2490.00, 50, 'active'),
(13, 'M', 'Navy', 2490.00, 50, 'active'),
(13, 'L', 'Light Gray', 2490.00, 50, 'active'),
(13, 'L', 'Black', 2490.00, 50, 'active'),
(13, 'L', 'Olive', 2490.00, 50, 'active'),
(13, 'L', 'Navy', 2490.00, 50, 'active'),
(13, 'XL', 'Light Gray', 2490.00, 50, 'active'),
(13, 'XL', 'Black', 2490.00, 50, 'active'),
(13, 'XL', 'Olive', 2490.00, 50, 'active'),
(13, 'XL', 'Navy', 2490.00, 50, 'active'),
(13, 'XXL', 'Light Gray', 2490.00, 50, 'active'),
(13, 'XXL', 'Black', 2490.00, 50, 'active'),
(13, 'XXL', 'Olive', 2490.00, 50, 'active'),
(13, 'XXL', 'Navy', 2490.00, 50, 'active'),

-- Product 14: Ultra Stretch Active Shorts (1490.00)
(14, 'XS', 'Gray', 1490.00, 50, 'active'),
(14, 'XS', 'Black', 1490.00, 50, 'active'),
(14, 'XS', 'Green', 1490.00, 50, 'active'),
(14, 'XS', 'Navy', 1490.00, 50, 'active'),
(14, 'S', 'Gray', 1490.00, 50, 'active'),
(14, 'S', 'Black', 1490.00, 50, 'active'),
(14, 'S', 'Green', 1490.00, 50, 'active'),
(14, 'S', 'Navy', 1490.00, 50, 'active'),
(14, 'M', 'Gray', 1490.00, 50, 'active'),
(14, 'M', 'Black', 1490.00, 50, 'active'),
(14, 'M', 'Green', 1490.00, 50, 'active'),
(14, 'M', 'Navy', 1490.00, 50, 'active'),
(14, 'L', 'Gray', 1490.00, 50, 'active'),
(14, 'L', 'Black', 1490.00, 50, 'active'),
(14, 'L', 'Green', 1490.00, 50, 'active'),
(14, 'L', 'Navy', 1490.00, 50, 'active'),
(14, 'XL', 'Gray', 1490.00, 50, 'active'),
(14, 'XL', 'Black', 1490.00, 50, 'active'),
(14, 'XL', 'Green', 1490.00, 50, 'active'),
(14, 'XL', 'Navy', 1490.00, 50, 'active'),
(14, 'XXL', 'Gray', 1490.00, 50, 'active'),
(14, 'XXL', 'Black', 1490.00, 50, 'active'),
(14, 'XXL', 'Green', 1490.00, 50, 'active'),
(14, 'XXL', 'Navy', 1490.00, 50, 'active'),

-- Product 15: AIRism Cotton Oversized Striped T-Shirt (790.00)
(15, 'XS', 'White', 790.00, 50, 'active'),
(15, 'XS', 'Gray', 790.00, 50, 'active'),
(15, 'XS', 'Black', 790.00, 50, 'active'),
(15, 'XS', 'Navy', 790.00, 50, 'active'),
(15, 'S', 'White', 790.00, 50, 'active'),
(15, 'S', 'Gray', 790.00, 50, 'active'),
(15, 'S', 'Black', 790.00, 50, 'active'),
(15, 'S', 'Navy', 790.00, 50, 'active'),
(15, 'M', 'White', 790.00, 50, 'active'),
(15, 'M', 'Gray', 790.00, 50, 'active'),
(15, 'M', 'Black', 790.00, 50, 'active'),
(15, 'M', 'Navy', 790.00, 50, 'active'),
(15, 'L', 'White', 790.00, 50, 'active'),
(15, 'L', 'Gray', 790.00, 50, 'active'),
(15, 'L', 'Black', 790.00, 50, 'active'),
(15, 'L', 'Navy', 790.00, 50, 'active'),
(15, 'XL', 'White', 790.00, 50, 'active'),
(15, 'XL', 'Gray', 790.00, 50, 'active'),
(15, 'XL', 'Black', 790.00, 50, 'active'),
(15, 'XL', 'Navy', 790.00, 50, 'active'),
(15, 'XXL', 'White', 790.00, 50, 'active'),
(15, 'XXL', 'Gray', 790.00, 50, 'active'),
(15, 'XXL', 'Black', 790.00, 50, 'active'),
(15, 'XXL', 'Navy', 790.00, 50, 'active'),

-- Product 4: Washable 3D Knit Polo (2490.00)
(4, 'XS', 'Black', 2490.00, 50, 'active'),
(4, 'XS', 'Blue', 2490.00, 50, 'active'),
(4, 'XS', 'Navy', 2490.00, 50, 'active'),
(4, 'S', 'Black', 2490.00, 50, 'active'),
(4, 'S', 'Blue', 2490.00, 50, 'active'),
(4, 'S', 'Navy', 2490.00, 50, 'active'),
(4, 'M', 'Black', 2490.00, 50, 'active'),
(4, 'M', 'Blue', 2490.00, 50, 'active'),
(4, 'M', 'Navy', 2490.00, 50, 'active'),
(4, 'L', 'Black', 2490.00, 50, 'active'),
(4, 'L', 'Blue', 2490.00, 50, 'active'),
(4, 'L', 'Navy', 2490.00, 50, 'active'),
(4, 'XL', 'Black', 2490.00, 50, 'active'),
(4, 'XL', 'Blue', 2490.00, 50, 'active'),
(4, 'XL', 'Navy', 2490.00, 50, 'active'),
(4, 'XXL', 'Black', 2490.00, 50, 'active'),
(4, 'XXL', 'Blue', 2490.00, 50, 'active'),
(4, 'XXL', 'Navy', 2490.00, 50, 'active');
