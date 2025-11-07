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
        $this->assertSame(200, ResultRestRenderer::getHttpStatusCode(ResultType::SUCCESS));
        $this->assertSame(204, ResultRestRenderer::getHttpStatusCode(ResultType::SUCCESS_NO_CONTENT));
        $this->assertSame(400, ResultRestRenderer::getHttpStatusCode(ResultType::FAILURE));
        $this->assertSame(202, ResultRestRenderer::getHttpStatusCode(ResultType::ACCEPTED));
        $this->assertSame(409, ResultRestRenderer::getHttpStatusCode(ResultType::DUPLICATED));
        $this->assertSame(404, ResultRestRenderer::getHttpStatusCode(ResultType::NOT_FOUND));
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
