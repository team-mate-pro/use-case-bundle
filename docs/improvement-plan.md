# Use Case Bundle - Improvement Plan

This document outlines proposed features to extend the use-case-bundle functionality.

## Completed

### 1. Extended ResultTypes (contracts v1.2.0)

Added missing HTTP status code mappings:
- `UNAUTHORIZED` (401) - Authentication required
- `FORBIDDEN` (403) - Authenticated but not authorized
- `PRECONDITION_FAILED` (412) - ETag mismatch, version conflict
- `UNPROCESSABLE` (422) - Semantic validation errors
- `TOO_MANY_REQUESTS` (429) - Rate limiting
- `SERVICE_UNAVAILABLE` (503) - Temporary unavailability

---

## Planned Features

### 2. Batch Operation Support

Handle bulk create/update/delete with partial failure reporting.

```php
final readonly class BatchResult
{
    /** @param array<int, Result> $results */
    public function __construct(
        public array $results,
        public int $successCount,
        public int $failureCount,
    ) {}

    public function hasFailures(): bool;
    public function getFailedIndices(): array;
}

// Usage in UseCase
public function __invoke(BatchCreateUsersDtoInterface $dto): BatchResult
{
    $results = [];
    foreach ($dto->getUsers() as $index => $userData) {
        $results[$index] = $this->createSingleUser($userData);
    }
    return BatchResult::fromResults($results);
}
```

**Problem solved:** Importing 100 users where 3 fail - currently no standardized way to report partial success.

---

### 3. FilterableTrait and SortableTrait

Common patterns for list endpoints.

```php
trait FilterableTrait
{
    #[PatchValidation([new Assert\Type('array')])]
    public array|Undefined $filters = new Undefined();

    /** @return array<string, mixed> */
    public function getFilters(): array
    {
        return $this->filters instanceof Undefined ? [] : $this->filters;
    }

    public function hasFilter(string $key): bool;
    public function getFilter(string $key, mixed $default = null): mixed;
}

trait SortableTrait
{
    public string|Undefined $sortBy = new Undefined();
    public string|Undefined $sortDirection = new Undefined(); // 'asc' | 'desc'

    public function getOrderBy(): ?array; // Returns ['field' => 'ASC'] or null
}

// Usage
final class FindPlayersRequest extends AbstractValidatedRequest
    implements FindPlayersDtoInterface
{
    use PaginationTrait;
    use FilterableTrait;
    use SortableTrait;
}
```

**Problem solved:** Every list endpoint repeats the same filter/sort logic.

---

### 4. IdempotencyTrait

Prevent duplicate operations (critical for payments, order creation).

```php
trait IdempotencyTrait
{
    #[Assert\Uuid]
    public ?string $idempotencyKey = null;

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }
}

final readonly class IdempotencyService
{
    public function check(string $key): ?Result; // Returns cached result if exists
    public function store(string $key, Result $result, int $ttlSeconds = 3600): void;
}

// UseCase usage
public function __invoke(CreatePaymentDtoInterface $dto): Result
{
    if ($cached = $this->idempotency->check($dto->getIdempotencyKey())) {
        return $cached;
    }

    $result = $this->processPayment($dto);
    $this->idempotency->store($dto->getIdempotencyKey(), $result);

    return $result;
}
```

**Problem solved:** User double-clicks submit, network retry creates duplicate order.

---

### 5. DateRangeFilterTrait

Common temporal filtering pattern.

```php
trait DateRangeFilterTrait
{
    #[PatchValidation([new Assert\DateTime(format: 'Y-m-d')])]
    public string|Undefined $dateFrom = new Undefined();

    #[PatchValidation([new Assert\DateTime(format: 'Y-m-d')])]
    public string|Undefined $dateTo = new Undefined();

    public function getDateFrom(): ?\DateTimeImmutable;
    public function getDateTo(): ?\DateTimeImmutable;
    public function getDateRange(): ?TimeRange;
}
```

**Problem solved:** Repeated date parsing and validation in report/analytics endpoints.

---

### 6. CursorPagination

For infinite scroll and large datasets.

```php
final readonly class CursorPagination
{
    public function __construct(
        public ?string $cursor,
        public int $limit = 20,
    ) {}

    public static function fromRequest(AbstractValidatedRequest $request): self;
}

final readonly class CursorPaginatedCollection
{
    public function __construct(
        public array $items,
        public ?string $nextCursor,
        public ?string $prevCursor,
        public bool $hasMore,
    ) {}
}

trait CursorPaginationTrait
{
    public ?string $cursor = null;
    public int $limit = 20;

    public function getCursorPagination(): CursorPagination;
}
```

**Problem solved:** Offset pagination breaks with large datasets or concurrent inserts.

---

### 7. RequestContextTrait

Pass request metadata through the system.

```php
trait RequestContextTrait
{
    public function getClientIp(): ?string;
    public function getUserAgent(): ?string;
    public function getRequestId(): string; // Auto-generated UUID
    public function getCorrelationId(): ?string; // From X-Correlation-ID header
    public function getLocale(): string;
    public function getTimezone(): \DateTimeZone;
}
```

**Problem solved:** Audit logging, analytics, debugging need request context deep in the stack.

---

### 8. AsyncResult for Long-Running Operations

```php
final readonly class AsyncResult
{
    public function __construct(
        public string $jobId,
        public string $statusUrl, // Polling endpoint
        public ?string $webhookUrl = null, // Callback when done
        public ?\DateTimeImmutable $estimatedCompletion = null,
    ) {}
}

// Usage
public function __invoke(GenerateReportDtoInterface $dto): Result
{
    $jobId = $this->queue->dispatch(new GenerateReportJob($dto));

    return Result::create(ResultType::ACCEPTED)
        ->with(new AsyncResult(
            jobId: $jobId,
            statusUrl: "/api/jobs/{$jobId}/status",
        ));
}
```

**Problem solved:** Large report generation, bulk imports that take minutes.

---

### 9. ETagTrait for Optimistic Concurrency

```php
trait ETagTrait
{
    #[Assert\NotBlank(groups: ['update'])]
    public ?string $ifMatch = null; // From If-Match header

    public function getExpectedVersion(): ?string
    {
        return $this->ifMatch;
    }
}

// UseCase usage
public function __invoke(UpdateUserDtoInterface $dto): Result
{
    $user = $this->repository->getOne($dto->getUserId());

    if ($dto->getExpectedVersion() !== $user->getETag()) {
        return Result::create(ResultType::PRECONDITION_FAILED, 'Resource was modified');
    }

    // ... proceed with update
}
```

**Problem solved:** Two users edit same resource simultaneously, last one overwrites first's changes.

---

### 10. ValidationContext for Cross-Field Validation

```php
#[Attribute(Attribute::TARGET_CLASS)]
class UniqueInOrganization extends Constraint
{
    public function __construct(
        public string $field,
        public string $entityClass,
        public ?string $excludeId = null, // For updates
    ) {}
}

// Validator has access to request context
final class UniqueInOrganizationValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var AbstractValidatedRequest $request */
        $request = $this->context->getRoot();
        $organizationId = $request->getOrganizationId();

        // Check uniqueness within organization scope
    }
}

// Usage
#[UniqueInOrganization(field: 'email', entityClass: Player::class)]
final class CreatePlayerRequest extends AbstractValidatedRequest
{
    public string $email;
}
```

**Problem solved:** "Email must be unique within organization" - needs context the validator doesn't naturally have.

---

## Priority Matrix

| Priority | Feature | Impact | Effort |
|----------|---------|--------|--------|
| **Done** | Extended ResultTypes | Fixes incorrect status codes | Low |
| **High** | FilterableTrait + SortableTrait | Eliminates boilerplate | Low |
| **High** | IdempotencyTrait | Prevents data corruption | Medium |
| **Medium** | BatchResult | Common import scenarios | Medium |
| **Medium** | CursorPagination | Scalability for large data | Medium |
| **Medium** | ETagTrait | Concurrent editing safety | Low |
| **Medium** | DateRangeFilterTrait | Common pattern | Low |
| **Low** | AsyncResult | Long-running operations | Medium |
| **Low** | RequestContextTrait | Audit/debugging | Low |
| **Low** | ValidationContext | Complex validation | Medium |
