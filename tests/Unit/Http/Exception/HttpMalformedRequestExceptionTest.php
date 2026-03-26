<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use TeamMatePro\UseCaseBundle\Http\Exception\HttpMalformedRequestException;

final class HttpMalformedRequestExceptionTest extends TestCase
{
    #[Test]
    public function defaultsToHttpBadRequest(): void
    {
        $exception = new HttpMalformedRequestException();

        self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        self::assertSame('', $exception->getMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new HttpMalformedRequestException(message: 'Field is missing');

        self::assertSame('Field is missing', $exception->getMessage());
    }

    #[Test]
    public function acceptsCustomStatusCode(): void
    {
        $exception = new HttpMalformedRequestException(statusCode: 422);

        self::assertSame(422, $exception->getStatusCode());
    }
}
