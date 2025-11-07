<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use Symfony\Component\HttpFoundation\Response;
use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;
use function get_class;
use function is_array;
use function is_object;

final class ResultRestRenderer
{
    public const ITEM = 'item';
    public const COLLECTION = 'collection';

    /**
     * @param array<string, mixed> $extra
     * @return array{
     *     message: ?string,
     *     code: int,
     *     errorCode: ?string,
     *     ...
     * }
     */
    public static function renderMandatory(
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
    public static function render(Result $result): array
    {
        $metadata = $result->getMeta();
        $itemOrCollection = self::ITEM;

        if (is_object($result->getResult()) && !$result->getResult() instanceof PaginatedCollection) {
            $metadata['type'] = get_class($result->getResult());
        }

        if (is_array($result->getResult()) || $result->getResult() instanceof PaginatedCollection) {
            $itemOrCollection = self::COLLECTION;
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
            'code' => self::getHttpStatusCode($result->getType()),
            'errorCode' => $result->getErrorCode(),
            'metadata' => $metadata,
        ];
    }

    public static function getHttpStatusCode(ResultType $type): int
    {
        return match ($type) {
            ResultType::SUCCESS => Response::HTTP_OK,
            ResultType::SUCCESS_CREATED => Response::HTTP_CREATED,
            ResultType::LOCKED => Response::HTTP_LOCKED,
            ResultType::SUCCESS_NO_CONTENT => Response::HTTP_NO_CONTENT,
            ResultType::ACCEPTED => Response::HTTP_ACCEPTED,
            ResultType::NOT_FOUND => Response::HTTP_NOT_FOUND,
            ResultType::DUPLICATED => Response::HTTP_CONFLICT,
            ResultType::GONE, ResultType::EXPIRED => Response::HTTP_GONE,
            default => Response::HTTP_BAD_REQUEST,
        };
    }
}
