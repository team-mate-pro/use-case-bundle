<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\RestApi;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Serializer\SerializerInterface;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;
use TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController;

final class AbstractRestApiControllerTest extends TestCase
{
    #[Test]
    public function responseReturnsJsonResponseWithCorrectStatusCode(): void
    {
        $controller = $this->createController();

        $result = Result::create(ResultType::SUCCESS)->with(['data' => 'value']);
        $response = $controller->response($result);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function responseWithStringSerializationGroup(): void
    {
        $controller = $this->createController();

        $result = Result::create(ResultType::SUCCESS_CREATED)->with(new \stdClass());
        $response = $controller->response($result, 'detail');

        self::assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function responseWithArraySerializationGroups(): void
    {
        $controller = $this->createController();

        $result = Result::create()->with(null);
        $response = $controller->response($result, ['list', 'detail']);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function responseWithCustomHeaders(): void
    {
        $controller = $this->createController();

        $result = Result::create();
        $response = $controller->response($result, null, ['X-Custom' => 'value']);

        self::assertSame('value', $response->headers->get('X-Custom'));
    }

    #[Test]
    public function responseWithCacheIntValue(): void
    {
        $controller = $this->createController();

        $result = Result::create();
        $response = $controller->responseWithCache($result, 3600);

        self::assertStringContainsString('s-maxage=3600', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function responseWithCacheArrayValue(): void
    {
        $controller = $this->createController();

        $result = Result::create();
        $response = $controller->responseWithCache($result, [600, 300]);

        $cacheControl = (string) $response->headers->get('Cache-Control');
        self::assertStringContainsString('s-maxage=600', $cacheControl);
        self::assertStringContainsString('max-age=300', $cacheControl);
    }

    #[Test]
    public function responseWithCacheNullValue(): void
    {
        $controller = $this->createController();

        $result = Result::create();
        $response = $controller->responseWithCache($result, null);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function responseWithCacheZeroValues(): void
    {
        $controller = $this->createController();

        $result = Result::create();
        $response = $controller->responseWithCache($result, [0, 0]);

        // No cache header added when both are 0
        self::assertSame(200, $response->getStatusCode());
    }

    private function createController(): AbstractRestApiController
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');

        $container = new Container();
        $container->set('serializer', $serializer);

        $controller = new class extends AbstractRestApiController {
        };
        $controller->setContainer($container);

        return $controller;
    }
}
