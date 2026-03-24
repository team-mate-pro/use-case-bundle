# Super Simple Architecture (SSA) Core Bundle

A Symfony PHP bundle providing architectural building blocks for clean REST API development. SSA enforces a use-case driven architecture with validated requests, standardized result objects, and consistent HTTP responses.

**Documentation**: [https://serek.dev/super-simple-architecture-by-serek-ssa](https://serek.dev/super-simple-architecture-by-serek-ssa)

## Overview

The Super Simple Architecture approach focuses on:

- **Use-case driven design**: Business logic encapsulated in dedicated use case classes with `__invoke()` method
- **Interface-based DTOs**: Use cases accept interfaces, not concrete request classes, for loose coupling
- **Validated requests**: Automatic request validation with Symfony constraints and authorization via `securityCheck()`
- **Standardized results**: Type-safe Result objects with consistent error handling and HTTP status mapping
- **REST API patterns**: Controllers that transform use case results into proper HTTP responses using `$this->response()`
- **Partial updates**: PATCH request support with `Undefined` sentinel values and conditional validation

## Installation

```bash
composer require team-mate-pro/use-case-bundle
```

## Quick Start

### 1. Define a DTO Interface

Use cases should depend on interfaces, not concrete request classes. This enables loose coupling and testability.

```php
interface CreateUserDtoInterface
{
    public function getEmail(): string;
    public function getName(): string;
}
```

### 2. Create a Validated Request

```php
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest extends AbstractValidatedRequest implements CreateUserDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $name;

    public function getEmail(): string
    {
        return $this->getValue('email');
    }

    public function getName(): string
    {
        return $this->getValue('name');
    }

    protected function securityCheck(): bool
    {
        return $this->isGranted('ROLE_ADMIN');
    }
}
```

Request objects automatically:
- Populate from JSON body, query params, route attributes, and multipart form data
- Validate using Symfony validator constraints
- Inject authenticated user ID if `userId` property exists
- Handle file uploads via 'file' or 'files' keys
- Throw `AccessDeniedException` if `securityCheck()` returns false

### 3. Create a Use Case

Use cases contain pure business logic. They accept DTO interfaces (not concrete requests) and return Result objects.

```php
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;

final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepository $repository,
        private UserFactory $factory
    ) {}

    public function __invoke(CreateUserDtoInterface $dto): Result
    {
        if ($this->repository->existsByEmail($dto->getEmail())) {
            return Result::create(ResultType::DUPLICATED, 'User already exists')
                ->withErrorCode('USER_EXISTS');
        }

        $user = $this->factory->create(
            email: $dto->getEmail(),
            name: $dto->getName()
        );

        $this->repository->save($user);

        return Result::create(ResultType::SUCCESS_CREATED)
            ->with($user);
    }
}
```

**Important**: Use cases must NOT contain authorization logic. Authorization belongs in the Request's `securityCheck()` method.

### 4. Create a REST Controller

Controllers use the `Action` suffix convention and delegate to use cases via `$this->response()`.

```php
use TeamMatePro\UseCaseBundle\Http\AbstractRestApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractRestApiController
{
    #[Route('/api/users', methods: ['POST'])]
    public function createUserAction(
        CreateUserRequest $request,
        CreateUserUseCase $useCase
    ): JsonResponse {
        return $this->response($useCase($request), ['user:read']);
    }

    #[Route('/api/users/{userId}', methods: ['GET'])]
    public function getUserAction(
        GetUserRequest $request,
        GetUserUseCase $useCase
    ): JsonResponse {
        return $this->response($useCase($request), ['user:read', 'user:details']);
    }

    #[Route('/api/users/{userId}', methods: ['PATCH'])]
    public function updateUserAction(
        UpdateUserRequest $request,
        UpdateUserUseCase $useCase
    ): JsonResponse {
        return $this->response($useCase($request), ['user:read']);
    }

    #[Route('/api/users/{userId}', methods: ['DELETE'])]
    public function deleteUserAction(
        DeleteUserRequest $request,
        DeleteUserUseCase $useCase
    ): JsonResponse {
        return $this->response($useCase($request));
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
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;

// Success with data
Result::create(ResultType::SUCCESS)->with($user);

// Created resource
Result::create(ResultType::SUCCESS_CREATED)->with($team);

// Failure with error code
Result::create(ResultType::DUPLICATED, 'Email already exists')
    ->withErrorCode('EMAIL_TAKEN');

// Not found
Result::create(ResultType::NOT_FOUND, 'User not found');

// No content (for DELETE operations)
Result::create(ResultType::SUCCESS_NO_CONTENT);

// Accepted (async operation)
Result::create(ResultType::ACCEPTED);

// Collection with metadata
Result::create()->with($users)
    ->withMeta('total', 100)
    ->withMeta('page', 1);
```

**ResultType enum** maps to HTTP status codes:

| ResultType | HTTP Status | Usage |
|------------|-------------|-------|
| `SUCCESS` | 200 OK | Successful GET, PATCH operations |
| `SUCCESS_CREATED` | 201 Created | Successful POST creating a resource |
| `ACCEPTED` | 202 Accepted | Async operations, background jobs |
| `SUCCESS_NO_CONTENT` | 204 No Content | Successful DELETE operations |
| `FAILURE` | 400 Bad Request | Business rule violations |
| `UNAUTHORIZED` | 401 Unauthorized | Authentication required |
| `FORBIDDEN` | 403 Forbidden | Authenticated but not authorized |
| `NOT_FOUND` | 404 Not Found | Resource doesn't exist |
| `DUPLICATED` | 409 Conflict | Resource already exists |
| `GONE` | 410 Gone | Resource was deleted |
| `EXPIRED` | 410 Gone | Resource has expired |
| `PRECONDITION_FAILED` | 412 Precondition Failed | ETag mismatch, version conflict |
| `UNPROCESSABLE` | 422 Unprocessable Entity | Semantic validation errors |
| `LOCKED` | 423 Locked | Resource locked (e.g., foreign key constraint) |
| `TOO_MANY_REQUESTS` | 429 Too Many Requests | Rate limiting |
| `SERVICE_UNAVAILABLE` | 503 Service Unavailable | Temporary unavailability |

### Validated Requests

#### Auto-Population

Request data is merged from multiple sources in this order:
1. Route attributes (from URL path)
2. JSON body
3. Query parameters
4. POST form data (multipart/form-data)
5. File uploads (via 'file' or 'files' keys)

#### getValue() Helper with Type Casting

The `getValue()` method provides validation and automatic type casting:

```php
class UpdatePlayerRequest extends AbstractValidatedRequest
{
    public string|int|null $age;
    public string|bool|null $active;

    // Automatically casts int to string
    public function getAge(): string
    {
        return $this->getValue('age'); // "25" even if $age = 25
    }

    // Automatically casts string to bool
    public function isActive(): bool
    {
        return $this->getValue('active'); // true if $active = "true"
    }
}
```

**Casting Rules:**
- **To string**: int, float, bool (true→"1", false→"0")
- **To int**: numeric string, float (truncates), bool (true→1, false→0)
- **To float**: numeric string, int
- **To bool**: string ("1","true","yes","on"→true), int (0→false, other→true)

#### Populate Strategies

```php
// Default: Direct property assignment
class MyRequest extends AbstractValidatedRequest
{
    protected function getPopulateStrategy(): string
    {
        return self::PROPERTY_SET_STRATEGY; // default
    }
}

// Serializer: For complex denormalization
class ComplexRequest extends AbstractValidatedRequest
{
    protected function getPopulateStrategy(): string
    {
        return self::SERIALIZER_STRATEGY;
    }
}
```

### PATCH Requests with Undefined Pattern

For partial updates, use the `Undefined` sentinel value and `PatchValidation` constraint:

```php
use TeamMatePro\Contracts\Dto\Undefined;
use TeamMatePro\UseCaseBundle\Validator\PatchValidation;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequest extends AbstractValidatedRequest implements UpdateUserDtoInterface
{
    #[PatchValidation([
        new Assert\NotBlank(),
        new Assert\Email(),
    ])]
    public string|Undefined $email = new Undefined();

    #[PatchValidation([
        new Assert\Length(min: 2, max: 100),
    ])]
    public string|Undefined $name = new Undefined();

    public function getEmail(): string|Undefined
    {
        return $this->getValue('email');
    }

    public function getName(): string|Undefined
    {
        return $this->getValue('name');
    }
}
```

The `PatchValidation` constraint:
- Only validates properties that were explicitly provided in the request
- Skips validation for properties that remain `Undefined`
- Allows you to have required validation on fields that are optional to send

### PartialUpdateService

Map values from DTOs to entities, automatically skipping `Undefined` values:

```php
use TeamMatePro\UseCaseBundle\Utils\PartialUpdateService;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserRepository $repository,
        private PartialUpdateService $partialUpdate
    ) {}

    public function __invoke(UpdateUserDtoInterface $dto): Result
    {
        $user = $this->repository->getOne($dto->getUserId());

        // Only updates properties that aren't Undefined
        $this->partialUpdate->map($dto, $user);

        $this->repository->save($user);

        return Result::create()->with($user);
    }
}
```

The `PartialUpdateService`:
- Maps getters from source (`getEmail()`) to setters on target (`setEmail()`) or public properties
- Automatically skips values that are instances of `Undefined`
- Supports a `$strict` mode that throws exceptions for unmapped properties
- Supports a `$skips` array to exclude specific properties

### Repository Collections

```php
use TeamMatePro\Contracts\Collection\Pagination;
use TeamMatePro\Contracts\Collection\PaginatedCollection;

// Create pagination
$pagination = new Pagination(page: 1, limit: 20);

// Return paginated collection
$items = $this->repository->findAll($pagination);
$collection = new PaginatedCollection(
    items: $items,
    count: $this->repository->count(),
    pagination: $pagination
);

// Use in Result
return Result::create()->with($collection);
```

For requests with pagination support, use the `PaginationTrait`:

```php
use TeamMatePro\UseCaseBundle\Http\PaginationTrait;

final class FindUsersRequest extends AbstractValidatedRequest implements FindUsersDtoInterface
{
    use PaginationTrait;

    // Provides: $page, $perPage properties and getPagination() method
}
```

### Content Negotiation

Check Accept headers to determine response format:

```php
use TeamMatePro\UseCaseBundle\Http\ContentType\ContentTypeChecker;

final class ExportController extends AbstractRestApiController
{
    #[Route('/api/users', methods: ['GET'])]
    public function findUsersAction(
        FindUsersRequest $request,
        FindUsersUseCase $useCase,
        ContentTypeChecker $contentTypeChecker,
        CsvResponseFactory $csvFactory
    ): Response {
        $result = $useCase($request);

        if ($contentTypeChecker->isCsvRequest($request)) {
            return $csvFactory->createCsvResponse($result, ['user:export']);
        }

        if ($contentTypeChecker->isPdfRequest($request)) {
            return $this->createPdfResponse($result);
        }

        return $this->response($result, ['user:read']);
    }
}
```

The `ContentTypeChecker` supports:
- **CSV detection**: `text/csv`, `application/csv`, `text/comma-separated-values`
- **PDF detection**: `application/pdf`
- Case-insensitive matching

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

## Architecture Standards

This bundle is designed to work with the TMP Standards (UCB rules). Key principles:

### UCB-001: UseCase Parameters Must Be Interfaces

```php
// Correct: UseCase accepts interface
public function __invoke(CreateUserDtoInterface $dto): Result

// Wrong: UseCase accepts concrete class
public function __invoke(CreateUserRequest $request): Result
```

### UCB-002: UseCase Must Have __invoke Method

```php
// Correct: Single entry point via __invoke
final readonly class CreateUserUseCase
{
    public function __invoke(CreateUserDtoInterface $dto): Result { }
}

// Wrong: Named method
final readonly class CreateUserUseCase
{
    public function execute(CreateUserDtoInterface $dto): Result { }
}
```

### UCB-003: No Authorization in UseCase Layer

Authorization belongs in the Request's `securityCheck()` method, NOT in the UseCase.

```php
// Correct: Authorization in Request
final class CreateUserRequest extends AbstractValidatedRequest
{
    protected function securityCheck(): bool
    {
        return $this->isGranted('ROLE_ADMIN');
    }
}

// Wrong: Security in UseCase
final readonly class CreateUserUseCase
{
    public function __construct(private Security $security) {} // Forbidden!

    public function __invoke(CreateUserDtoInterface $dto): Result
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) { } // Forbidden!
    }
}
```

### UCB-004: Controller Must Use $this->response()

```php
// Correct: Use $this->response()
return $this->response($useCase($request), ['user:read']);

// Wrong: Manual JSON construction
return $this->json(['user' => $user]);
```

### UCB-005: Controller Action Methods Must Have "Action" Suffix

```php
// Correct
public function createUserAction(): JsonResponse { }

// Wrong
public function createUser(): JsonResponse { }
```

## Architecture Flow

```
HTTP Request
    ↓
Controller receives Request object
    ↓
Request auto-validates (constraints)
    ↓
Request checks authorization (securityCheck())
    ↓
Controller invokes UseCase with Request (implements DTO interface)
    ↓
UseCase executes pure business logic
    ↓
UseCase returns Result object
    ↓
Controller converts Result to JsonResponse via $this->response()
    ↓
HTTP Response with proper status code
```

## Error Handling

Event listeners provide automatic exception handling:

- **ValidationExceptionListener**: Catches validation exceptions, returns structured error JSON
- **AuthorizationExceptionListener**: Handles access denied exceptions with 403 responses
- **HttpMalformedRequestException**: Thrown by `getValue()` for null/undefined/unset properties

### Error Codes

Use error codes for client-side handling of specific failure cases:

```php
final class ErrorCodes
{
    public const int USER_ALREADY_EXISTS = 100;
    public const int EMAIL_ALREADY_TAKEN = 101;
    public const int INVALID_PASSWORD = 102;
}

// In use case
return Result::create(ResultType::DUPLICATED, 'Email already exists')
    ->withErrorCode(ErrorCodes::EMAIL_ALREADY_TAKEN);
```

## Development

This bundle uses Docker for development. All commands run inside containers.

### Setup

```bash
# Clone the repository
git clone <repository-url>
cd use-case-bundle

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

## Testing Your Integration

```php
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;

class CreateUserUseCaseTest extends TestCase
{
    #[Test]
    public function newUserIsCreatedSuccessfully(): void
    {
        // Given
        $dto = $this->createMock(CreateUserDtoInterface::class);
        $dto->method('getEmail')->willReturn('test@example.com');
        $dto->method('getName')->willReturn('Test User');

        // When
        $result = $this->useCase->__invoke($dto);

        // Then
        $this->assertSame(ResultType::SUCCESS_CREATED, $result->getType());
        $this->assertNotNull($result->getResult());
    }

    #[Test]
    public function duplicateEmailReturnsDuplicatedResult(): void
    {
        // Given: existing user with same email
        $this->givenUserExistsWithEmail('test@example.com');

        $dto = $this->createMock(CreateUserDtoInterface::class);
        $dto->method('getEmail')->willReturn('test@example.com');

        // When
        $result = $this->useCase->__invoke($dto);

        // Then
        $this->assertSame(ResultType::DUPLICATED, $result->getType());
        $this->assertSame('USER_EXISTS', $result->getErrorCode());
    }
}
```

## Requirements

- PHP >= 8.3
- Symfony >= 7.0
- Docker (for development)

## Configuration

No special configuration required. The bundle auto-configures when installed in a Symfony application.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure PHPStan passes at max level
5. Submit a pull request

## License

Proprietary - Team Mate Pro

## Author

Sebastian Twaróg (sebastian.twarog1989@gmail.com)

## Links

- Documentation: https://serek.dev/super-simple-architecture-by-serek-ssa
- Package: team-mate-pro/use-case-bundle
