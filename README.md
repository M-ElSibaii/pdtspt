# PDTs.pt

Welcome to the **PDTs.pt** open-source repository. PDTs.pt is a Product Data Templates (PDT) platform built with Laravel that lets industry create, review, query, view, and export PDTs in compliance with the relevant ISO standards. It is the reference implementation for Portugal's product-data-template layer.

## Key Features

- **PDT authoring & lifecycle** – create PDTs from construction objects, edit them as offline drafts, and make new versions of published PDTs through a draft that is reviewed (diff against the live version, version vs revision) before it is published.
- **Query, view & download** – browse PDTs, groups of properties, and the shared data dictionary, then download a PDT as **XLSX, XML, or JSON**.
- **ISO 23387 reference layer** – resolvable identity pages for **units**, **physical quantities (quantity kinds)**, and **dimensions**, each citing QUDT as the external authority, available as HTML or JSON via content negotiation.
- **Relationships & dependencies** – model ISO 23387 entity relationships (IsSubtypeOf, HasPart, …) and property dependencies between properties.
- **API access** – a read API for PDTs, the data dictionary, reference documents, groups of properties, construction objects, and the units reference tables.
- **Standards compliance** – the data model follows **EN ISO 23387** and the data dictionary follows **EN ISO 23386**.

## Tech Stack

| Layer           | Technology                                                                                 |
| --------------- | ------------------------------------------------------------------------------------------ |
| Framework       | Laravel 9 (PHP 8.0.2+)                                                                     |
| Auth            | Laravel Breeze + Sanctum                                                                   |
| Database        | MySQL                                                                                      |
| Exports         | PhpSpreadsheet, `maatwebsite/excel` (XLSX), custom XML/JSON exporters (`Iso23387Exporter`) |
| Front-end build | Vite 4, Tailwind CSS 3, Alpine.js 3, Axios                                                 |

## Project Structure

PDTs.pt follows Laravel's Model-View-Controller pattern.

- **Controllers** (`app/Http/Controllers`) – request handling, e.g. `ProductdatatemplatesController` (view/query/export), `PreviewWorkflowController` (draft editor), `PdtCreateController`, `PdtVersioningController` (starts a new-version draft), `AdminHubController`, `StatusCheckController`, `UnitsReferenceController` (reference layer), `RelationshipController`.
- **Models** (`app/Models`) – e.g. `productdatatemplates`, `properties`, `propertiesdatadictionaries`, `groupofproperties`, `constructionobjects`, `referencedocuments`, and the reference-layer models `Unit`, `PhysicalQuantity`, `Dimension`.
- **Services** (`app/Services`) – domain logic, e.g. `Iso23387Exporter`, `UnitsReference`, `RelationshipService`, `VersioningService`, `PdtInheritanceService`, `PropertyPickerService`.
- **Views** (`resources/views`) – Blade templates; the ISO 23387 reference pages live in `resources/views/reference/`.
- **Routes** – web routes in `routes/web.php`, API routes in `routes/api.php`.
- **Console commands** (`app/Console/Commands`) – seeding & data-reconciliation tools (see below).

## Getting Started

**Requirements:** PHP 8.0.2+, Composer, Node.js + npm, and MySQL.

1. **Clone the repository**

    ```bash
    git clone https://github.com/M-ElSibaii/pdtspt.git
    cd pdtspt
    ```

2. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

3. **Configure the environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Set your database connection in `.env`:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=pdt_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

4. **Set up the database**

    ```bash
    php artisan migrate --seed
    ```

5. **Build front-end assets**

    ```bash
    npm run dev      # watch/hot-reload during development
    # npm run build  # production build
    ```

6. **Run the development server**

    ```bash
    php artisan serve
    ```

Visit `http://localhost:8000` to access the application.

## API

The API is served under the `/api` prefix and returns JSON. Interactive documentation lives at **`/apidoc`** on the platform.

### Product Data Templates

| Method & path                   | Description                                                                                                          |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `GET /api/{pdtID}`              | Full PDT with property groups, properties, data-dictionary attributes, reference documents, and construction object. |
| `GET /api/{pdtID}/json`         | PDT exported as EN ISO 23387 JSON.                                                                                   |
| `GET /api/{pdtID}/xml`          | PDT exported as EN ISO 23387 XML.                                                                                    |
| `GET /api/productDataTemplates` | All PDTs.                                                                                                            |
| `GET /api/constructionObjects`  | All construction objects.                                                                                            |

### Data dictionary, reference documents & groups

| Method & path                        | Description                        |
| ------------------------------------ | ---------------------------------- |
| `GET /api/dataDictionary`            | All data-dictionary properties.    |
| `GET /api/dataDictionary/{Id}`       | A single data-dictionary property. |
| `GET /api/referenceDocuments`        | All reference documents.           |
| `GET /api/referenceDocuments/{GUID}` | A single reference document.       |
| `GET /api/groupsOfProperties`        | All groups of properties.          |
| `GET /api/groupsOfProperties/{Id}`   | A single group of properties.      |

### ISO 23387 units reference layer

| Method & path            | Description                                                                         |
| ------------------------ | ----------------------------------------------------------------------------------- |
| `GET /api/units`         | All units, each with its identity URI, physical quantity, dimension, and QUDT link. |
| `GET /api/quantityKinds` | All physical quantities (quantity kinds).                                           |
| `GET /api/dimensions`    | All dimensions with their 7 SI exponents (ISO 80000 order).                         |

Units, quantity kinds and dimensions are dictionary entities, so they all resolve through
the identifier scheme below:

```
GET /uri/0.1/unit/{code}       e.g. /uri/0.1/unit/mm
GET /uri/0.1/pq/{name}         e.g. /uri/0.1/pq/length
GET /uri/0.1/dim/{canonical}   e.g. /uri/0.1/dim/L1
```

`/units` is the browsable index (search by symbol, unit name or quantity, in either language).

How the layer is built (`units:seed-reference`): every bSDD unit gets its SI dimension from
its symbol (`App\Services\SiDimension`) and the physical quantity it measures
(`App\Services\QuantityKinds`): mm → length, N/mm² → pressure, W/(m·K) → thermal
conductivity. The dimension decides by default; units that share a dimension with another
quantity (N·m torque vs J energy) or have none (currencies, data sizes, environmental
indicators) are classified explicitly. A unit that fits nothing stays unclassified rather than
getting an invented quantity, and keeps its own dimension.

Dictionary values were typed by hand, so `UnitsReference::resolveCode()` also accepts
`m2`, `N/mm2`, `kg/m3`, `KN`, `W/mK`…: pages link them to the right unit, exports carry
its references, and `/uri/0.1/unit/m2` redirects to `/uri/0.1/unit/m²`. Older identifiers
keep working: `/pq/millimetre` (quantity kinds used to be one per unit) redirects to
`/pq/length`, `/pq/comprimento` to `/pq/length`, and `/dim/L` to `/dim/L1`.

## Identifiers

Every record in the dictionary has exactly one resolvable identifier. This is the form
that goes into exports and declarations, and every record the API returns carries it in a
`uri` field.

```
https://pdts.pt/uri/{dictVersion}/{entity}/{code}
https://pdts.pt/uri/{dictVersion}/{entity}/{code}/v{versionNumber}   (optional, pinned)
```

`dictVersion` is the dictionary release (`config/pdts.php`, currently `0.1`); `latest` is
accepted on input and redirects to the current release. The unversioned form is canonical
and resolves to the record's current state; `/v{n}` pins a known version. The language a
page is read in never changes a record's identifier.

| Entity                | Segment     | Code                     |
| --------------------- | ----------- | ------------------------ |
| Property              | `prop`      | `Name`                   |
| Product data template | `dt`        | `Name`                   |
| Construction object   | `class`     | `Name`                   |
| Group of properties   | `gop`       | `{id}-Name`              |
| Class property        | `classprop` | `{classPropertyId}-Name` |
| Reference document    | `doc`       | `Name`                   |
| Unit                  | `unit`      | `Name`                   |
| Quantity kind         | `pq`        | `Name`                   |
| Dimension             | `dim`       | `Name`                   |
| Enumerated value      | `enum`      | `{id}-Name`              |

Codes are the **Portuguese** name. This is a Portuguese dictionary: `namePt` in CamelCase
is the identifier that appears in exports, in DoPCs and in the canonical URI.

A record's **English name also resolves**, as an alias: a request using `nameEn` finds the
same record and is redirected (301) to the canonical Portuguese URI. It is a second way to
arrive at the one canonical URI, never a second identity — nothing the platform emits ever
uses the English form. An English name is not offered as an alias when it is also some
record's Portuguese name (the Portuguese name always wins) or when two records share it;
`uri:check` reports both cases. Codes carrying a record id (`gop`, `classprop`) are
canonicalised the same way, so an English or outdated name part also 301s to the canonical
spelling.

Name-only codes rely on those names being unique across what the dictionary publishes.
Where they are not, that is a data defect: it is reported, never resolved by renaming one
of the records.

```
php artisan uri:check              # report collisions in what is published
php artisan uri:check --scope=all  # audit every stored row
php artisan uri:check --sync       # also rebuild the uri_codes registry
```

`App\Services\UriService` is the only place that builds, parses or resolves an
identifier — nothing else concatenates one. `uri_codes` holds a `UNIQUE (entity, code)`
registry keyed on both names — each row marked `canonical` or `alias` — which is what
guarantees a code means exactly one record. It is additive and derived, never a source of
truth.

## Language

The site renders in Portuguese or English, chosen with the header toggle (remembered for
the session, PT by default) or with `?lang=pt` / `?lang=en` on any page. Record text comes
from the language columns the data already holds (`nameEn`/`namePt`,
`definitionEn`/`definitionPt`, `nameEnSc`/`namePtSc`); nothing is machine-translated, and
a record with no text in the chosen language falls back to the other. Interface wording
lives in `resources/lang/ui.php`, keyed by the Portuguese string.

## Admin workflow

Everything starts from **`/admin`**: drafts in progress, every template with its actions,
the data-health summary, the tools and user management.

**New version of a published template**

1. *New version* on a template → a confirmation page → *Create draft*. The current version
   is copied into an offline draft (same lineage GUID). The public site, exports and the
   API keep serving the live version; a draft is 404 for anyone but admins.
2. Edit the draft in the draft editor, over as many sessions as needed — every change is
   saved as you go. Groups are collapsed; a property opens to edit how this template uses it
   and its dictionary definition. Editing a definition shared with other templates makes a
   draft copy, so the live definition is untouched. A group added to the draft opens
   straight away with *Add properties* ready.
3. *Review & publish* shows every change against the live version and the bump it needs:
   a **version** when the template is renamed or groups are added/removed, otherwise a
   **revision** (which the admin may still publish as a version). Edited shared definitions
   are decided there (publish as a new version of the property, or keep the live one).
4. *Publish* makes the draft the live version with the next numbers, keeps the old one as
   InActive history, renumbers the changed groups, and re-derives the statuses of every
   property and construction object involved. A JSON backup is written to `storage/app`.

A draft can be discarded at any time without affecting the live version. Small corrections
that need no new version stay available as *Quick fix* (in-place edit of an Active template).

**Status health check** (`/admin/status-check`) compares every status with the rules
(newest published version Active, older ones InActive; groups follow their template; a
property or construction object is Active when an Active template uses it, Preview when only
drafts do, otherwise InActive), explains each proposed fix, and applies only the ones
ticked (with a backup). It also lists structural problems a status change cannot fix.
`php artisan pdts:recompute-status [--apply]` runs the same rules from the command line.

## Running the tests

The feature tests rebuild their database, so they run against a dedicated `pdtspt_test`
database (set in `phpunit.xml`) and refuse to run against any database whose name does not
end in `_test`:

```bash
mysql -u root -e "CREATE DATABASE pdtspt_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan test
```

## Maintenance commands

```bash
# Rebuild the reference layer (units from bSDD, their SI dimensions and physical quantities).
# Dry run first; --apply writes after a JSON backup in storage/app/units-reconcile.

php artisan units:seed-reference            # what would change
php artisan units:seed-reference --apply
php artisan units:map-qudt --apply          # QUDT references for new units
php artisan uri:check --sync                # refresh the identifier registry

# Optional: rewrite the dictionary's free-text physicalQuantity/dimension columns from each
# property's unit (pages and exports already derive them from the unit).
php artisan properties:reconcile-units      # dry run
php artisan properties:reconcile-units --apply

# Re-run the dictionary dedup

php artisan pdts:dedupe-dictionary
```

## Deduplication

Two review tools, both under Admin, both writing a JSON backup to `storage/app` before any
change and running the change in one transaction:

- **Dictionary properties** (`/admin/dedupe-dictionary`) groups rows that share a name in
  *either* language — `nameEn` or `namePt`, followed transitively — so a property
  duplicated only in Portuguese is caught too. Version variants (same GUID, different
  version) are shown read-only and never merged.
- **Reference documents** (`/admin/dedupe-reference-documents`) separates two signals.
  Rows sharing an `rdName` are true duplicates and are directly actionable. Rows sharing
  only a `title` are shown for review and refuse to merge without an explicit
  acknowledgement, because a standard's amendments and corrigenda keep the base standard's
  title (`EN 474-1:2006+A6:2019` and `EN 474-1:2006+A4:2013/AC:2014` are different
  documents). A merge repoints every citation — properties, groups, data templates and
  construction objects — onto the survivor before deleting the rest.

Both tools reject a "keep separate" edit that would not actually separate the group, and
both re-validate the group server-side so a stale page cannot apply a decision.

## Contributing

Contributions are welcome. If you have suggestions, bug reports, or feature requests, please open an issue or submit a pull request.

## License

PDTs.pt is open-source software licensed under the [MIT license](LICENSE).

## Contact

For any inquiries or support, contact us at [pdts.portugal@gmail.com](mailto:pdts.portugal@gmail.com).

Thank you for using PDTs.pt — we look forward to your feedback and contributions.
