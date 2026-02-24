<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\ContentType;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\ContentType\ContentTypeChecker;
use TeamMatePro\UseCaseBundle\Http\ContentType\HeadersAwareInterface;

#[CoversClass(ContentTypeChecker::class)]
final class ContentTypeCheckerTest extends TestCase
{
    private ContentTypeChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ContentTypeChecker();
    }

    #[DataProvider('csvMimeTypeProvider')]
    public function testIsCsvRequestReturnsTrueForCsvMimeTypes(string $acceptHeader): void
    {
        $request = $this->createRequestWithAcceptHeader($acceptHeader);

        $this->assertTrue($this->checker->isCsvRequest($request));
    }

    public static function csvMimeTypeProvider(): \Generator
    {
        yield 'text/csv' => ['text/csv'];
        yield 'application/csv' => ['application/csv'];
        yield 'text/comma-separated-values' => ['text/comma-separated-values'];
        yield 'text/csv with charset' => ['text/csv; charset=utf-8'];
        yield 'text/csv uppercase' => ['TEXT/CSV'];
        yield 'text/csv mixed case' => ['Text/Csv'];
        yield 'text/csv with whitespace' => ['  text/csv  '];
        yield 'multiple types with csv first' => ['text/csv, application/json'];
        yield 'multiple types with csv second' => ['application/json, text/csv'];
    }

    #[DataProvider('nonCsvMimeTypeProvider')]
    public function testIsCsvRequestReturnsFalseForNonCsvMimeTypes(string $acceptHeader): void
    {
        $request = $this->createRequestWithAcceptHeader($acceptHeader);

        $this->assertFalse($this->checker->isCsvRequest($request));
    }

    public static function nonCsvMimeTypeProvider(): \Generator
    {
        yield 'application/json' => ['application/json'];
        yield 'application/xml' => ['application/xml'];
        yield 'text/html' => ['text/html'];
        yield 'text/plain' => ['text/plain'];
        yield 'application/pdf' => ['application/pdf'];
        yield 'empty string' => [''];
    }

    public function testIsCsvRequestReturnsFalseWhenNoAcceptHeader(): void
    {
        $request = $this->createMock(HeadersAwareInterface::class);
        $request->method('getHeader')->with('accept')->willReturn(null);

        $this->assertFalse($this->checker->isCsvRequest($request));
    }

    #[DataProvider('pdfMimeTypeProvider')]
    public function testIsPdfRequestReturnsTrueForPdfMimeTypes(string $acceptHeader): void
    {
        $request = $this->createRequestWithAcceptHeader($acceptHeader);

        $this->assertTrue($this->checker->isPdfRequest($request));
    }

    public static function pdfMimeTypeProvider(): \Generator
    {
        yield 'application/pdf' => ['application/pdf'];
        yield 'application/pdf with charset' => ['application/pdf; charset=utf-8'];
        yield 'application/pdf uppercase' => ['APPLICATION/PDF'];
        yield 'application/pdf mixed case' => ['Application/Pdf'];
        yield 'application/pdf with whitespace' => ['  application/pdf  '];
        yield 'multiple types with pdf first' => ['application/pdf, application/json'];
        yield 'multiple types with pdf second' => ['application/json, application/pdf'];
    }

    #[DataProvider('nonPdfMimeTypeProvider')]
    public function testIsPdfRequestReturnsFalseForNonPdfMimeTypes(string $acceptHeader): void
    {
        $request = $this->createRequestWithAcceptHeader($acceptHeader);

        $this->assertFalse($this->checker->isPdfRequest($request));
    }

    public static function nonPdfMimeTypeProvider(): \Generator
    {
        yield 'application/json' => ['application/json'];
        yield 'application/xml' => ['application/xml'];
        yield 'text/html' => ['text/html'];
        yield 'text/plain' => ['text/plain'];
        yield 'text/csv' => ['text/csv'];
        yield 'empty string' => [''];
    }

    public function testIsPdfRequestReturnsFalseWhenNoAcceptHeader(): void
    {
        $request = $this->createMock(HeadersAwareInterface::class);
        $request->method('getHeader')->with('accept')->willReturn(null);

        $this->assertFalse($this->checker->isPdfRequest($request));
    }

    private function createRequestWithAcceptHeader(string $acceptHeader): HeadersAwareInterface
    {
        $request = $this->createMock(HeadersAwareInterface::class);
        $request->method('getHeader')->with('accept')->willReturn($acceptHeader);

        return $request;
    }
}
