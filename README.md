# PDTs.pt

Welcome to the **PDTs.pt** open-source repository. PDTs.pt is a Product Data Templates (PDT) platform built with Laravel that lets industry create, review, query, view, and export PDTs in compliance with the relevant ISO standards. It is the reference implementation for Portugal's product-data-template layer.

## Key Features

- **PDT authoring & lifecycle** – create PDTs from construction objects, edit as free "Preview" drafts, publish, and cut new versions of active PDTs (staged plan → diff → commit).
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

- **Controllers** (`app/Http/Controllers`) – request handling, e.g. `ProductdatatemplatesController` (view/query/export), `PreviewWorkflowController` (draft editor), `PdtCreateController`, `PdtVersioningController`, `UnitsReferenceController` (reference layer), `RelationshipController`.
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

Each reference entity also resolves as a **dereferenceable page** on the platform, as HTML or (via `Accept: application/json`, `?format=json`, or a `.json` suffix) JSON:

```
GET /unit/{code}            e.g. /unit/mm
GET /quantitykind/{name}    e.g. /quantitykind/length
GET /dimension/{canonical}  e.g. /dimension/L
```

# Rebuild the reference layer (units from bsDD, dimensions, quantity kinds)

php artisan units:seed-reference
php artisan dimensions:derive --apply
php artisan units:map-qudt --apply
php artisan properties:reconcile-units --apply

# Re-run the dictionary dedup

php artisan pdts:dedupe-dictionary

## Contributing

Contributions are welcome. If you have suggestions, bug reports, or feature requests, please open an issue or submit a pull request.

## License

PDTs.pt is open-source software licensed under the [MIT license](LICENSE).

## Contact

For any inquiries or support, contact us at [pdts.portugal@gmail.com](mailto:pdts.portugal@gmail.com).

Thank you for using PDTs.pt — we look forward to your feedback and contributions.
