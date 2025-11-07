<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data\MotherObject;

use TeamMatePro\Contracts\Collection\Pagination;

final class PaginationMother
{
    public static function default(): Pagination
    {
        return Pagination::default();
    }

    public static function firstPage(int $pageSize = 20): Pagination
    {
        return Pagination::fromPage(1, $pageSize);
    }

    public static function secondPage(int $pageSize = 20): Pagination
    {
        return Pagination::fromPage(2, $pageSize);
    }

    public static function page(int $pageNumber, int $pageSize = 20): Pagination
    {
        return Pagination::fromPage($pageNumber, $pageSize);
    }

    public static function withCustomOffsetAndLimit(int $offset, int $limit): Pagination
    {
        return new Pagination($offset, $limit);
    }

    public static function smallPage(): Pagination
    {
        return Pagination::fromPage(1, 10);
    }

    public static function largePage(): Pagination
    {
        return Pagination::fromPage(1, 100);
    }

    public static function thirdPage(): Pagination
    {
        return Pagination::fromPage(3, 50);
    }
}
