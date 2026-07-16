# PARAM code guide

This guide is the quickest way for a student developer to trace the application.
The project uses plain PHP, PDO, HTML, CSS, and JavaScript. There is no PHP or
JavaScript framework to learn first.

## Request flow

Most features follow the same four steps:

1. A page in `public/` displays the interface.
2. JavaScript sends a request to a small API file in `public/`.
3. The API checks authentication, permissions, CSRF, and input.
4. A controller or service reads or changes the centralized database.

Keep validation and permission checks even when simplifying code. They are part
of the feature, not unnecessary complexity.

## Main folders

| Folder | Purpose |
| --- | --- |
| `public/landing/` | Public landing page |
| `public/store/` | Customer storefront, cart, checkout, and support |
| `public/AdminDashboard/` | Administrator interface |
| `public/DeliveryDashboard/` | Delivery queue and status updates |
| `public/CustomerServiceDashboard/` | Support and refund requests |
| `src/controllers/` | Handles one feature's application rules |
| `src/models/` | Reusable database operations for core records |
| `src/services/` | Shared workflows such as email, checkout, images, and auditing |
| `src/middleware/` | Login, role, permission, and CSRF checks |
| `db/` | Canonical schema, migrations, and location data |

## Admin dashboard scripts

The admin page loads five small scripts in this order:

| Script | Responsibility |
| --- | --- |
| `admin.js` | Shared API, text-safety, notices, and navigation helpers |
| `admin-users.js` | User and role management |
| `admin-inventory.js` | Products, size/color variants, stock, and image previews |
| `admin-operations.js` | Applications, refunds, reports, and audit display |
| `admin-init.js` | Starts each section after all loaders are registered |

Feature scripts communicate through the small `window.ParamAdmin` object. Add a
new shared helper only when two or more admin features genuinely need it.

## Key workflows

### Registration and password recovery

Start with `public/register.php`, then follow the relevant authentication
controller and `src/services/email-service.php`. One-time token hashes are kept
in `auth_tokens`; plain tokens only appear in emailed URLs.

### Shopping and checkout

Start with `public/store/shop.php`. Cart endpoints live beside the store pages.
`src/services/checkout-service.php` owns order totals, stock locking, checkout,
and manual payment recording. Its transaction is intentionally kept in one
service so partial orders cannot be saved.

One storefront product can have several `product_variants` rows. Each variant
represents one exact size and color combination with its own price and stock.

### Delivery

`public/DeliveryDashboard/delivery.js` calls `public/delivery-api.php`, which
uses `src/controllers/delivery-controller.php`. A delivery must be claimed before
that delivery user can update it.

### Customer support and refunds

Customers submit concerns through `public/store/ContactUs.php`. Customer Service
uses `public/customer-service-api.php`. Administrators review and complete manual
refunds through `src/controllers/refund-controller.php`.

### Audit trail

All new audit entries go through `src/services/audit-log-service.php`. Pass the
current PDO connection when the audit row must be saved in the same transaction
as the action being recorded.

## Simple coding rules

- Give each file one clear responsibility.
- Prefer descriptive names such as `$refundRequestId` over abbreviations.
- Use prepared SQL statements for values supplied by users.
- Keep database transactions around related writes that must succeed together.
- Escape values before placing them in HTML.
- Return early for invalid input or missing records.
- Avoid copying shared SQL; place genuinely reusable work in a small service.
- Do not edit files inside `public/store/images/prod6_files/`; they are copied
  third-party/static assets and are not representative of the team's source code.

When a file approaches 300 lines, check whether it contains more than one
feature. Large CSS files and a single transactional workflow may reasonably be
exceptions.
