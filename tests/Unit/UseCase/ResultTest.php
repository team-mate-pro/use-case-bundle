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

        yield 'has object content' => [
            Result::create(ResultType::SUCCESS)->withItem(new stdClass()),
            true
        ];

        yield 'has array item content' => [
            Result::create(ResultType::SUCCESS)->withItem(['key' => 'value']),
            true
        ];

        yield 'has array collection content' => [
            Result::create(ResultType::SUCCESS)->withCollection([1, 2, 3]),
            true
        ];

        yield 'has empty array collection' => [
            Result::create(ResultType::SUCCESS)->withCollection([]),
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

    public function testWithItemSetsData(): void
    {
        $data = ['key' => 'value'];
        $result = Result::create()->withItem($data);

        $this->assertSame($data, $result->getResult());
        $this->assertSame('item', $result->getItemType());
    }

    public function testWithCollectionSetsData(): void
    {
        $data = [['a'], ['b']];
        $result = Result::create()->withCollection($data);

        $this->assertSame($data, $result->getResult());
        $this->assertSame('collection', $result->getItemType());
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
        $payload = ['key' => 'value'];
        $result = Result::create(ResultType::SUCCESS, 'Success')
            ->withItem($payload)
            ->withMeta('m', 'v')
            ->withErrorCode('CODE');

        $this->assertSame($payload, $result->getResult());
        $this->assertSame(['m' => 'v'], $result->getMeta());
        $this->assertSame('CODE', $result->getErrorCode());
    }

    public function testIterableWithObject(): void
    {
        $obj = new stdClass();
        $result = Result::create()->withItem($obj);

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
        // 2xx Success
        yield 'SUCCESS' => [ResultType::SUCCESS];
        yield 'SUCCESS_CREATED' => [ResultType::SUCCESS_CREATED];
        yield 'ACCEPTED' => [ResultType::ACCEPTED];
        yield 'SUCCESS_NO_CONTENT' => [ResultType::SUCCESS_NO_CONTENT];

        // 4xx Client Errors
        yield 'FAILURE' => [ResultType::FAILURE];
        yield 'UNAUTHORIZED' => [ResultType::UNAUTHORIZED];
        yield 'FORBIDDEN' => [ResultType::FORBIDDEN];
        yield 'NOT_FOUND' => [ResultType::NOT_FOUND];
        yield 'DUPLICATED' => [ResultType::DUPLICATED];
        yield 'GONE' => [ResultType::GONE];
        yield 'EXPIRED' => [ResultType::EXPIRED];
        yield 'PRECONDITION_FAILED' => [ResultType::PRECONDITION_FAILED];
        yield 'UNPROCESSABLE' => [ResultType::UNPROCESSABLE];
        yield 'LOCKED' => [ResultType::LOCKED];
        yield 'TOO_MANY_REQUESTS' => [ResultType::TOO_MANY_REQUESTS];

        // 5xx Server Errors
        yield 'SERVICE_UNAVAILABLE' => [ResultType::SERVICE_UNAVAILABLE];
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
        self::assertIsArray($meta['nested']['level1']);
        $this->assertSame('value', $meta['nested']['level1']['level2']);
        $this->assertSame([1, 2, 3], $meta['array']);
        $this->assertInstanceOf(stdClass::class, $meta['object']);
    }
}
