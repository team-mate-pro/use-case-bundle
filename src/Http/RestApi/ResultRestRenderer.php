<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Result;

use function get_class;
use function is_array;
use function is_object;

final class ResultRestRenderer implements ResultRendererInterface
{
    public const ITEM = 'item';
    public const COLLECTION = 'collection';

    public function __construct(
        private readonly HttpStatusCodeResolverInterface $httpStatusCodeResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     * @return array{message: ?string, code: int, errorCode: ?string, ...}
     */
    public function renderMandatory(
        ?string $message = null,
        int $code = 200,
        ?string $errorCode = null,
        array $extra = []
    ): array {
        return array_merge($extra, [
            'message' => $message,
            'code' => $code,
            'errorCode' => $errorCode,
        ]);
    }

    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return array<string, mixed>
     */
    public function render(Result $result): array
    {
        $metadata = $result->getMeta();
        $itemOrCollection = $result->getItemType();

        // Backward compatibility: legacy `Result::with()` does not set itemType, so it stays
        // at the default 'item'. When the data shape implies a collection, fall back to
        // shape-based detection. Explicit `withCollection()` bypasses this branch entirely.
        if (
            $itemOrCollection === self::ITEM
            && (is_array($result->getResult()) || $result->getResult() instanceof PaginatedCollection)
        ) {
            $itemOrCollection = self::COLLECTION;
        }

        if (is_object($result->getResult()) && !$result->getResult() instanceof PaginatedCollection) {
            $metadata['type'] = get_class($result->getResult());
        }

        if (is_array($result->getResult()) && isset($result->getResult()[0]) && is_object($result->getResult()[0])) {
            $metadata['type'] = get_class($result->getResult()[0]);
        }

        if ($result->getResult() instanceof PaginatedCollection) {
            $metadata['count'] = $result->getResult()->getCount();
            $metadata['limit'] = $result->getResult()->getPagination()->getLimit();
        }

        return [
            $itemOrCollection => $result->getResult(),
            'message' => $result->getMessage(),
            'code' => $this->httpStatusCodeResolver->resolve($result->getType()),
            'errorCode' => $result->getErrorCode(),
            'metadata' => $metadata,
        ];
    }
}
