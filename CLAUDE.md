# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Super Simple Architecture (SSA) Core Bundle - a Symfony PHP bundle that provides architectural building blocks for clean
REST API development. The bundle enforces a use-case driven architecture with validated requests, standardized result
objects, and consistent HTTP responses.

Documentation available at: https://serek.dev/super-simple-architecture-by-serek-ssa

## Architecture Standards (UCB Rules)

This bundle follows TMP Standards (Use Case Bundle rules). Key conventions:

| Standard | Rule |
|----------|------|
| **UCB-001** | UseCase parameters MUST be interfaces, not concrete classes |
| **UCB-002** | UseCase MUST have `__invoke()` method (not `execute()`) |
| **UCB-003** | Authorization MUST be in Request's `securityCheck()`, NOT in UseCase |
| **UCB-004** | Controller MUST use `$this->response()`, NOT `$this->json()` |
| **UCB-005** | Controller actions MUST have "Action" suffix (e.g., `createUserAction`) |

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

- **Result** (from `team-mate-pro/contracts`): Generic container for use case outputs with type safety, metadata, and error codes
- **ResultType** (from `team-mate-pro/contracts`): Enum defining result states mapped to HTTP status codes

**ResultType → HTTP Status mapping:**
- 2xx: `SUCCESS` → 200, `SUCCESS_CREATED` → 201, `ACCEPTED` → 202, `SUCCESS_NO_CONTENT` → 204
- 4xx: `FAILURE` → 400, `UNAUTHORIZED` → 401, `FORBIDDEN` → 403, `NOT_FOUND` → 404
- 4xx: `DUPLICATED` → 409, `GONE`/`EXPIRED` → 410, `PRECONDITION_FAILED` → 412
- 4xx: `UNPROCESSABLE` → 422, `LOCKED` → 423, `TOO_MANY_REQUESTS` → 429
- 5xx: `SERVICE_UNAVAILABLE` → 503

### Request Validation Flow

Requests extend `AbstractValidatedRequest` which provides:

1. **Auto-population**: Request data (JSON body, query params, route attributes, multipart form data) populates properties
2. **Security check**: Override `securityCheck()` for authorization logic (runs BEFORE validation)
3. **Auto-validation**: Symfony validator constraints run automatically (disable via `autoValidateRequest()`)
4. **User injection**: If request has `userId` property and user is authenticated, it's auto-populated
5. **getValue()**: Helper with validation and automatic type casting (string↔int↔float↔bool)
6. **File uploads**: Supports 'file' or 'files' keys for file uploads

Two populate strategies available:
- `PROPERTY_SET_STRATEGY` (default): Direct property assignment
- `SERIALIZER_STRATEGY`: Uses Symfony serializer for complex denormalization

### PATCH Requests with Undefined Pattern

For partial updates, use `Undefined` sentinel value:

```php
use TeamMatePro\Contracts\Dto\Undefined;
use TeamMatePro\UseCaseBundle\Validator\PatchValidation;

final class UpdateUserRequest extends AbstractValidatedRequest
{
    #[PatchValidation([new Assert\Email()])]
    public string|Undefined $email = new Undefined();
}
```

- `Undefined` marks properties not provided in the request
- `PatchValidation` constraint only validates if value is NOT `Undefined`
- `PartialUpdateService` maps DTOs to entities, skipping `Undefined` values

### REST API Controllers

Extend `AbstractRestApiController` for standardized JSON responses:

- `response(Result $result, $serializationGroups, $headers)`: Converts Result to JsonResponse
- `responseWithCache(Result $result, $cacheInSeconds, ...)`: Adds Cache-Control headers
- `ResultRestRenderer`: Maps ResultType enum to HTTP status codes

### Utilities

- **PartialUpdateService**: Maps DTO getters to entity setters, skipping `Undefined` values
- **ContentTypeChecker**: Detects Accept header for CSV/PDF content negotiation
- **ResultResponseFactory**: Creates CSV/blob responses from Result objects

## Key Patterns

### 1. Define DTO Interface (UCB-001)

```php
interface CreateUserDtoInterface
{
    public function getEmail(): string;
    public function getName(): string;
}
```

### 2. Create Validated Request implementing interface

```php
final class CreateUserRequest extends AbstractValidatedRequest implements CreateUserDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    public function getEmail(): string
    {
        return $this->getValue('email');
    }

    protected function securityCheck(): bool
    {
        return $this->isGranted('ROLE_ADMIN');
    }
}
```

### 3. Create UseCase with __invoke (UCB-002)

```php
final readonly class CreateUserUseCase
{
    public function __invoke(CreateUserDtoInterface $dto): Result
    {
        // Pure business logic - NO authorization here (UCB-003)
        $user = $this->factory->create($dto->getEmail());
        $this->repository->save($user);

        return Result::create(ResultType::SUCCESS_CREATED)->with($user);
    }
}
```

### 4. REST Controller with Action suffix (UCB-004, UCB-005)

```php
final class UserController extends AbstractRestApiController
{
    #[Route('/api/users', methods: ['POST'])]
    public function createUserAction(CreateUserRequest $request, CreateUserUseCase $useCase): JsonResponse
    {
        return $this->response($useCase($request), ['user:read']);
    }
}
```

## Testing Structure

Tests located in `tests/Unit/` mirroring `src/` structure.

**Test naming convention (Given-When-Then with camelCase):**
```php
#[Test]
public function newUserWithValidEmailIsCreatedSuccessfully(): void
{
    // Given: <setup>
    // When: <action>
    // Then: <assertions>
}
```

**Mother Objects**: Test data builders in `tests/_Data/MotherObject/` for Result, Collection, Pagination

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
  - `make phpstan` - Run static analysis
  - `make phpstan_baseline` - Regenerate baseline
- **Baseline**: `phpstan-baseline.neon` contains known issues

### PHPUnit
- **Version**: 10.x (latest stable)
- **Features**: Attributes-based configuration, data providers, testdox output with colors
- **Coverage**: 165 tests, 297 assertions across all core components
