# OpenPulse

OpenPulse is a framework-free PHP and MySQL API analytics platform for collecting and storing application events. It is built with modern PHP practices, Composer autoloading, clear separation of concerns, and a repository-based data access layer.

## Current Status

The application currently supports:

- A lightweight request and response flow with a custom router.
- `GET /api/health` for a basic health check.
- `POST /api/events` to accept and persist event data.
- Event validation for the required `event` field.
- MySQL persistence through PDO and prepared statements.
- JSON storage for event properties.
- Optional `user_id` and `properties` fields, stored as SQL `NULL` when omitted.
- A migration runner for applying database migrations.
- A CLI command for running migrations.
- Composer PSR-4 autoloading.
- PHPUnit and PHPCS tooling configured for the codebase.

The event endpoint has been verified with valid requests and invalid blank event names. Valid events are saved to the `events` table, while invalid event names return HTTP 400 without inserting a row.

## Technology Stack

- PHP 8.3+
- MySQL
- PDO
- Composer
- PHPUnit
- PHP_CodeSniffer (PSR-12)
- Laravel Valet for local development

## Project Structure

```text
openpulse/
├── bin/
│   └── openpulse
├── config/
│   └── database.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   └── index.php
├── src/
│   ├── Contracts/
│   ├── Core/
│   ├── Controllers/
│   ├── DTOs/
│   ├── Exceptions/
│   ├── Jobs/
│   ├── Middleware/
│   ├── Models/
│   ├── Repositories/
│   └── Services/
├── templates/
├── tests/
│   ├── Integration/
│   └── Unit/
├── .env.example
├── composer.json
├── composer.lock
├── phpunit.xml
└── phpcs.xml
```

Some directories are in place for the project's planned architecture and may not yet contain implemented features.

## API

### Health Check

```http
GET /api/health
```

Example response:

```json
{
  "status": "ok"
}
```

### Collect an Event

```http
POST /api/events
Content-Type: application/json
```

Example request:

```json
{
  "event": "page_view",
  "user_id": "user_123",
  "properties": {
    "page": "/pricing",
    "source": "google"
  }
}
```

Example success response:

```json
{
  "message": "Event received",
  "event": "page_view"
}
```

The `event` field is required and must be a non-empty string. `user_id` and `properties` are optional. At the current stage, validation of these optional fields is planned but not yet implemented.

A request with a blank `event` value returns HTTP 400 and is not persisted.

## Database

The migration system tracks executed migration files in a `migrations` table. The current event migration creates an `events` table with these fields:

| Column | Purpose |
|---|---|
| `id` | Auto-incrementing event identifier |
| `event` | Event name |
| `user_id` | Optional user identifier |
| `properties` | Optional JSON event properties |
| `created_at` | Event creation timestamp |

Database settings are loaded from environment configuration through `config/database.php`. Keep local credentials in `.env`; do not commit `.env` to version control.

## Local Setup

1. Install PHP 8.3 or later, Composer, and MySQL.
2. Install project dependencies:

   ```bash
   composer install
   ```

3. Create a local `.env` file based on `.env.example` and configure the database connection.
4. Create the configured MySQL database.
5. Run database migrations from the project root:

   ```bash
   openpulse migrate
   ```

   The `openpulse` shell alias is configured locally to run `php bin/openpulse` from the project root. If the alias is not configured, run:

   ```bash
   php bin/openpulse migrate
   ```

6. Configure the local web server or Laravel Valet to serve the `public/` directory.

## Development Checks

Run the configured code-style check:

```bash
phpcs
```

Run the test suite:

```bash
vendor/bin/phpunit
```

## Current Development Focus

The next planned improvement is to extend `EventValidator` to validate the optional `user_id` and `properties` fields. Further work will build on the existing routing, validation, migration, and persistence foundations.
