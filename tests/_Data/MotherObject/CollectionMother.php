<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data\MotherObject;

use stdClass;
use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Pagination;

final class CollectionMother
{
    /**
     * @param array<mixed> $items
     */
    public static function create(array $items, int $totalCount, ?int $limit = null): PaginatedCollection
    {
        $pagination = $limit !== null ? new Pagination(0, $limit) : null;
        return new PaginatedCollection($items, $totalCount, $pagination);
    }

    public static function empty(): PaginatedCollection
    {
        return new PaginatedCollection([], 0, new Pagination(0, 10));
    }

    public static function withStdObjects(int $count = 5, ?int $limit = 10): PaginatedCollection
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $item = new stdClass();
            $item->id = $i;
            $item->name = "Item $i";
            $items[] = $item;
        }

        $pagination = $limit !== null ? new Pagination(0, $limit) : null;
        return new PaginatedCollection($items, $count, $pagination);
    }

    public static function withPartialResults(int $itemsReturned = 10, int $totalCount = 100, int $limit = 10): PaginatedCollection
    {
        $items = [];
        for ($i = 1; $i <= $itemsReturned; $i++) {
            $item = new stdClass();
            $item->id = $i;
            $item->name = "Item $i";
            $items[] = $item;
        }

        return new PaginatedCollection($items, $totalCount, new Pagination(0, $limit));
    }

    public static function withUsers(int $count = 3): PaginatedCollection
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = new stdClass();
            $user->id = $i;
            $user->name = "User $i";
            $user->email = "user$i@example.com";
            $users[] = $user;
        }

        return new PaginatedCollection($users, $count, new Pagination(0, $count));
    }

    public static function singlePage(int $count = 5): PaginatedCollection
    {
        return self::withStdObjects($count, $count);
    }

    public static function firstPageOfMany(int $pageSize = 10, int $totalCount = 100): PaginatedCollection
    {
        return self::withPartialResults($pageSize, $totalCount, $pageSize);
    }
}
