# OpenPulse

**A framework-free PHP and MySQL analytics platform built to demonstrate modern PHP engineering practices.**

OpenPulse is a hands-on backend engineering project focused on clean architecture, object-oriented PHP, database persistence, external API integration, caching, automated testing, and operational reliability.

The project is being developed incrementally, with an emphasis on understanding the engineering decisions behind each component rather than relying on a full-stack framework.

## Tech Stack

- **Language:** PHP 8.5
- **Dependency management:** Composer
- **Database:** MySQL
- **Caching:** Redis with Predis
- **Testing:** PHPUnit
- **Static analysis:** PHPStan
- **Coding standards:** PHP_CodeSniffer (PSR-12)
- **Architecture:** Framework-free, object-oriented PHP with dependency injection

## Features

### Application architecture

- Front controller and application bootstrap
- Lightweight HTTP request, response, and routing components
- Dependency injection container
- Interface-based service and repository bindings
- PSR-4 autoloading through Composer

### Database and persistence

- MySQL integration through PDO
- Prepared statements for database operations
- Repository pattern for separating persistence from business logic
- Versioned database migrations
- Migration tracking to avoid reapplying completed migrations
- Transaction-based persistence for multi-step database operations

### External API integration

- World Bank API integration for country data
- HTTP error and response validation
- JSON decoding and payload validation
- Normalization of external data before persistence
- Batch persistence using MySQL upserts

### Redis caching

- Cache abstraction through `CacheInterface`
- Redis implementation using Predis
- JSON serialization for cached values
- Time-to-live support
- Cache invalidation after country imports

### Background job queue (in progress)

- MySQL-backed job and failed-job tables
- Queue contract for dispatching and reserving jobs
- Retry scheduling and failed-job persistence foundations
- Planned worker CLI, reservation recovery, and automated queue tests

## Project Structure

```text
openpulse/
├── bin/
│   └── openpulse
├── config/
│   ├── commands.php
│   ├── database.php
│   ├── environment.php
│   ├── redis.php
│   └── services.php
├── database/
│   └── migrations/
├── public/
├── routes/
│   └── web.php
├── scripts/
├── src/
│   ├── Commands/
│   ├── Contracts/
│   ├── Controllers/
│   ├── Core/
│   ├── Repositories/
│   └── Services/
├── tests/
├── .env.example
├── composer.json
└── phpstan.neon
```

## Requirements

- PHP 8.5 or a compatible version meeting the project's Composer requirements
- Composer
- MySQL
- Redis
- A local PHP web server or a development environment such as Laravel Valet

Required PHP extensions depend on the Composer dependencies and configuration, including PDO MySQL and cURL.

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/tirthadev/openpulse.git
cd openpulse
composer install
```

Create your local environment configuration:

```bash
cp .env.example .env
```

Update `.env` with your local MySQL and Redis connection details. Keep `.env` and all credentials out of version control.

Ensure MySQL and Redis are running, and create the database configured in your environment.

Run the database migrations:

```bash
openpulse migrate
```

Import country data from the World Bank API:

```bash
openpulse countries:import
```

The import requires network access to the external API.

## HTTP Endpoints

The current application routes include:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/countries` | Retrieve country data, using Redis caching |
| `GET` | `/api/events` | Retrieve events |
| `POST` | `/api/events` | Submit an event |

Run the application through your configured local