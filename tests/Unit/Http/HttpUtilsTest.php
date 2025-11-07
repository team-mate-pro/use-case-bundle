<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(HttpUtils::class)]
final class HttpUtilsTest extends TestCase
{
    #[DataProvider('jsonContentTypeProvider')]
    public function testIsJsonReturnsTrueForJsonContentType(string $contentType, bool $expected): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', $contentType);

        $this->assertSame($expected, HttpUtils::isJson($request));
    }

    public static function jsonContentTypeProvider(): \Generator
    {
        yield 'application/json' => ['application/json', true];
        yield 'application/xml' => ['application/xml', false];
        yield 'text/html' => ['text/html', false];
        yield 'text/plain' => ['text/plain', false];
        yield 'multipart/form-data' => ['multipart/form-data', false];
        yield 'application/x-www-form-urlencoded' => ['application/x-www-form-urlencoded', false];
        yield 'application/json with charset' => ['application/json; charset=utf-8', false]; // Exact match required
        yield 'empty string' => ['', false];
    }

    public function testIsJsonReturnsFalseWhenNoContentTypeHeader(): void
    {
        $request = new Request();

        $this->assertFalse(HttpUtils::isJson($request));
    }

    public function testIsJsonWithMultipleHeaders(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Accept', 'application/json');

        $this->assertTrue(HttpUtils::isJson($request));
    }

    public function testIsJsonIsCaseSensitive(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'Application/JSON');

        // Symfony normalizes header values to lowercase
        $this->assertFalse(HttpUtils::isJson($request));
    }
}
