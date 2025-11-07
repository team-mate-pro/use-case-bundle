# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Super Simple Architecture (SSA) Core Bundle - a Symfony PHP bundle that provides architectural building blocks for clean
REST API development. The bundle enforces a use-case driven architecture with validated requests, standardized result
objects, and consistent HTTP responses.

Documentation available at: https://serek.dev/super-simple-architecture-by-serek-ssa

## Commands

All development commands should be run inside Docker containers via `docker compose`.

### Testing

```bash
# Run all unit tests
docker compose run --rm lib tests:unit

# Run PHPUnit directly
docker compose run --rm lib phpunit

# Run PHPStan static analysis
make phpstan
# or
docker compose run --rm bash -c "composer phpstan"

# Generate PHPStan baseline (to ignore current errors)
make phpstan_baseline
```

### Development Environment

```bash
# Enter bash shell in Docker container for manual commands
docker compose run --rm bash

# Inside the container, you can run composer commands:
# composer tests:unit
# composer phpunit
```

### Deployment

```bash
# Tag and publish new version (uses version from composer.json)
# These run on host machine as they require git/curl
make tag

# Publish dev-master
make publish
```

## Core Architecture

### Use Case Pattern

The bundle centers around the `Result` object pattern for use case responses:

- **Result** (`src/UseCase/Result.php`): Generic container for use case outputs with type safety, metadata, and error
  codes
- **ResultType** (`src/UseCase/ResultType.php`): Enum defining result states (SUCCESS, FAILURE, NOT_FOUND, etc.) that
  map to HTTP status codes
- Result objects are iterable and support both single items and collections

### Request Validation Flow

Requests extend `AbstractValidatedRequest` which provides:

1. **Auto-population**: Request data (JSON body, query params, route attributes) automatically populates class
   properties
2. **Security check**: Override `securityCheck()` for authorization logic before validation
3. **Auto-validation**: Symfony validator constraints run automatically on construction (disable via
   `autoValidateRequest()`)
4. **User injection**: If request has `userId` property and user is authenticated, it's auto-populated
5. **getValue()**: Helper method with comprehensive validation that throws `HttpMalformedRequestException` for null,
   undefined, or unset properties

Two populate strategies available:

- `PROPERTY_SET_STRATEGY` (default): Direct property assignment
- `SERIALIZER_STRATEGY`: Uses Symfony serializer for complex denormalization

Access dependencies via `RequestDependencies` object: validator, requestStack, security, serializer

### REST API Controllers

Extend `AbstractRestApiController` for standardized JSON responses:

- `response(Result $result, $serializationGroups, $headers)`: Converts Result to JsonResponse with proper HTTP status
- `responseWithCache(Result $result, $cacheInSeconds, ...)`: Adds Cache-Control headers (supports separate s-maxage and
  max-age)
- `ResultRestRenderer`: Maps ResultType enum cases to HTTP status codes

### Repository Pattern

- **Collection** (`src/Repository/Collection.php`): Generic collection object with items, total count, and limit
- **Pagination** (`src/Repository/Pagination.php`): Pagination helper for repository queries

### Response Factories

`ResultResponseFactory` provides blob response generation:

- `createCsvResponse()`: Converts Result data to CSV (optionally base64 encoded)
- `createBlobResponse()`: Converts Stringable Result items to binary responses

### Value Objects

Store reusable value objects in `src/ValueObject/`. Example: `TimeRange` with year and quarter support.

### Exception Handling

Event listeners in `src/Http/EventListener/`:

- `ValidationExceptionListener`: Catches validation exceptions and returns structured error responses
- `AuthorizationExceptionListener`: Handles access denied exceptions

## Key Patterns

### Creating a new validated request:

```php
class MyRequest extends AbstractValidatedRequest
{
    #[Assert\NotBlank]
    public string $name;

    protected function securityCheck(): bool
    {
        return $this->isGranted('ROLE_USER');
    }
}
```

### Creating a use case result:

```php
Result::create(ResultType::SUCCESS, 'User created')
    ->with($user)
    ->withMeta('count', 1)
    ->withErrorCode('USER_EXISTS');
```

### REST controller response:

```php
public function __invoke(MyRequest $request): JsonResponse
{
    $result = $this->useCase->execute($request);
    return $this->response($result, ['user:read']);
}
```

## Testing Structure

Tests located in `tests/Unit/` mirroring `src/` structure. No PHPUnit XML config - configuration in composer.json
scripts. Test environment set via `APP_ENV=test`.

## Package Publishing

This is a private GitLab package. Version is managed in `composer.json`. The `make tag` command:

1. Extracts version from composer.json
2. Creates and pushes git tag
3. Triggers GitLab CI/CD to publish to package registry

CI/CD (`.gitlab-ci.yml`) runs unit tests on all commits and publishes tagged versions.

## Code Quality Tools

### PHPStan
- **Level**: max (highest strictness - configured in `phpstan.neon`)
- **Coverage**: Analyzes both `src/` and `tests/` directories
- **Extensions**: PHPUnit and Symfony extensions enabled
- **Commands**:
  - `make phpstan` - Run static analysis (alias to `composer phpstan`)
  - `make phpstan_baseline` - Regenerate baseline (alias to `composer phpstan:baseline`)
- **Baseline**: `phpstan-baseline.neon` contains 113 known issues to fix incrementally

Current configuration ignores:
- Test-specific patterns (constructor instantiation, generic types)
- PHPDoc type certainty checks (treatPhpDocTypesAsCertain: false)

### PHPUnit
- **Version**: 10.x (latest stable)
- **Features**: Attributes-based configuration, data providers, testdox output with colors
- **Coverage**: 165 tests, 297 assertions across all core components
- **Mother Objects**: Test data builders in `tests/_Data/MotherObject/` for Result, Collection, Pagination