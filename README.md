# PDTs.pt

Welcome to the PDTs.pt open-source project repository! PDTs.pt is a Product Data Templates (PDT) query platform built using Laravel, designed to facilitate the creation, viewing, and extraction of PDTs. This platform serves as a valuable tool for industries to manage and utilize PDTs effectively, in compliance with relevant ISO standards.

## Key Features

- **Product Data Templates (PDT) Management**: Query, view, and give feedback on PDTs.
- **API Access**: Extract data using our comprehensive API gateways.
- **PDT View and Download**: View PDTs and download in various formats including XLSX, text, and JSON.
- **User Feedback**: Provide feedback and answer surveys to help us improve the platform.
- **Standards Compliance**: Our data model complies with EN ISO 23387:2020 and our data dictionary adheres to EN ISO 23386:2020 standards.

## Laravel Structure

PDTs.pt is built using Laravel, which follows the Model-View-Controller (MVC) architectural pattern. Here's a brief overview of the structure and where to find key components:

- **Controllers**: Located in the `app/Http/Controllers` directory, controllers handle the logic of the application. For example, `PropertiesController.php` manages property-related operations.
- **Models**: Found in the `app/Models` directory, models represent the data structure of the application. For instance, `Properties.php` defines the properties and relationships of the PDTs.
- **Views**: Stored in the `resources/views` directory, views handle the presentation layer. Blade templates like `properties.blade.php` are used to render HTML content.
- **Routes**: Defined in the `routes/web.php` file, routes map URLs to controllers. API routes are located in the `routes/api.php` file.

### Connecting to the Database

To connect to the database, configure your database settings in the `.env` file:

```plaintext
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pdt_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Ensure you have the necessary database and user permissions set up.

## API Documentation

Our platform includes an API page that provides detailed documentation on how to use the API gateways. To access the API documentation, visit `/api/documentation` on the platform.

### Example API Usage

#### Product Data Template API

**Get Product Data Template**  
Returns the product data template with the specified ID, with property groups, properties, data dictionary property attributes and relevant reference documents.

```
GET /api/{pdtID}
```

## Getting Started

To get started with PDTs.pt, clone this repository and follow the installation steps:

1. **Clone the repository**:
    ```bash
    git clone https://github.com/M-ElSibaii/pdtspt.git
    ```

2. **Install dependencies**:
    ```bash
    cd pdtspt
    composer install
    npm install
    ```

3. **Configure the environment**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Set up the database**:
    ```bash
    php artisan migrate --seed
    ```

5. **Run the development server**:
    ```bash
    php artisan serve
    ```

Visit `http://localhost:8000` to access the application.

## Contributing

We welcome contributions from the community! If you have suggestions, bug reports, or feature requests, please open an issue or submit a pull request. Together, we can make PDTs.pt a valuable resource for managing Product Data Templates.

## License

PDTs.pt is open-source software licensed under the [MIT license](LICENSE).

## Contact

For any inquiries or support, please contact us at [pdts.portugal@gmail.com](mailto:pdts.portugal@gmail.com).

Thank you for using PDTs.pt! We look forward to your feedback and contributions.