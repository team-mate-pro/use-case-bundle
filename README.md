# Super Simple Architecture (SSA) Core Bundle

A Symfony PHP bundle providing architectural building blocks for clean REST API development. SSA enforces a use-case driven architecture with validated requests, standardized result objects, and consistent HTTP responses.

**Documentation**: [https://serek.dev/super-simple-architecture-by-serek-ssa](https://serek.dev/super-simple-architecture-by-serek-ssa)

## Overview

The Super Simple Architecture approach focuses on:

- **Use-case driven design**: Business logic encapsulated in dedicated use case classes
- **Validated requests**: Automatic request validation with Symfony constraints
- **Standardized results**: Type-safe Result objects with consistent error handling
- **REST API patterns**: Controllers that transform use case results into proper HTTP responses
- **Repository abstraction**: Collection and pagination helpers for data access

## Installation

```bash
composer require team-mate-pro/use-case-bundle
```

## Quick Start

### 1. Create a Validated Request

```php
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequest extends AbstractValidatedRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $name;

    protected function securityCheck(): bool
    {
        return $this->isGranted('ROLE_ADMIN');
    }
}
```

Request objects automatically:
- Populate from JSON body, query params, and route attributes
- Validate using Symfony validator constraints
- Inject authenticated user ID if `userId` property exists
- Throw security exceptions if `securityCheck()` returns false

### 2. Create a Use Case

```php
use TeamMatePro\UseCaseBundle\UseCase\Result;
use TeamMatePro\UseCaseBundle\UseCase\ResultType;

class CreateUserUseCase
{
    public function execute(CreateUserRequest $request): Result
    {
        // Business logic here
        $user = new User($request->email, $request->name);

        // Check for conflicts
        if ($this->userExists($user->email)) {
            return Result::create(ResultType::FAILURE, 'User already exists')
                ->withErrorCode('USER_EXISTS');
        }

        $this->repository->save($user);

        return Result::create(ResultType::SUCCESS, 'User created')
            ->with($user)
            ->withMeta('id', $user->getId());
    }
}
```

Result objects support:
- Type-safe success/failure states (SUCCESS, FAILURE, NOT_FOUND, etc.)
- Single items or collections with `->with($data)`
- Metadata with `->withMeta($key, $value)`
- Error codes with `->withErrorCode($code)`
- Iteration over result items

### 3. Create a REST Controller

```php
use TeamMatePro\UseCaseBundle\Http\AbstractRestApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CreateUserController extends AbstractRestApiController
{
    public function __construct(
        private readonly CreateUserUseCase $useCase
    ) {}

    #[Route('/api/users', methods: ['POST'])]
    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        $result = $this->useCase->execute($request);

        return $this->response($result, ['user:read']);
    }
}
```

Controllers automatically:
- Map ResultType to appropriate HTTP status codes
- Serialize response data with specified serialization groups
- Support caching headers with `responseWithCache()`

## Core Components

### Result Object

The `Result` object is the heart of SSA, providing a standardized container for use case outputs:

```php
// Success with data
Result::create(ResultType::SUCCESS, 'Operation successful')
    ->with($user);

// Failure with error code
Result::create(ResultType::FAILURE, 'Validation failed')
    ->withErrorCode('INVALID_DATA');

// Collection with metadata
Result::create(ResultType::SUCCESS, 'Users retrieved')
    ->with($users)
    ->withMeta('total', 100)
    ->withMeta('page', 1);

// Not found
Result::create(ResultType::NOT_FOUND, 'User not found');
```

**ResultType enum** maps to HTTP status codes:
- `SUCCESS` → 200
- `FAILURE` → 400
- `NOT_FOUND` → 404
- `CREATED` → 201
- `NO_CONTENT` → 204

### Validated Requests

Two populate strategies available:

```php
// Default: Direct property assignment
class MyRequest extends AbstractValidatedRequest
{
    protected function populateStrategy(): string
    {
        return self::PROPERTY_SET_STRATEGY; // default
    }
}

// Serializer: For complex denormalization
class ComplexRequest extends AbstractValidatedRequest
{
    protected function populateStrategy(): string
    {
        return self::SERIALIZER_STRATEGY;
    }
}
```

**Helper methods**:
- `getValue(string $property)`: Returns property value or throws exception if null/unset
- `securityCheck()`: Override for custom authorization logic
- `autoValidateRequest()`: Return false to disable auto-validation

### Repository Collections

```php
use TeamMatePro\UseCaseBundle\Repository\Collection;
use TeamMatePro\UseCaseBundle\Repository\Pagination;

// Create pagination
$pagination = new Pagination(page: 1, limit: 20);

// Return collection
$items = $this->repository->findAll($pagination);
$collection = new Collection(
    items: $items,
    total: $this->repository->count(),
    limit: $pagination->getLimit()
);

// Use in Result
return Result::create(ResultType::SUCCESS, 'Users retrieved')
    ->with($collection);
```

### Response Factories

Generate blob responses from Result objects:

```php
use TeamMatePro\UseCaseBundle\Http\ResultResponseFactory;

// CSV response
$response = ResultResponseFactory::createCsvResponse(
    result: $result,
    filename: 'users.csv',
    base64Encode: false
);

// Binary blob response
$response = ResultResponseFactory::createBlobResponse(
    result: $result,
    contentType: 'application/pdf',
    filename: 'report.pdf'
);
```

### Content Type Checker

Check if a request expects a specific content type based on the `Accept` header:

```php
use TeamMatePro\UseCaseBundle\Http\ContentType\ContentTypeChecker;

class ExportController extends AbstractRestApiController
{
    public function __construct(
        private readonly ContentTypeChecker $contentTypeChecker,
        private readonly ExportUseCase $useCase
    ) {}

    #[Route('/api/users/export', methods: ['GET'])]
    public function __invoke(ExportRequest $request): Response
    {
        $result = $this->useCase->execute($request);

        // Check Accept header to determine response format
        if ($this->contentTypeChecker->isCsvRequest($request)) {
            return ResultResponseFactory::createCsvResponse($result, 'users.csv');
        }

        if ($this->contentTypeChecker->isPdfRequest($request)) {
            return ResultResponseFactory::createBlobResponse(
                result: $result,
                contentType: 'application/pdf',
                filename: 'users.pdf'
            );
        }

        // Default JSON response
        return $this->response($result, ['user:read']);
    }
}
```

The `ContentTypeChecker` supports:
- **CSV detection**: `text/csv`, `application/csv`, `text/comma-separated-values`
- **PDF detection**: `application/pdf`
- Case-insensitive matching
- Multiple MIME types in Accept header (e.g., `text/csv, application/json`)

Any class implementing `HeadersAwareInterface` can be checked (including `AbstractValidatedRequest`).

## Development

This bundle uses Docker for development. All commands run inside containers to ensure consistency.

### Setup

```bash
# Clone the repository
git clone <repository-url>
cd ssa-core-bundle

# Install dependencies (inside Docker)
docker compose run --rm lib composer install
```

### Running Tests

```bash
# Run all unit tests
docker compose run --rm lib tests:unit

# Run PHPUnit directly
docker compose run --rm lib phpunit

# Run with coverage
docker compose run --rm lib phpunit --coverage-text
```

Test structure:
- Tests located in `tests/Unit/` mirroring `src/` structure
- 165 tests, 297 assertions
- Mother objects in `tests/_Data/MotherObject/` for test data builders

### Static Analysis

```bash
# Run PHPStan (max level)
make phpstan
# or
docker compose run --rm lib composer phpstan

# Generate baseline for existing issues
make phpstan_baseline
```

PHPStan configuration:
- Level: max (highest strictness)
- Analyzes both `src/` and `tests/`
- Extensions: PHPUnit, Symfony
- Baseline: 113 known issues (see `phpstan-baseline.neon`)

### Interactive Development

```bash
# Enter bash shell in container
docker compose run --rm lib bash

# Inside container, run commands:
composer tests:unit
composer phpstan
vendor/bin/phpunit
```

### Deployment

```bash
# Tag and publish new version (reads version from composer.json)
make tag

# Publish dev-master
make publish
```

The `make tag` command:
1. Extracts version from `composer.json`
2. Creates and pushes git tag
3. Triggers GitLab CI/CD to publish to package registry

## Architecture Patterns

### Use Case Flow

```
HTTP Request
    ↓
Controller receives Request object
    ↓
Request auto-validates (constraints + security check)
    ↓
Controller passes Request to Use Case
    ↓
Use Case executes business logic
    ↓
Use Case returns Result object
    ↓
Controller converts Result to JsonResponse
    ↓
HTTP Response
```

### Exception Handling

Event listeners provide automatic exception handling:

- **ValidationExceptionListener**: Catches validation exceptions, returns structured error JSON
- **AuthorizationExceptionListener**: Handles access denied exceptions with 403 responses

### Value Objects

Store reusable value objects in `src/ValueObject/`:

```php
// Example: TimeRange with quarters
$timeRange = new TimeRange(year: 2025, quarter: 1);
```

## Configuration

No special configuration required. The bundle auto-configures when installed in a Symfony application.

## Testing Your Integration

```php
// In your application tests
use TeamMatePro\UseCaseBundle\UseCase\Result;
use TeamMatePro\UseCaseBundle\UseCase\ResultType;

class MyUseCaseTest extends TestCase
{
    public function testExecute(): void
    {
        $request = new MyRequest();
        $result = $this->useCase->execute($request);

        $this->assertEquals(ResultType::SUCCESS, $result->getType());
        $this->assertNotNull($result->getItem());
    }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure PHPStan passes at max level
5. Submit a pull request

## Requirements

- PHP >= 8.2
- Symfony >= 7.0
- Docker (for development)

## License

Proprietary - Team Mate Pro

## Author

Sebastian Twaróg (sebastian.twarog1989@gmail.com)

## Links

- Documentation: https://serek.dev/super-simple-architecture-by-serek-ssa
- Package: team-mate-pro/use-case-bundle
