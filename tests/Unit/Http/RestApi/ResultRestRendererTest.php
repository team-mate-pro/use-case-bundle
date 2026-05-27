<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\RestApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Pagination;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;
use TeamMatePro\UseCaseBundle\Http\RestApi\HttpStatusCodeResolver;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;

#[CoversClass(ResultRestRenderer::class)]
final class ResultRestRendererTest extends TestCase
{
    private ResultRestRenderer $sut;

    protected function setUp(): void
    {
        $this->sut = new ResultRestRenderer(new HttpStatusCodeResolver());
    }

    public function testRenderExplicitItemAsItem(): void
    {
        $result = Result::create()->withItem(new stdClass());

        $keys = array_keys($this->sut->render($result));

        $this->assertContains('item', $keys);
    }

    public function testRenderExplicitCollectionAsCollection(): void
    {
        $result = Result::create()->withCollection([new stdClass()]);

        $keys = array_keys($this->sut->render($result));

        $this->assertContains('collection', $keys);
    }

    public function testRenderMandatoryHasMandatoryFields(): void
    {
        $result = $this->sut->renderMandatory();
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('errorCode', $result);
    }

    public function testRender(): void
    {
        $result = Result::create();

        $this->assertEquals(ResultType::SUCCESS, $result->getType());

        $sut = $this->sut->render($result);

        $keys = array_keys($sut);

        $this->assertContains('item', $keys);
        $this->assertContains('message', $keys);
        $this->assertContains('code', $keys);
        $this->assertContains('metadata', $keys);
    }

    public function testHasItemWhenObjectPassed(): void
    {
        $result = Result::create()->withItem(new stdClass());

        $sut = $this->sut->render($result);

        $this->assertArrayHasKey('item', $sut);
    }

    public function testHasCollectionWhenCollectionPassed(): void
    {
        $result = Result::create()->withItem(new PaginatedCollection([], 1, new Pagination(0, 5)));

        $sut = $this->sut->render($result);

        $this->assertArrayHasKey('collection', $sut);
        self::assertIsArray($sut['metadata']);
        $this->assertArrayHasKey('count', $sut['metadata']);
        $this->assertArrayHasKey('limit', $sut['metadata']);
    }
}
