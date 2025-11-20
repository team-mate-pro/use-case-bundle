<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\PaginationTrait;
use TeamMatePro\Contracts\Collection\Pagination;

#[CoversClass(PaginationTrait::class)]
final class PaginationTraitTest extends TestCase
{
    public function testGetPaginationWithDefaultValues(): void
    {
        $sut = new class {
            use PaginationTrait;
        };

        $pagination = $sut->getPagination();

        $this->assertSame(0, $pagination->getOffset());
        $this->assertSame(20, $pagination->getLimit());
    }

    public function testGetPaginationWithStringPage(): void
    {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->page = '2';

        $pagination = $sut->getPagination();

        $this->assertSame(20, $pagination->getOffset());
        $this->assertSame(20, $pagination->getLimit());
    }

    public function testGetPaginationWithIntPage(): void
    {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->page = 3;

        $pagination = $sut->getPagination();

        $this->assertSame(40, $pagination->getOffset());
        $this->assertSame(20, $pagination->getLimit());
    }

    public function testGetPaginationWithStringPerPage(): void
    {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->perPage = '10';

        $pagination = $sut->getPagination();

        $this->assertSame(0, $pagination->getOffset());
        $this->assertSame(10, $pagination->getLimit());
    }

    public function testGetPaginationWithIntPerPage(): void
    {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->perPage = 50;

        $pagination = $sut->getPagination();

        $this->assertSame(0, $pagination->getOffset());
        $this->assertSame(50, $pagination->getLimit());
    }

    #[DataProvider('paginationDataProvider')]
    public function testGetPaginationWithVariousValues(
        string|int|null $page,
        string|int|null $perPage,
        int $expectedOffset,
        int $expectedLimit
    ): void {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->page = $page;
        $sut->perPage = $perPage;

        $pagination = $sut->getPagination();

        $this->assertSame($expectedOffset, $pagination->getOffset());
        $this->assertSame($expectedLimit, $pagination->getLimit());
    }

    /**
     * @return array<string, array{page: string|int|null, perPage: string|int|null, expectedOffset: int, expectedLimit: int}>
     */
    public static function paginationDataProvider(): array
    {
        return [
            'first page with default limit' => [
                'page' => 1,
                'perPage' => null,
                'expectedOffset' => 0,
                'expectedLimit' => 20,
            ],
            'second page with default limit' => [
                'page' => 2,
                'perPage' => null,
                'expectedOffset' => 20,
                'expectedLimit' => 20,
            ],
            'first page with custom limit' => [
                'page' => 1,
                'perPage' => 10,
                'expectedOffset' => 0,
                'expectedLimit' => 10,
            ],
            'third page with custom limit' => [
                'page' => 3,
                'perPage' => 15,
                'expectedOffset' => 30,
                'expectedLimit' => 15,
            ],
            'string page and perPage' => [
                'page' => '5',
                'perPage' => '25',
                'expectedOffset' => 100,
                'expectedLimit' => 25,
            ],
            'large page number' => [
                'page' => 100,
                'perPage' => 50,
                'expectedOffset' => 4950,
                'expectedLimit' => 50,
            ],
            'null values use defaults' => [
                'page' => null,
                'perPage' => null,
                'expectedOffset' => 0,
                'expectedLimit' => 20,
            ],
        ];
    }

    public function testGetPaginationConvertsTypesCorrectly(): void
    {
        $sut = new class {
            use PaginationTrait;
        };
        $sut->page = '10';
        $sut->perPage = '100';

        $pagination = $sut->getPagination();

        $this->assertSame(900, $pagination->getOffset());
        $this->assertSame(100, $pagination->getLimit());
    }
}
