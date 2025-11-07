<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when some value of Request dto is attempted to load is mandatory and not set.
 */
final class HttpMalformedRequestException extends HttpException
{
    public function __construct(int $statusCode = Response::HTTP_BAD_REQUEST, string $message = '', ?\Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}