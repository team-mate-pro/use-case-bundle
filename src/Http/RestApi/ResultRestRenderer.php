<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Result;

use function is_array;

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
        $data = $result->getResult();
        $metadata = $result->getMeta();
        $itemOrCollection = $result->getItemType();

        // Backward compatibility: legacy `Result::with()` does not set itemType, so it stays
        // at the default 'item'. When the data shape implies a collection, fall back to
        // shape-based detection. Explicit `withCollection()` bypasses this branch entirely.
        if (
            $itemOrCollection === self::ITEM
            && (is_array($data) || $data instanceof PaginatedCollection)
        ) {
            $itemOrCollection = self::COLLECTION;
        }

        $dataType = $result->getDataType();
        if ($dataType !== null) {
            $metadata['type'] = $dataType['fqcn'];
        }

        if ($data instanceof PaginatedCollection) {
            $metadata['count'] = $data->getCount();
            $metadata['limit'] = $data->getPagination()->getLimit();
        }

        return [
            $itemOrCollection => $data,
            'message' => $result->getMessage(),
            'code' => $this->httpStatusCodeResolver->resolve($result->getType()),
            'errorCode' => $result->getErrorCode(),
            'metadata' => $metadata,
        ];
    }
}
