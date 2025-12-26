# PHP Domain Service Skeleton

A production-ready PHP 8.3 service skeleton demonstrating Domain-Driven Design (DDD) patterns with a clean architecture approach.

## Overview

This skeleton implements a complete order management system with:
- **Domain Layer**: Pure business logic with entities, value objects, and business rules
- **Application Layer**: Command handlers and DTOs for use cases
- **Infrastructure Layer**: Database repositories, Redis caching, and external service integrations
- **HTTP Layer**: Slim 4 routing, middleware, and controllers

## Architecture

### Layers

```
src/
├── Domain/          # Business logic (entities, value objects, domain rules)
├── Application/     # Use cases (commands, handlers, DTOs)
├── Infrastructure/  # External concerns (database, cache, third-party APIs)
└── Http/            # HTTP layer (controllers, middleware, routing)
```

### Domain Model

**Order Entity**
- States: `DRAFT`, `CONFIRMED`, `CANCELLED`
- Business Rules:
  1. Order can only be CONFIRMED if it has at least 1 item
  2. Items must have quantity >= 1 and price_cents >= 0
  3. Cancelling a CONFIRMED order sets state to CANCELLED and writes an audit record

**OrderItem Entity**
- Validates quantity and price constraints
- Calculates item totals

### Design Decisions

**Why Slim 4?**
- Minimal, PSR-7 compliant framework
- Focus on domain logic, not framework features
- Easy to test and maintain

**Why Layered Architecture?**
- Clear separation of concerns
- Domain logic independent of infrastructure
- Easy to test each layer in isolation
- Supports future growth and complexity

**Transactions**
- Repository pattern encapsulates database access
- Transaction boundaries managed at the application layer
- Ensures data consistency for multi-step operations

**Caching**
- Redis cache for GET /orders/{id} responses (60s TTL)
- Cache invalidation on write operations (add item, confirm, cancel)
- Improves performance for frequently accessed data

## Tech Stack

- **PHP 8.3** with strict types
- **Slim 4** for HTTP routing
- **MySQL 8** for persistence
- **Redis 7** for caching
- **Docker + docker-compose** for local development
- **Composer** with PSR-4 autoload
- **PHPUnit** for unit and integration tests
- **PHPStan** (level 8) for static analysis
- **PHP-CS-Fixer** for code style

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Make (optional, for convenience commands)

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd php-domain-service-skeleton
```

2. Copy environment file:
```bash
cp env.example .env
```

3. Start services:
```bash
make up
# or
docker-compose up -d
```

4. Install dependencies:
```bash
make install
# or
docker-compose exec app composer install
```

5. Run migrations:
```bash
make migrate
# or
docker-compose exec app php bin/migrate.php
```

6. Seed sample data (optional):
```bash
make seed
# or
docker-compose exec app php bin/seed.php
```

### Running the Application

The application will be available at `http://localhost:8080`

### API Endpoints

- `POST /orders` - Create a new draft order
- `POST /orders/{id}/items` - Add an item to an order
- `POST /orders/{id}/confirm` - Confirm an order
- `POST /orders/{id}/cancel` - Cancel a confirmed order
- `GET /orders/{id}` - Get order details (cached for 60s)

### Example Usage

```bash
# Create an order
curl -X POST http://localhost:8080/orders

# Add an item
curl -X POST http://localhost:8080/orders/1001/items \
  -H "Content-Type: application/json" \
  -d '{"product_name": "Product A", "quantity": 2, "price_cents": 5000}'

# Confirm the order
curl -X POST http://localhost:8080/orders/1001/confirm

# Get order details
curl http://localhost:8080/orders/1001
```

## Development

### Running Tests

```bash
make test
# or
docker-compose exec app composer test
```

### Code Quality

**Linting:**
```bash
make lint
# or
docker-compose exec app composer lint
```

**Fix code style:**
```bash
make fix
# or
docker-compose exec app composer fix
```

**Static analysis:**
```bash
make stan
# or
docker-compose exec app composer stan
```

### Database Migrations

Migrations are stored in `/migrations` and applied in order. The migration runner tracks applied migrations in the `schema_migrations` table.

**Apply migrations:**
```bash
make migrate
```

**Create a new migration:**
1. Create a new SQL file in `/migrations` with a sequential number (e.g., `005_add_index.sql`)
2. Run `make migrate` to apply it

## Testing

### Unit Tests

Unit tests focus on domain logic and business rules:
- Order state transitions
- Business rule validation
- Entity behavior

Located in `tests/Unit/`

### Integration Tests

Integration tests verify end-to-end functionality:
- HTTP endpoints
- Database persistence
- Cache behavior
- Transaction boundaries

Located in `tests/Integration/`

Tests use a separate test database and reset state between runs for determinism.

## Features

### Request ID Middleware

All requests include an `X-Request-Id` header for tracing. If not provided, one is generated automatically.

### Error Handling

JSON error responses with appropriate HTTP status codes:
- 400 for domain exceptions (business rule violations)
- 404 for not found errors
- 500 for unexpected errors

### Structured Logging

Logs are written to stdout in structured format with:
- Request ID
- HTTP method and URI
- Response status
- Timestamp

## Project Structure

```
.
├── bin/                 # CLI scripts (migrate, seed)
├── docker/              # Docker configuration
├── migrations/          # SQL migration files
├── public/              # Web root (index.php)
├── src/                 # Application source code
│   ├── Domain/          # Domain layer
│   ├── Application/     # Application layer
│   ├── Infrastructure/  # Infrastructure layer
│   └── Http/            # HTTP layer
├── tests/               # Test suite
│   ├── Unit/           # Unit tests
│   ├── Integration/    # Integration tests
│   └── Helper/         # Test helpers
├── .github/workflows/   # GitHub Actions CI
└── README.md           # This file
```

## CI/CD

GitHub Actions workflow runs on push/PR:
1. **Lint**: PHP-CS-Fixer validation
2. **Static Analysis**: PHPStan level 8
3. **Tests**: Unit and integration tests with MySQL and Redis

## License

GPL 3.0

