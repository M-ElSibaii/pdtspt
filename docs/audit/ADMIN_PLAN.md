# PDTs.pt admin audit — plan

## Context
Map every admin process of PDTs.pt, prove each with Laravel/Pest feature tests, lock down the contract Dictagon and bSDD rely on, fix only defects the tests prove (one commit + test each), and report. Existing GUIDs, URIs, names, version numbers and API field names must not change.

## Base branch (approved)
- `master` (5f9c621) is 2 commits behind `fix/units-quantities-and-site-audit` (82067ab, e41c4c1). Only those 2 commits contain:
  - `tests/Support/PdtFixtures.php`;
  - the `tests/TestCase.php` guard that refuses any database not named `*_test`;
  - the fix for a critical hole. On master, `routes/web.php` declares `['middleware' => 'auth', 'verified', 'admin']`, which leaves **every /admin route open to any logged-in user**.
- **Plan: branch `audit-admin` from `fix/units-quantities-and-site-audit` (e41c4c1).** The report will say the audit branch depends on that one being merged first, and it will record that master in production, if that is what runs live, has the open-admin hole. That goes under "Decisions for Mohamad".
- If you want master instead, I branch from master, rebuild the fixtures and the DB guard there, and fix the middleware again as the first defect (it will conflict with the fix branch).

## Safety (verified so far)
- `.env`: DB at 127.0.0.1 / `pdtspt` (local). `MAIL_MAILER=smtp` in .env, but phpunit.xml overrides it with `array`, `DB_DATABASE=pdtspt_test`, sync queue and array cache. TestCase refuses non-`_test` databases.
- I'll check that `pdtspt_test` exists using a read-only `SHOW DATABASES`. If it doesn't exist, I stop and report; I won't create it without your OK.
- Tests will call `Http::fake()` and `Mail::fake()` and block stray HTTP requests, so nothing reaches bSDD or any other outside service. No `migrate`/`db:seed` against `pdtspt`, no package changes, no deploy. I push only `audit-admin`.

## Steps
0. **Baseline**: `php -v`, `php artisan --version`, `composer show` (key packages), and `php artisan test` with the result recorded. Write `docs/audit/ADMIN_PLAN.md` (this plan) and commit it.
1. **Map** (`docs/audit/ADMIN_MAP.md`): `php artisan route:list` (read-only), plus a pass through the controllers (AdminHub, PreviewWorkflow, PdtCreate, PdtVersioning, ActivePdtEdit, DictionaryDedupe, ReferenceDocumentDedupe, Relationship, PropertyDependency, PropertyPicker, StatusCheck, User, Groupofproperties comments/survey, Referencedocuments, Uri, Productdatatemplates exports), AdminMiddleware, models and services (VersioningService, DraftVersionService, Iso23387Exporter, UriService, BsddEnums). One table per process (a–i plus any others found): route, controller method, role, tables written, events/jobs, whether tests exist.
2. **Process tests** in `tests/Feature/Audit/*Test.php`, built on `PdtFixtures`. Each process is tested as guest, user and admin: the allowed role succeeds, the others are refused, and the right rows change. Versioning: same GUID, new Id, v/r bump per `VersioningService::decidePropertyBump`, earlier row still readable. I check that rule against ISO 23386 if a copy is in the repo (none found so far), otherwise I record the rule as found.
3. **Contract tests** (`tests/Feature/Audit/ApiContractTest.php`):
   - every `/api` route Dictagon calls returns 200 with a stable JSON shape and the listed property fields;
   - ISO 23387 JSON: it parses, every HasPropertyRef entry is in Library.Properties, and each property has a GUID, `_uri`, PT/EN names and a data type;
   - bSDD JSON: field names, data-type mapping and units checked against the current bSDD import docs, cited in the report;
   - URIs: `/uri/0.1/prop/<NamePt>` resolves, the EN form returns the same `@id`, and an unknown name returns 404;
   - master template 67: a fixture copies its structure (UrlDoPassaporteDigitalDoProduto in "Dados de conformidade"). To copy it, I read the structure from the local `pdtspt` database with SELECT only.
   - A read-only list of rows changed since the last bSDD export date, if the platform stores one (none found so far, so this will probably go under "could not verify").
4. **Security/robustness**: auth/role checks on every admin route, CSRF, `$fillable`/`$guarded`, upload type and size checks, validation returning 422 instead of 500, the API read-only and throttled, a secrets scan of tracked files. Timing and query count (via `DB::listen`) for one large fixture PDT on the export and PDT pages.
5. **Fixes**: one commit per proven defect, each with its test. I never edit a test or fixture to make it pass. If a contract field, GUID or URI differs from what the code produced before my change, I stop, commit, push and report.
5a. **Self-demotion guard** (your request): in `UserController::updateUsers` ([app/Http/Controllers/UserController.php](app/Http/Controllers/UserController.php)), an admin cannot change their own `isAdmin` flag. The change is skipped and a validation error is returned, while the other users' rows still update. In the same commit: missing `isAdmin`/`subscribe` arrays are treated as empty (no 500 on foreach over null), and unknown ids are skipped (no 500 on `null->save()`). Test `tests/Feature/Audit/UserAdminFlagTest.php`: own flag unchanged with an error returned, another user's flag changes, a non-admin is refused, an empty request returns no 500.
5b. **CI** (your request): `.github/workflows/ci.yml`, one commit. Runs on push and pull_request:
   - PHP version taken from composer.lock (`platform`/`require.php`; I'll read the lock and pin the matching 8.x in `shivammathur/setup-php`);
   - a `mysql:8.0` service with `MYSQL_DATABASE=pdtspt_test`, a throwaway root password set inline (not a secret, CI only) and a health check;
   - `cp .env.example .env`, `php artisan key:generate`, `composer install --no-interaction --prefer-dist`, `php artisan migrate --force` against `pdtspt_test` (env set in the job), `php artisan test`;
   - Node from `package-lock.json`: `npm ci`, then `npm run build`;
   - no repository secrets used. I can't run it locally, so the report will say it is unverified until the first push triggers it, and I'll record that run's result if it finishes before I stop.
6. **Report** in `docs/audit/ADMIN_AUDIT_REPORT.md` (all the sections you asked for), then commit, `git push -u origin audit-admin`, and stop.

## Verification
`php artisan test` (whole suite, against `pdtspt_test`) passes, apart from failures that are documented and not fixed. The report lists each test file per process and the final pushed commit hash.
