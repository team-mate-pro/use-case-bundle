<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\RestApi;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;

final class ResultRestRendererArrayCollectionTest extends TestCase
{
    #[Test]
    public function renderWithArrayOfObjectsSetsTypeMetadata(): void
    {
        $obj = new stdClass();
        $result = Result::create()->with([$obj]);

        $rendered = ResultRestRenderer::render($result);

        self::assertArrayHasKey('collection', $rendered);
        /** @var array{metadata: array{type: string}} $rendered */
        self::assertSame(stdClass::class, $rendered['metadata']['type']);
    }

    #[Test]
    public function renderWithPlainArrayCollection(): void
    {
        $result = Result::create()->with(['a', 'b']);

        $rendered = ResultRestRenderer::render($result);

        self::assertArrayHasKey('collection', $rendered);
    }

    #[Test]
    public function renderMandatoryWithCustomValues(): void
    {
        $rendered = ResultRestRenderer::renderMandatory(
            message: 'Error',
            code: 400,
            errorCode: 'VALIDATION_FAILED',
            extra: ['detail' => 'something']
        );

        self::assertSame('Error', $rendered['message']);
        self::assertSame(400, $rendered['code']);
        self::assertSame('VALIDATION_FAILED', $rendered['errorCode']);
        /** @phpstan-ignore offsetAccess.notFound */
        self::assertSame('something', $rendered['detail']);
    }
}
