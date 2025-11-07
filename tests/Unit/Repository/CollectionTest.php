<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use TeamMatePro\Contracts\Collection\PaginatedCollection;
use TeamMatePro\Contracts\Collection\Pagination;
use TeamMatePro\UseCaseBundle\Tests\_Data\MotherObject\CollectionMother;

#[CoversClass(PaginatedCollection::class)]
final class CollectionTest extends TestCase
{
    public function testConstructorSetsItemsCountAndLimit(): void
    {
        $items = ['item1', 'item2', 'item3'];
        $pagination = new Pagination(0, 5);
        $collection = new PaginatedCollection($items, 10, $pagination);

        $this->assertSame($items, $collection->getItems());
        $this->assertSame(10, $collection->getCount());
        $this->assertSame(5, $collection->getPagination()->getLimit());
    }

    public function testCollectionWithNullLimit(): void
    {
        $items = ['item1'];
        $pagination = new Pagination(0, null);
        $collection = new PaginatedCollection($items, 1, $pagination);

        $this->assertNull($collection->getPagination()->getLimit());
    }

    public function testEmptyCollection(): void
    {
        $collection = CollectionMother::empty();

        $this->assertSame([], $collection->getItems());
        $this->assertSame(0, $collection->getCount());
        $this->assertSame(10, $collection->getPagination()->getLimit());
    }

    public function testCollectionIsIterable(): void
    {
        $items = ['item1', 'item2', 'item3'];
        $collection = new PaginatedCollection($items, 3, null);

        $iteratedItems = [];
        foreach ($collection as $item) {
            $iteratedItems[] = $item;
        }

        $this->assertSame($items, $iteratedItems);
    }

    public function testCollectionWithObjects(): void
    {
        $obj1 = new stdClass();
        $obj1->id = 1;
        $obj2 = new stdClass();
        $obj2->id = 2;

        $items = [$obj1, $obj2];
        $pagination = new Pagination(0, 10);
        $collection = new PaginatedCollection($items, 2, $pagination);

        $this->assertCount(2, $collection->getItems());
        $this->assertSame($obj1, $collection->getItems()[0]);
        $this->assertSame($obj2, $collection->getItems()[1]);
    }

    public function testCollectionCountCanBeLargerThanItemsCount(): void
    {
        // This represents a paginated result where total count is larger than returned items
        $items = ['item1', 'item2'];
        $pagination = new Pagination(0, 2);
        $collection = new PaginatedCollection($items, 100, $pagination);

        $this->assertCount(2, $collection->getItems());
        $this->assertSame(100, $collection->getCount());
    }

    public function testPartialResultsCollection(): void
    {
        $collection = CollectionMother::withPartialResults(10, 100, 10);

        $this->assertCount(10, $collection->getItems());
        $this->assertSame(100, $collection->getCount());
        $this->assertSame(10, $collection->getPagination()->getLimit());
    }

    public function testSinglePageCollection(): void
    {
        $collection = CollectionMother::singlePage(5);

        $this->assertCount(5, $collection->getItems());
        $this->assertSame(5, $collection->getCount());
        $this->assertSame(5, $collection->getPagination()->getLimit());
    }

    public function testCollectionWithUsers(): void
    {
        $collection = CollectionMother::withUsers(3);

        $this->assertCount(3, $collection->getItems());
        $items = $collection->getItems();
        $this->assertSame(1, $items[0]->id);
        $this->assertSame('User 1', $items[0]->name);
        $this->assertSame('user1@example.com', $items[0]->email);
    }

    public function testIteratorReturnsArrayIterator(): void
    {
        $items = [1, 2, 3];
        $collection = new PaginatedCollection($items, 3, null);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
    }

    public function testCollectionWithMixedTypes(): void
    {
        $items = [1, 'string', new stdClass(), ['array'], true, null];
        $collection = new PaginatedCollection($items, count($items), null);

        $this->assertCount(6, $collection->getItems());
        $this->assertSame(1, $collection->getItems()[0]);
        $this->assertSame('string', $collection->getItems()[1]);
        $this->assertTrue($collection->getItems()[4]);
        $this->assertNull($collection->getItems()[5]);
    }
}
