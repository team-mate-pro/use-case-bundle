<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use Symfony\Component\HttpFoundation\Response;
use TeamMatePro\Contracts\Collection\ResultType;

final class HttpStatusCodeResolver implements HttpStatusCodeResolverInterface
{
    public function resolve(ResultType $type): int
    {
        return match ($type) {
            // 2xx Success
            ResultType::SUCCESS => Response::HTTP_OK,
            ResultType::SUCCESS_CREATED => Response::HTTP_CREATED,
            ResultType::ACCEPTED => Response::HTTP_ACCEPTED,
            ResultType::SUCCESS_NO_CONTENT => Response::HTTP_NO_CONTENT,

            // 4xx Client Errors
            ResultType::UNAUTHORIZED => Response::HTTP_UNAUTHORIZED,
            ResultType::FORBIDDEN => Response::HTTP_FORBIDDEN,
            ResultType::NOT_FOUND => Response::HTTP_NOT_FOUND,
            ResultType::DUPLICATED => Response::HTTP_CONFLICT,
            ResultType::GONE, ResultType::EXPIRED => Response::HTTP_GONE,
            ResultType::PRECONDITION_FAILED => Response::HTTP_PRECONDITION_FAILED,
            ResultType::UNPROCESSABLE => Response::HTTP_UNPROCESSABLE_ENTITY,
            ResultType::LOCKED => Response::HTTP_LOCKED,
            ResultType::TOO_MANY_REQUESTS => Response::HTTP_TOO_MANY_REQUESTS,

            // 5xx Server Errors
            ResultType::SERVICE_UNAVAILABLE => Response::HTTP_SERVICE_UNAVAILABLE,

            // Default fallback
            default => Response::HTTP_BAD_REQUEST,
        };
    }
}
