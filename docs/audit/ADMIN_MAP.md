# PDTs.pt admin map

Branch `audit-admin` (from `fix/units-quantities-and-site-audit` e41c4c1). Sources: `php artisan route:list`, `routes/web.php`, `routes/api.php`, the controllers in `app/Http/Controllers`, the services in `app/Services`, `app/Http/Middleware/AdminMiddleware.php`, the models.

## Roles and gates

| Role | How it is set | What it reaches |
|---|---|---|
| Guest | — | Public pages, `/api/*` (read-only, `throttle:api`), `/uri/*`, the public JSON/XML downloads (`POST /pdt-export/{json,xml}/{id}`), contact form (`throttle:5,1`), login/register/password reset. |
| User (verified) | Registration + e-mail verification | Profile, comments (`/comments/{id}`, `/deletefeedback`: own comments only), survey (`/pdtssurvey/*`, `throttle:10,1`). |
| Admin | `users.isAdmin = 1`, set by another admin on the admin page (`POST /admin/update`) | Everything under `auth` + `verified` + `admin` (`AdminMiddleware`: `isAdmin == 1`, otherwise redirect to `/`). 58 routes. |

- No policies, observers, model events, jobs or listeners exist (`app/Policies`, `app/Observers`, `app/Jobs`, `app/Events` are absent; no `boot()` hooks on the models). Every write happens directly in a controller or service, mostly through `DB::table()`.
- The queue is `sync`, and the only mail is comment notifications and the contact form (tests use `Mail::fake()`). The platform makes no outgoing HTTP call (bSDD is uploaded by hand from the downloaded file).
- `isAdmin` is not fillable on `User`. The only code that writes it is `UserController::updateUsers`.

"Tests" means feature tests that exist after this audit. `tests/Feature/Audit/AdminAccessTest.php` proves guest → login and user → `/` with no row written, for **every** admin route below.

## a) Sign-in, roles, reaching /admin

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `GET/POST /login`, `/register`, `/forgot-password`, `/reset-password` | Breeze `Auth\*` | guest | `users`, `password_resets` | yes (Breeze `Auth/*Test`) |
| `GET /verify-email/{id}/{hash}` | `VerifyEmailController` | user | `users.email_verified_at` | yes |
| `GET /admin` | `AdminHubController@index` | admin (verified) | — | yes (AdminPagesTest, AdminAccessTest) |
| `POST /admin/update` | `UserController@updateUsers` | admin | `users.isAdmin`, `users.subscribe` | yes (UserAdminFlagTest) |

## b) Properties (ISO 23386 dictionary): `propertiesdatadictionaries`

Properties are never edited in place on live data, with two exceptions (the live mapping editor and the dedupe tool, below). They are created and changed inside a draft template.

| Route | Controller → service | Role | Writes | Tests |
|---|---|---|---|---|
| `POST /admin/previews/{pdt}/property/add-new` | `PreviewWorkflowController@addNewProperty` → `PreviewService::addNewProperty` | admin | `propertiesdatadictionaries` (Preview, v1.0, new or given GUID, must be unique), `properties` | yes |
| `POST /admin/previews/{pdt}/property/add-existing` | `…@addExistingProperty` | admin | `properties` | yes |
| `POST /admin/previews/{pdt}/property/edit` | `…@editProperty` → `editPreviewProperty` (forks a shared row: same GUID, new Id, Preview) | admin | `propertiesdatadictionaries`, `properties.propertyId` | yes |
| `POST /admin/previews/{pdt}/property/... remove` (`/context/remove`) | `…@removeContext` | admin | deletes `properties` row (draft only) | yes |
| `POST /admin/pdt/{pdt}/edit/mapping` | `ActivePdtEditController@updateDictMapping` | admin | `propertiesdatadictionaries.relationToOtherDataDictionaries` **in place on an Active row** | yes |
| `GET/POST/DELETE /admin/property-dependencies…` | `PropertyDependencyController` → `PropertyDependencyService` | admin | `property_dependencies`, `property_dependency_targets` (soft delete) | yes |

Attributes: GUID (32 hex characters, `GuidGenerator`), `namePt/nameEn` (the URI code is `namePt`; `nameEn` is an alias), `namePtSc/nameEnSc`, `definitionPt/En`, `dataType` (bSDD enum, required), `units` (bSDD unit list, optional), `dimension`, `physicalQuantity`, `status` (Preview/Active/InActive), `versionNumber/revisionNumber`, `dateOfVersion/dateOfRevision`, plus the rest of the ISO 23386 columns. Server-side checks: the mandatory list in `SchemaAttributeService::RULE_MANDATORY`, enums in `BsddEnums`, and per-table whitelists (`editableOf`).

## c) Versions and revisions: `VersioningService`, `DraftVersionService`, `PreviewService::applyPublish`

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `GET /admin/pdt/{pdt}/new-version` | `PdtVersioningController@start` | admin | — (confirmation page) | yes |
| `POST /admin/pdt/{pdt}/new-version` | `…@create` → `DraftVersionService::startDraft` | admin | copy of the template, groups and properties as Preview (same GUIDs, new Ids) | yes (PdtDraftVersioningTest, AdminProcessesTest) |
| `GET /admin/previews/{pdt}/review`, `/publish-plan` | `PreviewWorkflowController@review/publishPlan` | admin | — | yes |
| `POST /admin/previews/{pdt}/publish` | `…@publishApply` → `PreviewService::applyPublish` | admin | draft → Active; earlier version → InActive (+ depreciation text); forked properties → new dict version or discarded; JSON backup in `storage/app` | yes |

Rule as found (the standard's text is not in the repo):
- A lineage is one GUID. Every version or revision is a **new row with the same GUID and a new Id**; the earlier row stays, set to `InActive`.
- Property: a change to a name or definition (`nameEn/Pt`, `nameEn/PtSc`, `definitionEn/Pt`) → **version**, or a revision if flagged as a correction. A change to an attribute (`dataType`, `units`, `dimension`, `physicalQuantity`, value lists, …) → **revision** (`decidePropertyBump`).
- Group: a name change → version; a definition or attribute change → revision. Template: renaming it, or adding or removing a group → version; a usage-text change → revision. The admin may raise a revision to a version, but cannot lower a required version.
- `dateOfVersion` = the publish day on a version bump; a revision keeps the earlier `dateOfVersion` and sets `dateOfRevision`.
- The earlier version stays readable via `/api/{oldId}`, `/api/{oldId}/json` and the pinned URI `/uri/0.1/{entity}/{code}/v{n}`. The unpinned URI resolves to the current version.

## d) Groups of properties: `groupofproperties`

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `POST /admin/previews/{pdt}/gop/add` | `PreviewWorkflowController@addGop` | admin | `groupofproperties` (Preview, v1.0) | yes |
| `POST /admin/previews/{pdt}/gop` | `…@editGop` | admin | `groupofproperties` (whitelisted columns, draft only) | yes |
| `POST /admin/previews/{pdt}/gop/remove` | `…@removeGop` | admin | deletes the group and its `properties` (draft only) | yes |
| `GET /admin/previews/{pdt}/gop/suggestions`, `GET /admin/lookup/gops` | | admin | — | access only |

## e) Data templates (ISO 23387): `productdatatemplates`, `constructionobjects`, `referencedocuments`

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `GET/POST /admin/pdt/create` | `PdtCreateController@create/store` | admin | `productdatatemplates` (Preview v1.0), optionally `constructionobjects` (Preview) | yes |
| `GET /admin/previews`, `POST /admin/previews/create` | `PreviewWorkflowController@drafts/createDraft` | admin | `productdatatemplates` | access + page |
| `GET /admin/previews/{pdt}`, `POST /admin/previews/{pdt}/pdt`, `/context` | `…@editor/editPdt/editContext` | admin | `productdatatemplates`, `properties` (draft only) | yes |
| `GET /admin/pdt/{pdt}/edit`, `POST …/edit/context` | `ActivePdtEditController` | admin | `properties` usage text, **in place on Active** | yes |
| `POST /admin/reference-documents/create-ajax` | `ReferencedocumentsController@createAjax` | admin | `referencedocuments` (new GUID) | yes |
| `GET /referencedocuments/list`, `POST /referencedocuments/create` | `…@getReferenceDocuments/referenceDocumentCreate` | admin | `referencedocuments` (GUID from the form) | yes (fixed: duplicate GUID gave a 500) |
| `GET/POST/DELETE /admin/relations…` | `RelationshipController` → `RelationshipService` | admin | `entity_relationships` (IsSubtypeOf, HasPart…; soft delete) | yes |
| `GET /admin/picker/properties`, `POST /admin/picker/match`, `/gap` | `PropertyPickerController` | admin | — (reads an uploaded xlsx/xls/csv ≤ 4 MB; download) | yes |

## f) Review, comments, status, deletion

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `GET /pdtssurvey/{pdtID}`, `POST /pdtssurvey/store`, `/saveAnswers` | `GroupofpropertiesController` | user | `comments` (+ mail to admins), `answers` | yes (SecurityTest) |
| `POST /comments/{propID}`, `DELETE /deletefeedback` | `…@getCommentProperty/destroyfeedback` | user (own) / admin (any) | deletes `comments` (hard) | yes (SecurityTest) |
| `GET /admin/status-check`, `POST …/apply` | `StatusCheckController` → `StatusHealthService` | admin | `status` on templates, groups and dictionary rows (selected fixes only) + JSON backup | yes (StatusHealthCheckTest) |
| `GET /admin/previews/{pdt}/delete-plan`, `POST …/delete` | `PreviewWorkflowController@deletePlan/deleteApply` | admin | **hard** delete of a draft template and the rows only it owns; never Active | yes |

Statuses: `Preview` (draft), `Active`, `InActive` (superseded/deprecated, with `depreciationDate/Explanation`). The admin has no soft delete; deletion exists only for drafts and comments.

## g) Translations, duplicate detection, merge

- Translations are columns (`*Pt`/`*En`), edited with the other attributes. UI language is the `/language/{locale}` switch (`SetLanguage`), which never affects a URI.

| Route | Controller | Role | Writes | Tests |
|---|---|---|---|---|
| `GET /admin/dedupe-dictionary…` | `DictionaryDedupeController@index/group/deduped` | admin | — | yes |
| `POST /admin/dedupe-dictionary/apply` | `…@apply` → `DictionaryDedupeService::applyDecision` | admin | **merge**: re-points `properties` (propertyId **and GUID**) to the survivor, **hard-deletes** the duplicate dictionary rows (other GUIDs; same-GUID rows with FK checks off); **keep separate**: renames `nameEn`; JSON backup | yes (as found) |
| `POST /admin/dedupe-dictionary/dict` | `…@updateDict` | admin | `definitionEn/Pt` **in place, no version** | yes (as found) |
| `POST /admin/dedupe-dictionary/property`, `/review-state` | `…@updateProperty/reviewState` | admin | `properties` descriptions; review-state file | 422 path |
| `GET/POST /admin/dedupe-reference-documents…` | `ReferenceDocumentDedupeController` | admin | re-points `referenceDocumentGUID`, deletes duplicates; backup | 422 path |

## h) Exports

| Route | Controller | Role | Output | Tests |
|---|---|---|---|---|
| `GET /api/{id}` | `ProductdatatemplatesController@productDataTemplate` → `Iso23387Exporter::exportWithRawData` | public | ISO 23387 structure + `_raw` rows | yes |
| `GET /api/{id}/json`, `POST /pdt-export/json/{id}` | `…Json` / `downloadPdtJson` → `exportToJson` | public | ISO 23387 JSON: `Library.DataTemplates[].HasPropertyRef`, `Library.Properties[]` (`dt:GUID` dashed, `Name` en/pt, `DataType.name`, `_uri`), `Library.GroupOfProperties[]` | yes |
| `GET /api/{id}/xml`, `POST /pdt-export/xml/{id}` | `exportToXml` | public | ISO 23387 XML | yes (parses) |
| `GET /pdtsdownload/{id}` | `GroupofpropertiesController@getGroupOfProperties` | public | page + XLSX | page timed |
| `GET /exportdomainbsdd`, `POST /exportdomainbsdd-psets` | closure / `…@exportDataToJsonPSETS` | admin | bSDD JSON (ModelVersion 2.0, UseOwnUri, Classes + Properties); refuses (409) duplicate codes | yes |

## i) URIs: `UriController@resolve`, `UriService`

`https://pdts.pt/uri/{0.1}/{entity}/{code}[/v{n}]`. Entities are `prop dt class gop classprop doc unit pq dim enum`.
- `prop`: the code is `namePt` (sanitized). A `nameEn` hit answers **301** to the Portuguese URI (the query string is kept), so the "@id" is always the Portuguese one.
- HTML by default. JSON (`@id`, `entity`, `code`, `label`, `versionNumber`, `revisionNumber`, `pinnedUri`, `record`) with `Accept: application/json`, `?format=json` or a `.json` suffix.
- `latest` → 302 to the canonical form. An unknown entity, version, code or `/v{n}` → 404. A code claimed by two records → 409 page.
- Tests: yes (ApiContractTest, AdminProcessesTest).

## Public API (Dictagon): all `GET`, `api` middleware group (`throttle:api`, 60/min)

`/api/productDataTemplates`, `/api/{id}`, `/api/{id}/json`, `/api/{id}/xml`, `/api/dataDictionary`, `/api/dataDictionary/{Id}`, `/api/groupsOfProperties`, `/api/groupsOfProperties/{Id}`, `/api/referenceDocuments`, `/api/referenceDocuments/{GUID}`, `/api/constructionObjects`, `/api/units`, `/api/quantityKinds`, `/api/dimensions`. The collection endpoints serialize whole table rows plus the appended `uri`. Key sets are pinned in `ApiContractTest`.
