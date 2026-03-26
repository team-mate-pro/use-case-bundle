<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\UseCaseBundle\Http\ResultResponseFactory;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

final class ResultResponseFactoryBlobTest extends TestCase
{
    #[Test]
    public function createBlobResponseWithStringableItem(): void
    {
        $item = new class implements Stringable {
            public function __toString(): string
            {
                return 'blob-content';
            }
        };

        /** @var Result<Stringable> $result */
        $result = Result::create()->with($item);
        $serializer = new Serializer([], [new CsvEncoder()]);
        $factory = new ResultResponseFactory($serializer);

        $response = $factory->createBlobResponse($result);

        self::assertSame(base64_encode('blob-content'), $response->getContent());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function createBlobResponseWithCustomMime(): void
    {
        $item = new class implements Stringable {
            public function __toString(): string
            {
                return 'raw-content';
            }
        };

        /** @var Result<Stringable> $result */
        $result = Result::create()->with($item);
        $serializer = new Serializer([], [new CsvEncoder()]);
        $factory = new ResultResponseFactory($serializer);

        $response = $factory->createBlobResponse($result, base64: true, mime: 'application/pdf');

        self::assertSame(base64_encode('raw-content'), $response->getContent());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function createBlobResponseThrowsForNonStringable(): void
    {
        /** @var Result<Stringable> $result */
        $result = Result::create()->with(new \stdClass());
        $serializer = new Serializer([], [new CsvEncoder()]);
        $factory = new ResultResponseFactory($serializer);

        $this->expectException(InvalidArgumentException::class);
        $factory->createBlobResponse($result);
    }

    #[Test]
    public function createCsvResponseWithBase64(): void
    {
        $result = Result::create()->with([['a', 'b']]);
        $serializer = new Serializer([], [new CsvEncoder()]);
        $factory = new ResultResponseFactory($serializer);

        $response = $factory->createCsvResponse($result, base64: true);

        self::assertNotEmpty($response->getContent());
        self::assertNotFalse(base64_decode($response->getContent(), true));
    }

    #[Test]
    public function createCsvResponseWithStringSerializationGroup(): void
    {
        $result = Result::create()->with([['a', 'b']]);
        $serializer = new Serializer([], [new CsvEncoder()]);
        $factory = new ResultResponseFactory($serializer);

        $response = $factory->createCsvResponse($result, base64: false, serializationGroups: 'csv');

        self::assertSame('text/csv', $response->headers->get('Content-Type'));
    }
}
