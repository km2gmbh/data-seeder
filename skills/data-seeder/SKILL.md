---
name: data-seeder
description: Write, change and debug seed data for TYPO3 projects that use the km2/data-seeder extension (`typo3 database:seed`, a seeder.yaml / seed.yaml config, YAML record files under a folder such as data/database/). Use when adding test or demo content, example records for a new content element, page, inline/IRRE child, category, FAL file or file reference, when a `database:seed` run reports failed nodes, or when migrating old seed syntax. Not for TYPO3 distributions, data.xml / impexp exports or Initialisation folders.
---

# km2/data-seeder

The extension creates and updates database records from YAML files **without fixed UIDs**.
Every record gets a string `identifier`; the extension stores it in an extra column
`seed_identifier` that it adds to every table during `database:updateschema`. Other records
reference it as `{table:identifier}`. Running the seed again **updates** the records with the
same identifier instead of duplicating them.

## Before you write anything

1. Find the config: `composer.json` scripts (often `seed` / `flush-seed`), a `seeder.yaml`
   in the project root (the default) or the file passed via `--config`. Read it: `data.options.path`
   is where record files live, `fal.storages` lists storage identifiers, `ordering` the table order.
2. Read two or three existing record files near where yours will go and copy their layout,
   naming, `sorting` steps and folder structure. Project convention beats the examples here.
3. Look up the target table's TCA (columns, `type`, `foreign_table`, `foreign_field`, `MM`)
   before writing fields. The converter is chosen from the TCA field type, so a wrong
   field name or type silently ends up as a plain value.

## Record syntax

```yaml
_variables:                       # optional, reusable values
  lorem: "<p>Lorem ipsum <a href=\"t3://page?uid={pages:home}\">link</a></p>"

pages:
  - identifier: home              # a-z A-Z 0-9 - _ ; unique per table
    pid: "{pages:root}"           # {pages:root} = 0
    sorting: 100
    doktype: 1
    title: Home
    is_siteroot: 1                # slug is generated

tt_content:
  - identifier: home-intro
    pid: "{pages:home}"
    colPos: 0
    sorting: 10
    CType: text
    header: Welcome
    bodytext: "{variable:lorem}"
```

- A table is a root key holding a list of records; fields are database column names.
- References: `"{table:identifier}"` resolves to the uid, `"{table:identifier:field}"` to another
  field. Always quote them and always use the curly braces: the form without braces
  (`pages:home`) still works but is deprecated. References also work inside strings
  (`t3://page?uid={pages:home}`).
- Always set `identifier`. Without it an md5 of the properties is used, so any change to the
  record creates a new row instead of updating the old one.
- `pid` is required for everything except child records created through a relation (they
  inherit it).

Relations (file references, inline/IRRE children, MM, categories) have a short form that fills
`uid_foreign`, `tablenames`, `fieldname`, `foreign_field`, `sorting` and `pid` for you.
**Read [references/relations.md](references/relations.md) before writing any relation.**
The config file (`data`, `fal`, `ordering`, `operations`) is in
[references/configuration.md](references/configuration.md).

## Run and verify

1. Schema first, so new tables get `seed_identifier`:
   `typo3 database:updateschema '*.add'` (prefix with `ddev`/`vendor/bin/` as the project does).
2. `typo3 database:seed --config <path>`, or the project's composer script.
3. **Read the output. The command exits 0 even when nodes failed.** Look for
   `Processing all data failed. Failed nodes:` or `Unable to build parent node`, and for
   deprecation notices. See [references/troubleshooting.md](references/troubleshooting.md).
4. Check the result: query the table for `seed_identifier = '<identifier>'`, or open the page
   in the frontend/backend.

## Safety

- `database:seed --reset`, `database:flush` and scripts like `flush-seed` **wipe the whole
  database**. Never run them without asking the user first, and only on a local/dev instance.
- Seed files are shared by the team through VCS: keep identifiers stable. Renaming one
  orphans the old row and creates a new one.
- Files referenced via `sys_file.source` must exist in the repository (or under `EXT:`).
