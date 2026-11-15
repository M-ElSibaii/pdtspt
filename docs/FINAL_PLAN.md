# PDTs.pt: final branch plan (`prop-origin-filter`)

Branch `prop-origin-filter`, from `audit-admin` (e3adb7c). This is the last branch before production, so it carries everything that goes live. The audit report and map are in [audit/](audit/).

## Rules for this branch

- Tests run on `pdtspt_test` only; the `*_test` guard in `tests/TestCase.php` stays.
- Nothing is run against the `.env` database: no migrate, no seed, no writing command.
- No outside calls, apart from the `npm install` that step 1 needs to sync the lock file.
- No package version changes. The lock sync in step 1 is the only exception.
- No deploy. Push only `prop-origin-filter`. No merge.
- **Stop rule:** if any export, API field, GUID or URI changes, stop, commit, push and report. A test is never adjusted to make it pass.

## 1. Fix CI (first commit, done: 9826364)

The CI run on e3adb7c failed at `npm ci`. Node 20's npm refused the lock, because `yaml@1.10.3` was hoisted to the top level while `postcss-load-config` declares an optional peer `yaml@^2.4.2`. Running `npm install` moves `yaml@1.10.3` under `tw-elements`, the only package that needs it. No version in `package.json` changed, and only `package-lock.json` is committed. Locally, `npm ci` and `npm run build` pass. The branch was pushed so that CI runs.

## 2. Dedupe merge warning (keeping the hard delete)

The merge itself does not change. What changes is the confirmation shown before it.

- **Dictionary properties** (`/admin/dedupe-dictionary`): the merge panel lists every row that will be permanently deleted: its GUID, its version (`v1.0`) and its URI (built by `UriService`, the `uri` the API returns). It updates as the survivor choice changes. Below the list is one sentence: these identifiers will stop resolving, and this cannot be undone. A checkbox must be ticked before *Apply* is enabled for a merge.
- **Reference documents** (`/admin/dedupe-reference-documents`): the same, with GUID, version ("not versioned": reference documents have no version number) and URI.
- `DictionaryDedupeService::enrichRow` gains a `uri` key for the admin page. This is the admin JSON, not the public API.
- **The tick is checked in the browser, not on the server.** A server check would make the two existing merge tests ("rule as found: merging duplicates…") fail unless they were edited to send the flag, and editing them is not allowed here. This goes to Mohamad as a decision.
- The dedupe definition editor and the bSDD unit codes stay as they are. Both are listed under "Later" in the report.

## 3. Property origin filter

### Pages that list a PDT's properties

| Page | Route | Lists | Filter |
|---|---|---|---|
| Public PDT page | `GET /pdtsdownload/{id}` (the "Ver PDT" button on the PDT view) | own and inherited groups | yes |
| Review and comments | `GET /pdtssurvey/{id}` (signed in) | own and inherited groups | yes |
| Draft editor (new templates *and* new-version drafts, so it is also the versioning editor) | `GET /admin/previews/{id}` | own groups only | yes; inherited groups added read-only |
| Quick fix (in-place edit of an Active template) | `GET /admin/pdt/{id}/edit` | own groups only | yes; inherited groups added read-only |

Not included:
- **`pdtview`** (`/uri/0.1/dt/{code}`, `viewPdt`) shows the template's attributes and a group count, not its properties. Its "Ver PDT" button leads to `/pdtsdownload/{id}`, which is where the filter goes.
- **`/admin/previews/{id}/review`** lists changes against the live version, not the property list.

**Decision taken:** the two admin editors query only the template's own groups, so a filter there would have a single origin. They now also show the inherited groups (own first, then each ancestor, then the master), read-only and collapsed, with a note to edit them in the template they come from. Without that, the filter has nothing to filter. It goes to Mohamad as a decision.

### Origins

`PdtInheritanceService::origins($pdt, $groups)` labels each effective group with its origin and returns the ordered list of origins:

- `own`: "Este modelo" / "This template", white swatch (`#ffffff`, the legend's existing colour for own rows);
- one per IsSubtypeOf ancestor, keyed by its lineage GUID and labelled with its name in the page language;
- `master`: "Modelo mestre" / "Master template", for groups owned by the master lineage (`230d…0ad`), whether it is reached as an ancestor or through the legacy fallback.

Colours come from `PdtInheritanceService::sourceColors()`, called with the same ordered name list the pages use now, so every swatch matches the existing row colour. Each origin carries its group ids and its property count. The controllers pass this as one new view variable (`$originFilter`). No existing variable, export or API response changes.

### Behaviour (Alpine.js, in the browser)

- A shared component, `<x-origin-filter>`, renders the bar: one real `<input type="checkbox">` per origin with a `<label>`, the swatch and the count, all checked by default; a "Mostrar todas" / "Show all" button; an `aria-live="polite"` line "A mostrar N de M propriedades" / "Showing N of M properties"; and one line when nothing is selected.
- Groups and property rows carry `data-origin`. A group whose origin is unchecked is hidden. On the public pages a heading can merge groups from several origins (they are grouped by `gopNamePt`), so a heading carries the list of its groups' origins and hides only when none of them is checked.
- Hiding uses a CSS class (`.origin-off`), so it does not conflict with the jQuery group toggles or the editor's search.
- The choice is kept in the URL with `history.replaceState`: `?origins=own,master,<guid>`. With all origins checked, the parameter is dropped. With none checked, it is `?origins=` and the empty-state line shows. Unknown keys are ignored.
- Next to the download buttons, one line: "As transferências (XLSX, XML, JSON) e a API incluem sempre todas as propriedades." / "Downloads (XLSX, XML, JSON) and the API always include every property." The CSV/XLS file is built from the hidden flat table, which the filter does not touch.
- Labels are in PT and EN through `resources/lang/ui.php` (`Lg::t`).

## 4. Deployment note

`docs/DEPLOY.md`: the steps in order, as given in the brief, and how to roll back (restore the dump, check out the previous commit, rebuild the caches).

## Tests (all on `pdtspt_test`)

1. **Exports unchanged** (`tests/Feature/Final/ExportSnapshotTest.php`): a fixture with fixed Ids, GUIDs and dates (master ← parent ← child, through IsSubtypeOf). For each template it records `/api/{id}`, `/api/{id}/json`, `/api/{id}/xml`, `POST /pdt-export/json|xml/{id}`, and the five `/api` collections. The snapshots are written **before any step 2 or 3 code**, when this branch differs from e3adb7c only in `package-lock.json`, so they are audit-admin's output. The test compares byte for byte.
2. **Origins**: each group carries the right origin, the counts are right, and the pages render the bar with every origin, its swatch and its count.
3. **Merge confirmation**: the page lists each GUID, version and URI that a merge would delete, with the sentence and an unticked checkbox, and *Apply* stays disabled until the box is ticked.
4. The whole suite passes.

## Report

`docs/FINAL_REPORT.md`: one line per commit, the test result, screenshots if they can be taken, decisions for Mohamad, and the pushed commit.
