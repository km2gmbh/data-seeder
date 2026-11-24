# Configuration file

Default location is `seeder.yaml` in the project root; any other path is passed with
`typo3 database:seed --config <path>`.

```yaml
data:
  loader: yaml
  options:
    type: folder            # folder: every *.yaml below path (recursive); file: a single file
    path: data/database/

fal:
  storages:                 # the first storage is the default storage
    - identifier: default   # used as `storage:` in sys_file records
      storagePath: fileadmin/   # relative to project root, or absolute when starting with /
      # baseUri: optional

ordering:                   # optional changes to the processing order
  fe_users:
    after: [ "fe_groups", "pages" ]
  tx_myext_item:
    before: [ "sys_file_reference" ]
    after: [ "tt_content" ]

operations:
  envFileGenerator:         # writes uids into an env file, e.g. for the 404 page
    envFiles:
      pids:
        path: pids.env
        values:
          PID_404: "{pages:404:uid}"
          IS_SEEDED_DATA: "1"
```

## ordering

Default order: `sys_file`, then `pages`, then every other table. Add an entry when a table
references records of another table that would otherwise be processed later and the seed
reports failed nodes. `before` and `after` take lists of table names.

## Variables

Defined under `_variables` in any record file and used as `{variable:<name>}` in any string
value, including inside longer strings. Variables may contain references.

## operations.envFileGenerator

Each key under `envFiles` is one file. `values` accept static strings, references
(`"{pages:404:uid}"`) and variables. The older object form
`PID_404: { identifier: pages:404, field: uid }` still works.

Custom operations, data loaders, processors and property converters are registered via PHP
attributes; see the extension documentation (Developers chapter) if a project needs one.

## Commands

| Command | Effect |
|---|---|
| `typo3 database:seed [--config <path>]` | Create/update records. Safe to repeat. |
| `typo3 database:seed --reset [--no-interaction]` | **Wipes the database**, then seeds. Ask first. |
| `typo3 database:flush [--delete-tables] [--no-interaction]` | **Wipes the database.** Ask first. |
