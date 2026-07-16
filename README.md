# Param application

New contributors should read [CODE_GUIDE.md](CODE_GUIDE.md) for the project map,
request flow, and simple coding conventions.

## Local URL

Copy `.env.example` to `.env`, keep the example `APP_URL`, and open:

`http://localhost/Param/new`

Apache routes the request into `public/`; `/public` should not be included in links.

## Database setup

### Fresh installation

1. Select the empty target database in phpMyAdmin.
2. Import `db/database_schema.sql`. It is the complete canonical schema and
   already includes the structural changes represented by migrations 001–007.
3. Import `db/hostinger_ph_locations_data.sql` to populate the normalized
   Philippine region, province/district, city/municipality, and barangay tables.

Do **not** run every file in `db/migrations/` after importing the canonical
schema. Migrations are incremental upgrades for databases created from older
versions of the project.

### Existing installation

Apply only the migrations that the existing database has not received, in
numeric order. The migration SQL files no longer contain a hard-coded database
name; select the target database in phpMyAdmin before importing them. Migration
005 creates location tables but does not populate them, so import
`db/hostinger_ph_locations_data.sql` afterward.

Local developers may run `C:\xampp\php\php.exe db\import_ph_locations.php`
instead of importing the generated location-data SQL file.

The location importer is idempotent. Its bundled 2019 dataset and MIT license
are documented in `db/data/README.md`.

## Customer email confirmation

New customer accounts remain in `pending_verification` status until the
customer follows the one-time confirmation link sent during registration.
Links are stored as SHA-256 hashes in `auth_tokens`, expire after 24 hours, and
can be resent after a 60-second cooldown.

Copy `.env.example` to `.env` and configure `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, and
`MAIL_FROM_NAME`. `APP_URL` must be the public application URL so confirmation
links point to the correct domain.

The same SMTP settings send one-hour forgot-password links and security
notifications after a password is changed. Public reset requests always return
a generic response so they do not reveal whether an email address is registered.

## Manual payment exercise

Run `db/migrations/006_manual_payment_fields.sql` on existing databases. The
checkout records COD, GCash, and demonstration card inputs using basic amount,
balance, and change arithmetic. No payment API is connected: electronic
submissions use `submitted_unverified`, and COD uses `pending_collection`.
Never enter or store a full card number or CVV in this classroom flow.

## Delivery queue

Run `db/migrations/007_delivery_queue.sql` on existing databases. Each order is
added to an unassigned queue, where a Delivery user can claim it before updating
its status. New checkouts create the delivery task atomically with the order.

## Hostinger deployment

1. Upload the **contents** of this folder (including `.htaccess`) into `public_html`.
2. Copy `.env.example` to `.env` and set `APP_URL=https://your-domain.com`.
3. In Hostinger, create/import the MySQL database and put its host, port, database name, user, and password in `.env`.
4. Set the mail values in `.env` for customer confirmation and staff account emails.
5. Use PHP 8.0 or newer and confirm Apache `mod_rewrite` is enabled (it is enabled on standard Hostinger shared hosting).

If this app is installed in a subdirectory, include it in `APP_URL`, for example `https://your-domain.com/param`.

Do not copy or commit `src/config/mail.local.php`; it contains machine-local SMTP configuration.
