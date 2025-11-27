<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Utils;

use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stringable;
use TeamMatePro\UseCaseBundle\Utils\AbstractPlainDto;

#[CoversClass(AbstractPlainDto::class)]
final class AbstractPlainDtoTest extends TestCase
{
    public function testSerializesPublicProperties(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $name = 'John';
            public int $age = 30;
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['name' => 'John', 'age' => 30], $result);
    }

    public function testIgnoresPrivateAndProtectedProperties(): void
    {
        $dto = new class ('visible') extends AbstractPlainDto {
            protected string $protected = 'hidden';

            public function __construct(
                public string $public,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['public' => 'visible'], $result);
    }

    public function testIgnoresUninitializedProperties(): void
    {
        $dto = new class ('value') extends AbstractPlainDto {
            /** @phpstan-ignore-next-line */
            public string $uninitialized;

            public function __construct(
                public string $initialized,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['initialized' => 'value'], $result);
    }

    public function testSerializesStringBackedEnum(): void
    {
        $dto = new class (StringBackedTestEnum::ACTIVE) extends AbstractPlainDto {
            public function __construct(
                public StringBackedTestEnum $status,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['status' => 'active'], $result);
    }

    public function testSerializesIntBackedEnum(): void
    {
        $dto = new class (IntBackedTestEnum::HIGH) extends AbstractPlainDto {
            public function __construct(
                public IntBackedTestEnum $priority,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['priority' => 3], $result);
    }

    public function testSerializesStringableObject(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable value';
            }
        };

        $dto = new class ($stringable) extends AbstractPlainDto {
            public function __construct(
                public Stringable $content,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['content' => 'stringable value'], $result);
    }

    public function testSerializesJsonSerializableObject(): void
    {
        $jsonSerializable = new class implements JsonSerializable {
            /**
             * @return array<string, mixed>
             */
            public function jsonSerialize(): array
            {
                return ['nested' => 'data', 'count' => 42];
            }
        };

        $dto = new class ($jsonSerializable) extends AbstractPlainDto {
            public function __construct(
                public JsonSerializable $data,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['data' => ['nested' => 'data', 'count' => 42]], $result);
    }

    public function testSerializesNestedDto(): void
    {
        $innerDto = new class extends AbstractPlainDto {
            public string $inner = 'value';
        };

        $dto = new class ($innerDto) extends AbstractPlainDto {
            public function __construct(
                public AbstractPlainDto $nested,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nested' => ['inner' => 'value']], $result);
    }

    public function testSerializesArrayOfPrimitives(): void
    {
        $dto = new class extends AbstractPlainDto {
            /** @var string[] */
            public array $tags = ['php', 'symfony'];
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['tags' => ['php', 'symfony']], $result);
    }

    public function testSerializesArrayOfEnums(): void
    {
        $dto = new class ([StringBackedTestEnum::ACTIVE, StringBackedTestEnum::INACTIVE]) extends AbstractPlainDto {
            /**
             * @param StringBackedTestEnum[] $statuses
             */
            public function __construct(
                public array $statuses,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['statuses' => ['active', 'inactive']], $result);
    }

    public function testSerializesArrayOfJsonSerializableObjects(): void
    {
        $item1 = new class implements JsonSerializable {
            /**
             * @return array<string, int>
             */
            public function jsonSerialize(): array
            {
                return ['id' => 1];
            }
        };
        $item2 = new class implements JsonSerializable {
            /**
             * @return array<string, int>
             */
            public function jsonSerialize(): array
            {
                return ['id' => 2];
            }
        };

        $dto = new class ([$item1, $item2]) extends AbstractPlainDto {
            /**
             * @param JsonSerializable[] $items
             */
            public function __construct(
                public array $items,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['items' => [['id' => 1], ['id' => 2]]], $result);
    }

    public function testSerializesNullablePropertyWithValue(): void
    {
        $dto = new class extends AbstractPlainDto {
            public ?string $nullable = 'has value';
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nullable' => 'has value'], $result);
    }

    public function testSerializesNullablePropertyWithNull(): void
    {
        $dto = new class extends AbstractPlainDto {
            public ?string $nullable = null;
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nullable' => null], $result);
    }

    public function testSerializesComplexDto(): void
    {
        $innerDto = new class extends AbstractPlainDto {
            public string $name = 'inner';
        };

        $dto = new class ('Test', 5, StringBackedTestEnum::ACTIVE, $innerDto, ['a', 'b']) extends AbstractPlainDto {
            /**
             * @param string[] $tags
             */
            public function __construct(
                public string $title,
                public int $count,
                public StringBackedTestEnum $status,
                public AbstractPlainDto $child,
                public array $tags,
            ) {
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame([
            'title' => 'Test',
            'count' => 5,
            'status' => 'active',
            'child' => ['name' => 'inner'],
            'tags' => ['a', 'b'],
        ], $result);
    }

    public function testJsonEncodeProducesValidJson(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $name = 'Test';
            public int $value = 42;
        };

        $json = json_encode($dto);

        $this->assertSame('{"name":"Test","value":42}', $json);
    }

    public function testEmptyDtoReturnsEmptyArray(): void
    {
        $dto = new class extends AbstractPlainDto {
        };

        $result = $dto->jsonSerialize();

        $this->assertSame([], $result);
    }

    public function testPreservesCamelCasePropertyNames(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $someField = 'value';
            public string $anotherLongFieldName = 'another';
        };

        $result = $dto->jsonSerialize();

        $this->assertArrayHasKey('someField', $result);
        $this->assertArrayHasKey('anotherLongFieldName', $result);
    }
}

enum StringBackedTestEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum IntBackedTestEnum: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}
