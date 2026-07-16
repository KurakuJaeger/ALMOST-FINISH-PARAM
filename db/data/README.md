# Philippine location reference data

`philippine_locations_2019.json` comes from Jacob Flores' MIT-licensed
`philippine-regions-provinces-cities-municipalities-barangays` repository.
The included data reflects the May 2019 election geography and may not contain
locations created or renamed after that date.

After applying `database_schema.sql` (or migration `005_philippine_locations.sql`
for an existing database), populate the normalized reference tables with:

```powershell
C:\xampp\php\php.exe db\import_ph_locations.php
```

The importer is idempotent and can be rerun safely.

## Postal-code reference

`philippine_postal_codes.json` is the offline dataset used by the guided address
form. It was generated from the MIT-licensed `use-postal-ph` version 1.1.14
dataset, with PHLPost's official ZIP Code Locator retained as the authoritative
reference. The package source snapshot and license are included as
`use-postal-ph.js` and `USE_POSTAL_PH_LICENSE`.

Regenerate the normalized JSON after replacing the source snapshot with:

```powershell
node db\build_postal_library_data.js
```

The UI auto-fills only an unambiguous locality or barangay match. Where PHLPost
assigns several codes to one city, it presents the valid choices and leaves the
field editable instead of guessing.
