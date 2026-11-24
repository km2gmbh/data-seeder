# Troubleshooting

`typo3 database:seed` returns exit code 0 even when records failed. Always read the output.

| Output | Cause | Fix |
|---|---|---|
| `Processing all data failed. Failed nodes:` + one table per record | A reference could not be resolved after three passes: typo in an identifier, the referenced record doesn't exist, or ordering. | Check every `{table:identifier}` of the listed record against existing identifiers (`grep -rn "identifier: <id>"` in the data folder). If the record exists, add an `ordering` entry so the referenced table is processed first. |
| `Unable to build parent node for record. Check pid property value.` | `pid` missing, empty, or pointing to an unknown page identifier. | Set `pid: "{pages:<identifier>}"`; for root level `"{pages:root}"`. |
| `No record with identifier "x" in table y found.` / `Node for combined identifier ... cannot be resolved.` | Referenced identifier exists neither in YAML nor in the DB. | Fix the identifier or add the record. |
| `Invalid combined identifier given.` | Reference with more than three parts or invalid characters. | Use `{table:identifier}` or `{table:identifier:field}`, characters `a-z A-Z 0-9 - _`. |
| `Relations for existing records having a foreign_field defined in TCA are not supported, yet.` | List of references on an inline field with `foreign_field`. | Create the children inline below the field (see relations.md). |
| `New records created by MM relation needs an identifier given` | New record inside an MM field without `identifier`. | Add an identifier. |
| `Unknown column 'seed_identifier'` (SQL error) | Schema not updated after adding a table/extension. | Run `typo3 database:updateschema '*.add'`, then seed again. |
| Deprecation: `Using identifier without curly brackets id deprecated` | Old syntax `pages:home`. | Write `"{pages:home}"`. |
| Duplicate rows after a change | Record has no `identifier`, so its generated id changed. | Add a stable `identifier`; remove the stale rows (ask before deleting). |
| Value stored literally, e.g. `"{pages:home}"` in a text column | Field name not in TCA, or the reference is malformed. | Check the column name against TCA and the brace syntax. |

## Migrating old seed files

Safe mechanical changes, one file at a time, re-seed after each batch:

- `pid: pages:home` → `pid: "{pages:home}"` (all bare references, including `uid_local`, links).
- `image: { sys_file_reference: [ ... uid_local/uid_foreign/tablenames/fieldname ... ] }` →
  `image: [ { identifier: ..., file: "{sys_file:...}", <other fields> } ]`.
  Keep the `identifier` of each reference so existing rows are updated, not duplicated.
- `field: { MM: { relations: [...] } }` → `field: [...]`.
