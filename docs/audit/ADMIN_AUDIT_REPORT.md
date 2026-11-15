# PDTs.pt admin audit: report

Branch `audit-admin`, based on `fix/units-quantities-and-site-audit` (e41c4c1), as agreed. **This branch depends on that one**: master (5f9c621) does not have its test fixtures or its DB guard, nor its fix for the open admin group (see Decisions, item 1). The process map is in [ADMIN_MAP.md](ADMIN_MAP.md) and the plan in [ADMIN_PLAN.md](ADMIN_PLAN.md).

## 1. Baseline (before any change)

| | |
|---|---|
| PHP | 8.1.6 (CLI, ZTS, Windows/XAMPP) |
| Laravel | 9.52.20; breeze 1.19.2, sanctum 3.3.3, maatwebsite/excel 3.1.62, phpspreadsheet 1.30.5 |
| Tests | Pest 1.23.1 on PHPUnit 9.6.22 |
| Node | 24.19.0 locally (CI uses 20) |
| `php artisan test` before changes | **84 passed**, 0 failed, 442.7 s |

Database safety:
- `.env` points at `127.0.0.1` / `pdtspt` (local MySQL, XAMPP), with `MAIL_MAILER=smtp` and `APP_DEBUG=true`.
- `phpunit.xml` overrides these for tests with `DB_DATABASE=pdtspt_test`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, and array cache and session. `tests/TestCase.php` refuses any database not named `*_test`.
- Every test and command in this audit ran against `pdtspt_test` only. The local `pdtspt` copy was read with `SELECT`/`SHOW` only, to find template 67 and to count unit spellings.
- No migration, seeder or writing artisan command touched `pdtspt`. No package was installed or changed. No queue worker ran.
- The new tests call `Http::preventStrayRequests()` and `Mail::fake()`. Nothing called bSDD or any other outside service. The only outside request was reading the public bSDD documentation on GitHub (§5).

## 2. Processes: pass or fail, and their tests

All new tests are in `tests/Feature/Audit/`. For **every** admin route (58), `AdminAccessTest` proves that a guest is sent to login, a logged-in non-admin is sent to `/`, an unverified admin is refused, and **no row is written**. It reads the route table, so a future admin route without the `admin` middleware fails the test.

| Process | Result | Tests |
|---|---|---|
| a) Sign-in, roles, reaching `/admin` | **pass** | AdminAccessTest (8), Breeze `Auth/*`, UserAdminFlagTest (6) |
| a) Granting and removing admin | **fail → fixed** (self-demotion allowed; partial form gave 500) | UserAdminFlagTest |
| b) Properties: create, edit (fork), add existing, remove; bSDD enums enforced; system fields not writable | **pass** | AdminProcessesTest: "a draft is authored over HTTP…", "non-bSDD data type or unit…", "refuses to touch a live template…" |
| b) Live mapping edit (in place, only `relationToOtherDataDictionaries`) | **pass** (rule as found) | "the live editor edits only usage text and mappings…" |
| b) Property dependencies | **pass** | "property dependencies are created and removed…" |
| c) Versions and revisions: same GUID, new Id, old row InActive and still readable by Id, `/api/{oldId}/json` and pinned `/v{n}` | **pass** | PdtDraftVersioningTest (24, existing), AdminProcessesTest: "a published new version keeps the GUID…", "a property edited in a draft becomes a new dictionary row…" |
| d) Groups: add, edit, remove (draft only) | **pass** | "a draft is authored over HTTP…", "refuses to touch a live template…" |
| e) Create a template (existing or new construction object), mandatory fields | **pass** | "creating a PDT…" (3 tests) |
| e) Reference documents: AJAX create | **pass** | "reference documents: the AJAX create…" |
| e) Reference documents: list form with an existing GUID | **fail (500) → fixed** | "…refuses a GUID that already exists…" |
| e) Relations (IsSubtypeOf/HasPart), self-subtype refused | **pass** | "relations between templates…" |
| e) Property picker upload: type and size | **pass** | "the property picker upload accepts spreadsheets only…" |
| f) Comments: author or admin only; survey | **pass** | SecurityTest (existing) |
| f) Status check: dry run, selected fixes only | **pass** | StatusHealthCheckTest (existing), "the status check with nothing selected…" |
| f) Deleting a draft: plan first, own rows only, never Active | **pass** | "deleting a brand-new draft…", "refuses to touch a live template…" |
| g) Dedupe pages; invalid decisions → 422, nothing written | **pass** | "dedupe pages render…" |
| g) Dedupe merge and definition editor | **pass as coded**, but they rewrite or delete identifiers → Decisions 2 and 3 | "rule as found: merging duplicates…", "rule as found: the dedupe definition editor…" |
| h) ISO 23387 JSON / XML / raw | **pass** | ApiContractTest |
| h) bSDD JSON | **pass**, except unit codes → **fail, not fixed** (Decision 4) | ApiContractTest (1 test skipped with its reason) |
| i) URIs | **pass** | ApiContractTest (4 URI tests), AdminProcessesTest (pinned versions) |

## 3. Fixed (one commit each, with its test)

- `6e28cfe`: `POST /referencedocuments/create` returned a **500** when the GUID already existed (GUID is the primary key). It now gives a validation error (`unique:referencedocuments,GUID`).
- `ac2938f`: `POST /admin/update` let an admin **change their own admin flag**, and returned a **500** when `isAdmin` or `subscribe` was missing or a user id was stale. A change to one's own flag is now refused (the rest of the form still saves, and the admin page shows the refusal), missing lists count as empty, and unknown ids are skipped. You asked for this one.
- `feae355`: `.github/workflows/ci.yml` (you asked for this). PHP 8.1 (composer.lock allows 8.1–8.3), a MySQL 8 service with `pdtspt_test` and a throwaway password set inline, `composer install`, `npm ci && npm run build` (the views need the Vite manifest), `php artisan migrate --force`, `php artisan test`. No secrets.

Test and documentation commits: `de514bd` (plan), `18c2d69` (access, process and contract tests), `5bcaa08` (performance measure), `1e236ac` (merge recorded as found), plus the map and this report.

No existing GUID, URI, name, version number or API field name was changed. The contract tests (key sets, URIs, field values) pass, and they were written against the code before either fix.

## 4. Fails and was not fixed

1. **bSDD export unit codes.** `Units` is copied raw from `propertiesdatadictionaries.units`. In the local data copy, **17 of 70** distinct Active unit values are not bSDD unit codes: `kg CO2 eq`, `m3`, `m2`, `W/m²K`, `W/mK`, `kg (PO4)^(3-) eq`, `N/mm2`, `mm2`, `cm2`, `cm4`, `cm3`, `KN/m3`, `months`, `disease incidence`, `kg P eq`, `kg CFC-11 eq`, `dimensionless`. The test "the bSDD export sends only bSDD unit codes…" is **skipped** with that reason. Fixing it means choosing a code for each spelling, and some have no bSDD code (Decision 4).
2. **N+1 in the ISO 23387 exporter** (measured, not failing; §7). For each property, `PropertyDependencyService::dependenciesFor()` and `Iso23387Exporter::propertySpecializationRef()` each run one query. Not fixed, because batching them changes the exporter's internals for no failing test. It's a safe follow-up behind the contract tests.

## 5. Contract tests (`tests/Feature/Audit/ApiContractTest.php`)

- **Dictagon `/api`**: `/productDataTemplates`, `/dataDictionary`, `/groupsOfProperties`, `/referenceDocuments` and `/constructionObjects` answer 200, and each row's key set equals the set at the start of the audit (the table columns plus `uri`). `/dataDictionary/{id}`, `/groupsOfProperties/{id}` and `/referenceDocuments/{GUID}` answer the same. A dictionary property carries `Id, GUID, uri, namePt, nameEn, namePtSc, nameEnSc, definitionPt, definitionEn, dataType, units, dimension, physicalQuantity, status, versionNumber, revisionNumber, dateOfVersion` with the right values. `/{id}`, `/{id}/json` and `/{id}/xml` answer 200 (the XML parses), and an unknown id answers 404.
- **ISO 23387 JSON**: it parses. Every `HasPropertyRef` of the template and of each group is in `Library.Properties`. Every property has a dashed GUID, a `_uri` under `https://pdts.pt/uri/0.1/prop/`, names in `en` and `pt`, and a `DataType`.
- **bSDD JSON**, checked against the bSDD JSON import model ([buildingSMART/bSDD, Documentation/bSDD JSON import model.md](https://github.com/buildingSMART/bSDD/blob/master/Documentation/bSDD%20JSON%20import%20model.md), read 2026-11-15). Checks:
  - the required dictionary fields (`OrganizationCode, DictionaryCode, DictionaryVersion, LanguageIsoCode, LanguageOnly, UseOwnUri, Classes, Properties`, plus `DictionaryName` and `DictionaryUri`, which `UseOwnUri=true` requires);
  - Property `Code`, `Name` and `DataType` ∈ {Boolean, Character, Integer, Real, String, Time}, with an `OwnedUri`;
  - Status ∈ {Active, Inactive};
  - ISO 8601 dates;
  - Class `Code`, `Name` and `ClassType` ∈ {Class, Material, GroupOfProperties, AlternativeUse}, with an `OwnedUri`;
  - every `ClassProperty.PropertyCode` is in `Properties`.

  `Units` must be codes from the bSDD units list: this fails for legacy data (§4). The export sends `ModelVersion` `"2.0"`; the documentation does not name the current value, so it was not checked.
- **URIs**:
  - `/uri/0.1/prop/<NamePt>` resolves as HTML and as JSON (`Accept`, `?format=json`, `.json`), with `@id` = `https://pdts.pt/uri/0.1/prop/<NamePt>`;
  - the English name answers 301 to the Portuguese URI, whose `@id` is the same;
  - a template's API `uri` resolves to itself;
  - `/v1` is pinned;
  - an unknown name, entity, dictionary version or version answers **404** (no 500).
- **Master template 67**: a fixture copies the structure described in the brief: Id 67, the master lineage GUID `230d9954097541b793f2a1fddb8bd0ad`, the group "Dados de conformidade" / "Compliance data", and the property `UrlDoPassaporteDigitalDoProduto` / `DigitalProductPassportUrl`. The test proves the template is in `/api/productDataTemplates`, that `/api/67/json` holds the group with the property in its `HasPropertyRef`, and that the property's URI `https://pdts.pt/uri/0.1/prop/UrlDoPassaporteDigitalDoProduto` is in `/api/dataDictionary` and resolves. The English name redirects to it.
- **Changed since the last bSDD export**: **not possible to list**. The platform records no export date: `exportDataToJsonPSETS` streams a file and stores nothing (there is no column, table or log for it). See Decision 7.

## 6. Security findings

| Check | Finding |
|---|---|
| Admin routes behind auth and role | All 58 have `auth`, `verified` and `admin`, and the test reads the route table. **On master they do not** (Decision 1). |
| CSRF | `VerifyCsrfToken` is in the `web` group, and `$except` is empty. |
| Writes without a login | Only contact, login, register, forgot-password, reset-password and the two read-only export POSTs (pinned by a test). Ignition's `/_ignition/*` routes also appear, because `APP_DEBUG=true` in local `.env`. Production must run with `APP_DEBUG=false`; I could not check that (§9). |
| Mass assignment | `User::$fillable` excludes `isAdmin`. `Contact` has a fillable list. The draft and live editors whitelist columns (`editableOf`, `CTX_EDITABLE`, `DICT_EDITABLE`), and tests prove that `GUID`, `status` and `propertyId` are ignored. |
| Uploads | Profile photo: image types, ≤ 5 MB, renamed (SecurityTest). Picker: xlsx/xls/csv, ≤ 4 MB (new test). There are no other upload points. |
| 500s | Two found and fixed (§3). Editor and dedupe failures answer 422 JSON. Unknown templates and URIs answer 404. |
| Public API | GET only (`POST` → 405), `throttle:api` 60/min per user or IP. |
| Secrets | No credentials in tracked files. `.env` is not tracked, `.env.example` has empty values, and `database/schema-pdtspt.sql` is schema only (no INSERTs). `test_api.php`, `test_exporter.php` and `test.http` hold no secrets and are outside `public/`. |
| Draft visibility | A new-version draft is hidden. A **brand-new** template's draft is public by Id (Decision 5). |
| `/api/dataDictionary/{Id}` | An unknown Id answers 200 with an empty body, not 404. Not changed, because Dictagon may rely on it (Decision 6). |

## 7. Performance (one large template: 12 groups, 300 properties)

Measured on the local test database after a warm-up (`LargePdtPerformanceTest`):

| Page | Time | Queries |
|---|---|---|
| `GET /api/{id}` | 3401 ms | 645 |
| `GET /api/{id}/json` | 1954 ms | 639 |
| `GET /api/{id}/xml` | 3342 ms | 639 |
| `POST /pdt-export/json/{id}` | 3470 ms | 640 |
| `GET /pdtsdownload/{id}` (template page) | 2171 ms | 12 |
| `GET /uri/0.1/dt/…` | 46 ms | 10 |
| `POST /exportdomainbsdd-psets` (whole domain: this template) | 1660 ms | 343 |

The exporter runs about 2 queries per property (§4.2). The template page uses few queries, but rendering takes about 2 s. The bSDD export runs about 1 query per property plus a reference-document lookup per class. On the real domain (66+ templates) it will grow linearly.

## 8. Decisions for Mohamad

1. **Merge `fix/units-quantities-and-site-audit` to master soon.** On master, `routes/web.php` declares the admin group as `['middleware' => 'auth', 'verified', 'admin']`. Laravel reads only `'auth'`, so **every `/admin` route, including `POST /admin/update` (grant admin) and the bSDD export, is open to any logged-in user**. If production runs master, that is live now.
2. **The dictionary merge removes identifiers.** "Merge" re-points every use of a duplicate to the survivor (its `properties.GUID` changes too) and **hard-deletes** the duplicate dictionary rows. That includes rows with another GUID, and same-GUID rows (earlier versions) with foreign-key checks off. Those GUIDs, their `/api/dataDictionary/{id}` and their pinned URIs stop resolving, and "keep separate" renames `nameEn`, which changes the English alias URI. That conflicts with "nothing that exists may change identifier or URI". Options: mark the duplicates InActive and point them at the survivor (`listOfReplacingProperties`) instead of deleting them, or restrict the tool to Preview rows.
3. **The dedupe definition editor bypasses versioning.** `POST /admin/dedupe-dictionary/dict` rewrites `definitionEn/Pt` of an Active row in place: no new version or revision, no `dateOfRevision`. The platform's own rule (`VersioningService::decidePropertyBump`) treats a definition change as a version. Options: route it through the draft/versioning flow, or allow it only for typo-level corrections and set the revision date.
4. **bSDD unit codes** (§4.1). Map the 17 legacy spellings to bSDD codes. The units reference layer (`UnitsReference`) already resolves many of them, so you'd decide whether to use it in the bSDD export, or to clean the data. Some (`kg CO2 eq`, `disease incidence`, `months`) have no bSDD code and need a call.
5. **A brand-new template's draft is public by Id** (`/api/{id}`, the template page). That is deliberate in `Lineage::abortIfHiddenDraft` ("keeps its existing visibility"). New-version drafts are hidden. Should brand-new drafts be hidden too?
6. **Unknown ids on `/api/dataDictionary/{Id}`, `/api/groupsOfProperties/{Id}` and `/api/referenceDocuments/{GUID}` answer 200 with an empty body.** A 404 would be cleaner, but Dictagon may test for an empty body, so this needs checking with Dictagon first.
7. **Record bSDD exports**, for example a row per export with its date and the file's hash. "Changed since the last export" then becomes a query.
8. **Publish decisions default to "keep".** On publish, a forked property with no decision, or an unrecognized one, keeps the live definition and **silently drops the edit**. The review page always sends one, but an explicit refusal of unknown values would be safer.
9. **Versioning rule vs ISO 23386.** The platform treats `dataType`, `units`, `dimension` and `physicalQuantity` changes as **revisions**. A unit or data-type change arguably changes a property's meaning, which in ISO 23386 calls for a new version. The standard's text is not in the repo, so I could not check this. Please confirm against EN ISO 23386:2020 §5.
10. **Performance**: batch the exporter's per-property queries (§4.2). The contract tests now guard the output.

## 9. Could not verify

- **Template 67 in production.** The local `pdtspt` copy ends at Id 66 (master is Id 37, v4.1) and has no `UrlDoPassaporteDigitalDoProduto`. The fixture follows the structure described in the brief (Id 67 on the master lineage, version 5.0 assumed, data type String assumed), not a copy of the production rows. Its URI and API shape are what the code produces for that structure.
- **Production settings**: `APP_DEBUG`, the mail driver, and which branch is deployed.
- **The bSDD import itself**: checked against the documentation, not by uploading.
- **CI**: the workflow has not run yet. The first push of `audit-admin` triggers it on GitHub, and I have not watched its result.
- **ISO 23386 / 23387 text**: neither standard is in the repo. Versioning is recorded as the code defines it.

## 10. Result

`php artisan test` on `audit-admin` against `pdtspt_test`: see the final line below.

**136 passed, 1 skipped (the bSDD unit-code check, §4.1), 0 failed**, 157.9 s. The baseline was 84 passed; the audit added 52 tests and 1 skipped.

One earlier full run showed `UserAdminFlagTest` failing with "table users doesn't exist". I had started a single test file while that suite was running, and its database refresh dropped the tables mid-run. A clean rerun with nothing else running passed. It is noted here because both runs used the same test database.

Pushed: branch `audit-admin` on `origin`. This report is the last commit; its parent is `1e236ac`. Nothing was merged.
