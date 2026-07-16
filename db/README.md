# Database installation and migrations

The project has two different database workflows. Do not combine them.

## Fresh database

1. Select the empty database in phpMyAdmin.
2. Import `database_schema.sql` once.
3. Import `hostinger_ph_locations_data.sql` once.

`database_schema.sql` is the canonical schema. It already contains the
structural changes represented by migrations 001-007.

## Existing database

Run `verify_database.sql` from phpMyAdmin's Import or SQL tab first. It is a
read-only report showing which numbered schema changes and reference records
are present. Use a migration only when that numbered change is missing from the database.
Select the correct database in phpMyAdmin before importing it. Migration 005
creates the normalized Philippine location tables, but it does not populate
them; import `hostinger_ph_locations_data.sql` afterward.

Do not import `database_schema.sql` over a populated database and do not run
the entire migrations folder after importing the canonical schema. Either can
produce duplicate objects or confusing partial-import errors.

## Current Hostinger database recovery

The current `u180682095_param_db` screenshot already shows the canonical tables,
20 products, and 391 variants. Its four Philippine location tables are present
but empty. For that database, import only:

`hostinger_ph_locations_data.sql`

The file uses `INSERT IGNORE`, has no `USE` statement, and can be imported after
selecting `u180682095_param_db` in phpMyAdmin. Expected counts after import:

| Table | Rows |
| --- | ---: |
| `ph_regions` | 17 |
| `ph_provinces` | 86 |
| `ph_localities` | 1,647 |
| `ph_barangays` | 42,042 |

Verify the result in phpMyAdmin's SQL tab:

```sql
SELECT 'ph_regions' AS table_name, COUNT(*) AS row_count FROM ph_regions
UNION ALL
SELECT 'ph_provinces', COUNT(*) FROM ph_provinces
UNION ALL
SELECT 'ph_localities', COUNT(*) FROM ph_localities
UNION ALL
SELECT 'ph_barangays', COUNT(*) FROM ph_barangays;
```

If the website still reports a connection error after this import, check the
deployed `.env` values. The database name and user are different settings:

```dotenv
DB_NAME=u180682095_param_db
DB_USER=u180682095_param
DB_PASS=your-hostinger-database-password
```

Use the database host and port shown by Hostinger. Do not put the database name
in `DB_HOST`.
