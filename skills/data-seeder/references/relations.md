# Relations

Which converter applies is decided by the **TCA type of the field**, not by the YAML shape alone.
Check the field's TCA config before choosing a form.

## FAL files (`sys_file`)

```yaml
sys_file:
  - identifier: hero_jpg
    source: 'data/files/hero.jpg'   # relative to project root, or EXT:...
    storage: default                # identifier from fal.storages in the config
    folder: seed/                   # target folder inside the storage, created if missing
```

## File references (TCA `type: file`)

```yaml
tt_content:
  - identifier: hero
    pid: "{pages:home}"
    CType: image
    image:
      - identifier: hero-image          # recommended, keeps the reference stable
        file: "{sys_file:hero_jpg}"     # a bare identifier "hero_jpg" also works
        alternative: Lorem ipsum
        crop: '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}'
```

Filled automatically: `uid_local`, `uid_foreign`, `tablenames`, `fieldname`, `pid` (from the
parent) and `sorting_foreign` (10, 20, ...). Any other `sys_file_reference` column can be added.

Legacy form, still accepted, don't write new code like this:
`image: { sys_file_reference: [ { uid_local: ..., uid_foreign: ..., tablenames: ..., fieldname: ... } ] }`.

## Inline / IRRE children (TCA `type: inline` or select/group without MM)

New child records: a map of **child table → list of records** below the field.

```yaml
tt_content:
  - identifier: faq
    pid: "{pages:home}"
    CType: myext_accordion
    tx_myext_items:
      tx_myext_item:
        - identifier: faq-item-1
          header: First question
        - identifier: faq-item-2
          header: Second question
```

Filled automatically: `foreign_field` (points to the parent), `foreign_table_field`,
`foreign_match_fields`, the sorting field (`foreign_sortby` or the table's `sortby`, 10, 20, ...)
and `pid` (from the parent unless the parent is a page; set `pid` explicitly for children of
`pages`).

Existing records: a list of references.

```yaml
    tx_myext_related:
      - "{tx_myext_item:faq-item-1}"
```

Limitation: a list of references is **not supported** when the field has a `foreign_field`
(it throws). Create the children inline instead, or define them as separate records whose
`foreign_field` column points to the parent.

## Many-to-many (TCA with `MM`, e.g. `categories`)

Existing records:

```yaml
pages:
  - identifier: news
    pid: "{pages:home}"
    title: News
    categories:
      - "{sys_category:topic-a}"
      - "{sys_category:topic-b}"
```

New records (each one **must** have an `identifier`):

```yaml
    categories:
      sys_category:
        - identifier: topic-a
          title: Topic A
```

The MM rows (`uid_local`, `uid_foreign`, sorting, `MM_match_fields`, opposite side) are created
for you. Deprecated: `categories: { MM: { relations: [...] } }`.

## Single references and references in text

- Any string field that is exactly a reference becomes the uid: `parent: "{sys_category:topic-a}"`.
- References inside strings are replaced in place:
  `bodytext: '<a href="t3://page?uid={pages:contact}">Contact</a>'`, `link: "t3://page?uid={pages:home}"`.
- `{table:identifier:field}` returns another field, e.g. `{pages:home:slug}`.

## Order and forward references

Files come first, then pages (sorted by their `pid` dependencies), then all other tables.
References to records processed later are retried: the seeder runs up to three passes and
creates skeleton rows (only `pid` + `seed_identifier`) for records it cannot finish yet. If
something stays unresolved, add an `ordering` entry in the config
(see [configuration.md](configuration.md)).
