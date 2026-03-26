<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\RestApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Pagination;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;

#[CoversClass(ResultRestRenderer::class)]
final class ResultRestRendererTest extends TestCase
{
    public function testRenderMandatoryHasMandatoryFields(): void
    {
        $result = ResultRestRenderer::renderMandatory();
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('errorCode', $result);
    }

    public function testRender(): void
    {
        $result = Result::create();

        $this->assertEquals(ResultType::SUCCESS, $result->getType());

        $sut = ResultRestRenderer::render($result);

        $keys = array_keys($sut);

        $this->assertContains('item', $keys);
        $this->assertContains('message', $keys);
        $this->assertContains('code', $keys);
        $this->assertContains('metadata', $keys);
    }

    public function testGetHttpStatusCode(): void
    {
        // 2xx Success
        $this->assertSame(200, ResultRestRenderer::getHttpStatusCode(ResultType::SUCCESS));
        $this->assertSame(201, ResultRestRenderer::getHttpStatusCode(ResultType::SUCCESS_CREATED));
        $this->assertSame(202, ResultRestRenderer::getHttpStatusCode(ResultType::ACCEPTED));
        $this->assertSame(204, ResultRestRenderer::getHttpStatusCode(ResultType::SUCCESS_NO_CONTENT));

        // 4xx Client Errors
        $this->assertSame(400, ResultRestRenderer::getHttpStatusCode(ResultType::FAILURE));
        $this->assertSame(401, ResultRestRenderer::getHttpStatusCode(ResultType::UNAUTHORIZED));
        $this->assertSame(403, ResultRestRenderer::getHttpStatusCode(ResultType::FORBIDDEN));
        $this->assertSame(404, ResultRestRenderer::getHttpStatusCode(ResultType::NOT_FOUND));
        $this->assertSame(409, ResultRestRenderer::getHttpStatusCode(ResultType::DUPLICATED));
        $this->assertSame(410, ResultRestRenderer::getHttpStatusCode(ResultType::GONE));
        $this->assertSame(410, ResultRestRenderer::getHttpStatusCode(ResultType::EXPIRED));
        $this->assertSame(412, ResultRestRenderer::getHttpStatusCode(ResultType::PRECONDITION_FAILED));
        $this->assertSame(422, ResultRestRenderer::getHttpStatusCode(ResultType::UNPROCESSABLE));
        $this->assertSame(423, ResultRestRenderer::getHttpStatusCode(ResultType::LOCKED));
        $this->assertSame(429, ResultRestRenderer::getHttpStatusCode(ResultType::TOO_MANY_REQUESTS));

        // 5xx Server Errors
        $this->assertSame(503, ResultRestRenderer::getHttpStatusCode(ResultType::SERVICE_UNAVAILABLE));
    }

    public function testHasItemWhenObjectPassed(): void
    {
        $result = Result::create()->with(new stdClass());

        $sut = ResultRestRenderer::render($result);

        $this->assertArrayHasKey('item', $sut);
    }

    public function testHasCollectionWhenCollectionPassed(): void
    {
        $result = Result::create()->with(new PaginatedCollection([], 1, new Pagination(0, 5)));

        $sut = ResultRestRenderer::render($result);

        $this->assertArrayHasKey('collection', $sut);
        $this->assertArrayHasKey('count', $sut['metadata']);
        $this->assertArrayHasKey('limit', $sut['metadata']);
    }
}
