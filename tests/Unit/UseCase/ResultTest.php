<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\UseCase;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Tests\_Data\MotherObject\ResultMother;
use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;
use stdClass;

#[CoversClass(Result::class)]
final class ResultTest extends TestCase
{
    #[DataProvider('hasContentProvider')]
    public function testHasContent(Result $sut, bool $expected): void
    {
        $this->assertSame($expected, $sut->hasContent());
    }

    public static function hasContentProvider(): Generator
    {
        yield 'has no content set' => [
            Result::create(ResultType::SUCCESS),
            false
        ];

        yield 'has null' => [
            Result::create(ResultType::SUCCESS)->with(null),
            false
        ];

        yield 'has string content' => [
            Result::create(ResultType::SUCCESS)->with('data'),
            true
        ];

        yield 'has object content' => [
            Result::create(ResultType::SUCCESS)->with(new stdClass()),
            true
        ];

        yield 'has array content' => [
            Result::create(ResultType::SUCCESS)->with([1, 2, 3]),
            true
        ];

        yield 'has zero as content' => [
            Result::create(ResultType::SUCCESS)->with(0),
            true
        ];

        yield 'has false as content' => [
            Result::create(ResultType::SUCCESS)->with(false),
            true
        ];

        yield 'has empty string as content' => [
            Result::create(ResultType::SUCCESS)->with(''),
            true
        ];
    }

    public function testCreateWithDefaultType(): void
    {
        $result = Result::create();

        $this->assertSame(ResultType::SUCCESS, $result->getType());
        $this->assertNull($result->getMessage());
    }

    public function testCreateWithMessage(): void
    {
        $result = Result::create(ResultType::SUCCESS, 'Operation completed');

        $this->assertSame('Operation completed', $result->getMessage());
    }

    public function testWithSetsData(): void
    {
        $data = ['key' => 'value'];
        $result = Result::create()->with($data);

        $this->assertSame($data, $result->getResult());
    }

    public function testWithMetaAddsMetadata(): void
    {
        $result = Result::create()
            ->withMeta('count', 10)
            ->withMeta('page', 2);

        $meta = $result->getMeta();
        $this->assertSame(10, $meta['count']);
        $this->assertSame(2, $meta['page']);
        $this->assertCount(2, $meta);
    }

    public function testWithErrorCodeSetsErrorCode(): void
    {
        $result = Result::create()->withErrorCode('USER_NOT_FOUND');

        $this->assertSame('USER_NOT_FOUND', $result->getErrorCode());
    }

    public function testWithErrorCodeAcceptsInteger(): void
    {
        $result = Result::create()->withErrorCode(404);

        $this->assertSame('404', $result->getErrorCode());
    }

    public function testGetErrorCodeReturnsNullByDefault(): void
    {
        $result = Result::create();

        $this->assertNull($result->getErrorCode());
    }

    public function testGetMetaReturnsEmptyArrayByDefault(): void
    {
        $result = Result::create();

        $this->assertSame([], $result->getMeta());
    }

    public function testGetTypeReturnsCorrectType(): void
    {
        $result = Result::create(ResultType::FAILURE);

        $this->assertSame(ResultType::FAILURE, $result->getType());
    }

    public function testChainableInterface(): void
    {
        $result = Result::create(ResultType::SUCCESS, 'Success')
            ->with('data')
            ->withMeta('key', 'value')
            ->withErrorCode('CODE');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame('data', $result->getResult());
        $this->assertSame(['key' => 'value'], $result->getMeta());
        $this->assertSame('CODE', $result->getErrorCode());
    }

    public function testResultIsIterableWithArrayIterator(): void
    {
        // When data is set, Result wraps non-Traversable items in ArrayIterator
        $result = Result::create()->with('single');

        $this->assertInstanceOf(\Traversable::class, $result->getIterator());

        $items = [];
        foreach ($result as $item) {
            $items[] = $item;
        }

        $this->assertSame(['single'], $items);
    }

    public function testIterableWithSingleItem(): void
    {
        $result = Result::create()->with('single');

        $items = iterator_to_array($result);

        $this->assertSame(['single'], $items);
    }

    public function testIterableWithObject(): void
    {
        $obj = new stdClass();
        $result = Result::create()->with($obj);

        $items = iterator_to_array($result);

        $this->assertCount(1, $items);
        $this->assertSame($obj, $items[0]);
    }

    #[DataProvider('resultTypeProvider')]
    public function testAllResultTypes(ResultType $type): void
    {
        $result = Result::create($type);

        $this->assertSame($type, $result->getType());
    }

    public static function resultTypeProvider(): Generator
    {
        yield 'SUCCESS' => [ResultType::SUCCESS];
        yield 'SUCCESS_NO_CONTENT' => [ResultType::SUCCESS_NO_CONTENT];
        yield 'SUCCESS_CREATED' => [ResultType::SUCCESS_CREATED];
        yield 'FAILURE' => [ResultType::FAILURE];
        yield 'ACCEPTED' => [ResultType::ACCEPTED];
        yield 'DUPLICATED' => [ResultType::DUPLICATED];
        yield 'NOT_FOUND' => [ResultType::NOT_FOUND];
        yield 'LOCKED' => [ResultType::LOCKED];
        yield 'GONE' => [ResultType::GONE];
        yield 'EXPIRED' => [ResultType::EXPIRED];
    }

    public function testMotherObjectSuccess(): void
    {
        $result = ResultMother::success('Test message');

        $this->assertSame(ResultType::SUCCESS, $result->getType());
        $this->assertSame('Test message', $result->getMessage());
    }

    public function testMotherObjectFailure(): void
    {
        $result = ResultMother::failure('Error occurred');

        $this->assertSame(ResultType::FAILURE, $result->getType());
        $this->assertSame('Error occurred', $result->getMessage());
    }

    public function testMotherObjectWithData(): void
    {
        $data = ['test' => 'data'];
        $result = ResultMother::successWithData($data);

        $this->assertTrue($result->hasContent());
        $this->assertSame($data, $result->getResult());
    }

    public function testMetadataCanBeOverwritten(): void
    {
        $result = Result::create()
            ->withMeta('key', 'initial')
            ->withMeta('key', 'updated');

        $this->assertSame('updated', $result->getMeta()['key']);
    }

    public function testComplexMetadataStructure(): void
    {
        $result = Result::create()
            ->withMeta('nested', ['level1' => ['level2' => 'value']])
            ->withMeta('array', [1, 2, 3])
            ->withMeta('object', new stdClass());

        $meta = $result->getMeta();
        $this->assertIsArray($meta['nested']);
        $this->assertSame('value', $meta['nested']['level1']['level2']);
        $this->assertSame([1, 2, 3], $meta['array']);
        $this->assertInstanceOf(stdClass::class, $meta['object']);
    }
}