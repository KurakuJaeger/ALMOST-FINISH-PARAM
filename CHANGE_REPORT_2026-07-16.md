# Param Change Report

**Project folder:** `Param/new`  
**Report date:** July 16, 2026  
**Scope:** Work completed during the landing page, buyer workflow, authentication, delivery, customer service, refund, and audit-log refactoring sessions.

## 1. Executive summary

The project was reorganized around one application folder and one canonical database. The public landing page and customer storefront are treated as separate experiences while sharing authentication, users, products, orders, and operational data.

The buyer requirements now have working registration, email verification, categorized products, cart, checkout, and a classroom-safe manual payment page. Order placement feeds a delivery queue. Customers can submit order-linked concerns, Customer Service can reply and request a refund, and Admin can review and manually complete that refund. Relevant operations are written to the audit log, which now displays the actor's role.

No full replacement ZIP was created. Work was made directly in the `new` folder. No repository push was performed by Codex.

## 2. Architecture and routing

### Completed

- Separated the public landing experience from the storefront.
- Kept one general login entry point and redirected users according to role.
- Added clean routes for landing, login, registration, verification, password reset, staff application, storefront, Admin, Delivery, and Customer Service.
- Kept application internals (`src`, `db`, `.env`, and similar files) inaccessible through Apache routes.
- Preserved the existing storefront and landing-page assets/content instead of replacing the peers' work wholesale.
- Kept deployment and Hostinger setup notes in `README.md`.

### Main routes

| Route | Purpose |
|---|---|
| `/` | Public landing page |
| `/login` | Shared authentication entry point |
| `/register` | Customer registration |
| `/apply` | Staff application |
| `/store` | Customer storefront |
| `/admin` | Administrator dashboard |
| `/delivery` | Delivery dashboard |
| `/support` | Customer Service dashboard |

## 3. Centralized database and normalization

### Completed

- Made `db/database_schema.sql` the canonical database definition.
- Removed the separate `products.sql` dependency from the `new` application.
- Centralized users, roles, permissions, addresses, contacts, products, variants, carts, orders, payments, deliveries, support concerns, refunds, and audit logs.
- Normalized Philippine locations into region, province/district, locality, and barangay reference tables.
- Added automatic postal-code support where reference data is available.
- Kept addresses atomic through separate location keys and address fields rather than storing the entire address as one registration value.
- Added a one-delivery-per-order database constraint.

### Migrations

| Migration | Purpose |
|---|---|
| `001_overlapping_permissions.sql` | Role/permission corrections |
| `002_readable_views.sql` | Readable role and permission views |
| `003_support_concerns.sql` | Support-concern workflow |
| `004_centralize_storefront_schema.sql` | Centralized storefront/product/order schema |
| `005_philippine_locations.sql` | Normalized Philippine location tables |
| `006_manual_payment_fields.sql` | Manual payment amount, change, and submission fields |
| `007_delivery_queue.sql` | Existing-order delivery backfill and unique delivery constraint |

## 4. Landing page and visual preservation

### Completed

- Kept the landing page separate from the storefront.
- Retained peer-created About Param, product-quality, statistics, team, imagery, and footer content.
- Restored/retained the footer branding and logo assets.
- Kept calls to action for registration, login, staff application, and storefront entry.
- Improved the shared login entry page presentation.
- Improved registration contrast and address-selection usability.

## 5. Authentication and account security

### Completed

- Centralized authentication for Customer, Customer Service, Delivery, and Administrator accounts.
- Added PHPMailer-backed customer email verification.
- Added expiring hashed verification tokens and resend cooldown behavior.
- Added forgot-password email links with expiring, one-time tokens.
- Added password-change notification emails.
- Corrected the reset-password success flow so a successfully used token shows completion instead of immediately showing “Request a fresh link.”
- Added CSRF checks to state-changing forms and APIs covered by the refactoring.
- Restricted role dashboards through role permissions.

### Configuration dependency

Email delivery requires valid SMTP values in `.env`. The code can be locally correct while outgoing email still fails if the hosting SMTP configuration is missing or rejected.

## 6. Buyer requirement checklist

| Requirement | Status | Implementation notes |
|---|---|---|
| Complete name | Complete | Registration stores atomic name fields. |
| Valid email address | Complete | Format validation and unique customer email. |
| Password and confirmation | Complete | Confirmation and password rules are enforced. |
| Complete address | Complete | Normalized Philippine location dropdowns plus street/house/postal data. |
| Contact numbers | Complete | Stored separately in `user_contacts`. |
| Email confirmation | Complete | PHPMailer and one-time verification links. |
| Categorized product list | Complete | Store catalog uses centralized categories/products/variants. |
| Add to cart | Complete | Product variants can be added to the active customer cart. |
| Cart page | Complete | Quantity and removal flows are connected. |
| Checkout page | Complete | Validates ownership, stock, address, totals, and duplicate submission nonce. |
| Payment page without API | Complete | COD, GCash, and demonstration card inputs use format checks and arithmetic only. |

## 7. Storefront, cart, checkout, and payment

### Completed

- Connected Add to Cart and Buy Now to their intended flows.
- Added a checkout page with customer details, saved delivery address, order summary, shipping fee, and payment-method selection.
- Rechecks inventory during the checkout transaction.
- Stores order-item snapshots so later product changes do not rewrite old order details.
- Deducts stock and closes a checked-out cart atomically.
- Prevents accidental duplicate checkout submissions with a nonce and completed-checkout session record.
- Added a separate manual payment page.
- Added basic amount-due, submitted amount, balance, and change calculations.
- Uses `submitted_unverified` for electronic demonstrations and `pending_collection` for COD.
- Does not store a full card number or CVV.

## 8. Delivery workflow

### Original problem

Checkout created orders and payments but did not create the corresponding row read by the Delivery dashboard.

### Completed

- New checkouts create a pending delivery task within the same transaction as the order.
- Existing orders were backfilled through migration `007_delivery_queue.sql`.
- Delivery staff can see unassigned work in a shared queue.
- Delivery staff can claim a task without hard-coding one courier during checkout.
- Claiming is atomic, preventing two delivery accounts from claiming the same job.
- Assigned delivery staff can set pending, assigned, picked up, in transit, delivered, or failed status.
- Delivery status updates synchronize the related order status.
- Delivery claim and status changes are audited.

### Current local sample state

- One delivery record exists for the sample order.
- The sample delivery is assigned and marked delivered.

## 9. Customer Service workflow

### Original problem

The Customer Service dashboard could read concerns, but customers had no working way to create one.

### Completed

- Converted the storefront Contact page into an authenticated support entry point while retaining the original store information and hiring section.
- Customers can select one of their own orders or submit a general concern.
- The newest customer order is selected by default to avoid a misleading “no order” appearance.
- Server-side ownership validation prevents a customer from linking another customer's order.
- Customers can see their own concern status and Customer Service response.
- Customer Service can reply, assign the concern to itself, and update its status.
- Customer Service can escalate an order-linked concern into a refund request.
- Support submission, response, and refund-request actions are audited.

### UI consistency

- Refactored the Contact/Support page around Bootstrap grid, form, card, badge, alert, and responsive utility classes.
- Reduced custom CSS to Param branding and targeted component overrides.
- Verified desktop and phone-width layouts with no horizontal overflow.
- Added cache-busting to the Contact page stylesheet.
- Fixed the support submit button's background, contrast, hover, active, and keyboard-focus states.

## 10. Admin refund workflow

### Original problem

Customer Service could request a refund, but Admin had no interface to confirm or reject it.

### Completed

- Added a Refund Reviews section to the Admin dashboard.
- Displays the request, customer, order, payment method, amount, reason, Customer Service notes, Admin notes, and status.
- Admin can approve or reject a pending request.
- An approved request can be marked manually refunded.
- Manual completion updates:
  - `refund_requests.status` to `refunded`;
  - the related payment status to `refunded`;
  - the related order status to `refunded`;
  - reviewer/executor user IDs and timestamps.
- Review and manual completion actions are audited.

### Important limitation

“Mark Refunded” records that the refund was handled manually. It does not move money because no payment API is connected.

## 11. Audit log improvements

### Completed

- Added the actor's role to each displayed Admin audit-log row.
- Roles are loaded from the centralized `roles` table.
- System or deleted-user entries fall back to `System`.
- Added a readable role badge in the Admin audit table.

### Limitation

The displayed role is the user's current role. It is not yet an immutable snapshot of the role at the exact time an older audit event occurred.

## 11.1 Admin product-image upload

### Completed

- Added a required product-image picker to the Admin Add Product form.
- Added an immediate local preview and selected filename.
- Added clear JPEG, PNG, WebP, and 5 MB guidance.
- Sends new products through a multipart request instead of embedding image data in JSON.
- Validates the real server-side MIME type, upload status, file size, and image dimensions.
- Limits accepted images to JPEG, PNG, and WebP, 5 MB, and 6000 × 6000 pixels.
- Generates a random server filename rather than trusting the original filename.
- Stores only a relative `uploads/products/...` path in the centralized products table.
- Shows existing catalog thumbnails in the Admin stock list.
- Deletes managed uploaded images when their product is deleted.
- Removes a newly uploaded file if database creation fails.
- Excludes runtime product uploads from version control while retaining the upload directory placeholder.

## 12. Validation performed

The following checks were used during implementation:

- PHP syntax checks for modified PHP controllers and pages.
- JavaScript syntax checks for Delivery, Customer Service, storefront, and Admin scripts.
- Direct database verification of orders, payments, deliveries, concerns, refunds, permissions, and audit data.
- Transactional or isolated temporary-data tests for:
  - customer support submission and reply visibility;
  - delivery queue creation and query visibility;
  - refund pending → approved → refunded transitions;
  - order/payment status synchronization.
- Authenticated browser checks for the customer support page.
- Desktop and mobile responsive checks.
- Temporary test accounts, orders, concerns, refunds, and related audit rows were removed after testing.

## 13. Current local workflow data

At report generation time, the local database contains:

| Record type | Count |
|---|---:|
| Users | 4 |
| Orders | 1 |
| Deliveries | 1 |
| Support concerns | 1 |
| Refund requests | 1 |

The remaining refund request is a real local project record for the sample order and remains pending for Admin review. It was not automatically approved during testing.

## 14. Known limitations and recommended next work

1. **Payment legitimacy:** No external payment API exists. GCash/card submissions cannot be proven legitimate.
2. **Refund transfer:** Admin completion is a manual record, not a financial transaction.
3. **Delivery proof:** The Delivery dashboard currently records a proof-image path; a complete secure upload/storage workflow is still recommended.
4. **Email hosting:** Verification and password emails depend on correct SMTP and public `APP_URL` configuration.
5. **Production domain:** Local functionality does not prove Hostinger DNS, document-root, permissions, PHP version, rewrite module, or SSL configuration. These must be tested on the actual host.
6. **Bootstrap scope:** Bootstrap consistency was applied to the customer Contact/Support refactor. A complete storefront-wide Bootstrap pass has not yet been performed.
7. **Audit role history:** Add an actor-role snapshot column if historical role immutability is required.
8. **Automated test suite:** Current checks are targeted smoke/integration tests. A repeatable PHPUnit/browser regression suite would improve team handoff.
9. **Order history:** A dedicated customer order-history/tracking page would make delivery, payment, support, and refund status easier to discover than relying on separate pages.

## 15. Deployment checklist

Before deploying the latest application:

1. Back up the production database.
2. Upload the contents of `new`, including `.htaccess`, without exposing `src`, `db`, or `.env` publicly.
3. Import the canonical schema for a fresh installation, or apply migrations `003` through `007` in order as required by the installation's current baseline.
4. Run the Philippine location importer if reference rows are not populated.
5. Configure `APP_URL`, database credentials, and SMTP values in `.env`.
6. Confirm PHP 8+ and Apache rewrite support.
7. Test registration verification, password reset, login role redirects, checkout, payment recording, delivery claim/update, support reply, refund review, and audit display using non-production test accounts.
8. Remove all test accounts and records before final submission.

## 16. Primary files changed or added

### Database and configuration

- `db/database_schema.sql`
- `db/migrations/004_centralize_storefront_schema.sql`
- `db/migrations/005_philippine_locations.sql`
- `db/migrations/006_manual_payment_fields.sql`
- `db/migrations/007_delivery_queue.sql`
- `.htaccess`
- `README.md`

### Authentication and registration

- `public/login.php`
- `public/register.php`
- `public/forgot-password.php`
- `public/reset-password.php`
- `public/verify-email.php`
- `public/verification-pending.php`
- `src/models/user.php`
- `src/services/email-service.php`

### Landing page and shared presentation

- `public/index.php`
- `public/landing.php`
- `public/views/landing/`
- `public/landing/assets/`
- `public/landing/includes/`
- `public/landing/assets/login.css`
- `public/landing/assets/verification.css`

### Profile and Philippine locations

- `public/location-options.php`
- `public/assets/location-selects.js`
- `public/store/Profile.php`
- `public/store/updateProfile.php`
- `src/models/philippine-location.php`
- `src/models/postal-code.php`

### Buyer/storefront

- `public/store/shop.php`
- `public/store/getProductDetails.php`
- `public/store/addToCart.php`
- `public/store/cart.php`
- `public/store/checkout.php`
- `public/store/payment.php`
- `src/services/checkout-service.php`

### Customer support

- `public/store/ContactUs.php`
- `public/store/contact_process.php`
- `public/store/css/ContactUs.css`
- `public/CustomerServiceDashboard/support.php`
- `public/CustomerServiceDashboard/support.js`
- `public/customer-service-api.php`
- `src/controllers/customer-service-controller.php`

### Delivery

- `public/DeliveryDashboard/delivery.php`
- `public/DeliveryDashboard/delivery.js`
- `public/DeliveryDashboard/delivery.css`
- `public/delivery-api.php`
- `src/controllers/delivery-controller.php`

### Admin

- `public/AdminDashboard/admin.php`
- `public/AdminDashboard/admin.js`
- `public/AdminDashboard/admin-users.js`
- `public/AdminDashboard/admin-inventory.js`
- `public/AdminDashboard/admin-operations.js`
- `public/AdminDashboard/admin-init.js`
- `public/AdminDashboard/admin.css`
- `public/api.php`
- `src/controllers/admin-user-controller.php`
- `src/controllers/refund-controller.php`
- `src/services/audit-log-service.php`
- `src/services/product-image-service.php`
- `public/uploads/products/.gitkeep`

### Readability refactor

- Split the previous 615-line admin script by feature responsibility.
- Split user administration and refund handling into focused controllers.
- Centralized repeated audit-log inserts in `AuditLogService`.
- Added `CODE_GUIDE.md` as a student-oriented map of folders and workflows.
- Removed mojibake from the changed admin interface text.
- Clarified the difference between storefront products and size/color variants.
- Added explicit color and size labels to admin inventory and inventory reports.
- Changed inventory updates to modify one selected variant instead of every
  variant belonging to the product.
- Added visible color names beside storefront swatches.
- Repaired a missing CSS closing brace that caused admin forms, responsive
  inventory cards, action buttons, tables, and footer sizing to inherit broken
  layout rules.
- Standardized admin summary cards with an auto-fitting responsive grid.

---

This report describes the current `new` folder and local database at the time of generation. It is a technical handoff report, not evidence that the public hosting environment has already been deployed or accepted.
