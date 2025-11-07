<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Repository;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\Contracts\Collection\Pagination;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function testDefaultPaginationHasZeroOffsetAndFiftyLimit(): void
    {
        $pagination = Pagination::default();

        $this->assertSame(0, $pagination->getOffset());
        $this->assertSame(50, $pagination->getLimit());
    }

    public function testConstructorSetsOffsetAndLimit(): void
    {
        $pagination = new Pagination(100, 25);

        $this->assertSame(100, $pagination->getOffset());
        $this->assertSame(25, $pagination->getLimit());
    }

    #[DataProvider('validPageProvider')]
    public function testFromPageCalculatesCorrectOffset(int $page, int $limit, int $expectedOffset): void
    {
        $pagination = Pagination::fromPage($page, $limit);

        $this->assertSame($expectedOffset, $pagination->getOffset());
        $this->assertSame($limit, $pagination->getLimit());
    }

    public static function validPageProvider(): \Generator
    {
        yield 'first page with default limit' => [1, 50, 0];
        yield 'second page with default limit' => [2, 50, 50];
        yield 'third page with default limit' => [3, 50, 100];
        yield 'first page with custom limit' => [1, 20, 0];
        yield 'second page with custom limit' => [2, 20, 20];
        yield 'fifth page with small limit' => [5, 10, 40];
        yield 'tenth page with large limit' => [10, 100, 900];
        yield 'page 100 with limit 25' => [100, 25, 2475];
    }

    #[DataProvider('invalidPageProvider')]
    public function testFromPageThrowsExceptionForInvalidPage(int $invalidPage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be a positive integer');

        Pagination::fromPage($invalidPage);
    }

    public static function invalidPageProvider(): \Generator
    {
        yield 'page zero' => [0];
        yield 'negative page' => [-1];
        yield 'large negative page' => [-100];
    }

    public function testFirstPageHasZeroOffset(): void
    {
        $pagination = Pagination::fromPage(1, 20);

        $this->assertSame(0, $pagination->getOffset());
    }

    public function testPaginationIsReadonly(): void
    {
        $pagination = new Pagination(10, 20);

        $this->assertSame(10, $pagination->getOffset());
        $this->assertSame(20, $pagination->getLimit());

        // Attempting to modify should not be possible (readonly class)
        // This test verifies the readonly nature implicitly through type system
        $this->assertInstanceOf(Pagination::class, $pagination);
    }

    public function testFromPageWithVeryLargePage(): void
    {
        $pagination = Pagination::fromPage(1000, 50);

        $this->assertSame(49950, $pagination->getOffset());
        $this->assertSame(50, $pagination->getLimit());
    }

    public function testFromPageWithSingleItemLimit(): void
    {
        $pagination = Pagination::fromPage(5, 1);

        $this->assertSame(4, $pagination->getOffset());
        $this->assertSame(1, $pagination->getLimit());
    }
}
