<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Utils;

use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stringable;
use TeamMatePro\Contracts\Dto\Undefined;
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

    public function testSerializesPublicMethods(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getName(): string
            {
                return 'John';
            }

            public function getAge(): int
            {
                return 30;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['name' => 'John', 'age' => 30], $result);
    }

    public function testMethodTakesPrecedenceOverProperty(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $title = 'from property';

            public function getTitle(): string
            {
                return 'from method';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['title' => 'from method'], $result);
    }

    public function testPropertiesAndMethodsCombined(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $propertyOnly = 'property value';
            public string $overridden = 'will be overridden';

            public function getOverridden(): string
            {
                return 'method wins';
            }

            public function getMethodOnly(): string
            {
                return 'method value';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame([
            'propertyOnly' => 'property value',
            'overridden' => 'method wins',
            'methodOnly' => 'method value',
        ], $result);
    }

    public function testIgnoresPrivateAndProtectedMethods(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getPublic(): string
            {
                return 'visible';
            }

            protected function getProtected(): string
            {
                return 'hidden';
            }

            /** @phpstan-ignore method.unused */
            private function getPrivate(): string
            {
                return 'hidden';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['public' => 'visible'], $result);
    }

    public function testIgnoresMethodsWithRequiredParameters(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getInitialized(): string
            {
                return 'value';
            }

            public function getWithParam(string $param): string
            {
                return $param;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['initialized' => 'value'], $result);
    }

    public function testSerializesStringBackedEnum(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getStatus(): StringBackedTestEnum
            {
                return StringBackedTestEnum::ACTIVE;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['status' => 'active'], $result);
    }

    public function testSerializesIntBackedEnum(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getPriority(): IntBackedTestEnum
            {
                return IntBackedTestEnum::HIGH;
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
                private Stringable $stringable,
            ) {
            }

            public function getContent(): Stringable
            {
                return $this->stringable;
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
                private JsonSerializable $jsonSerializable,
            ) {
            }

            public function getData(): JsonSerializable
            {
                return $this->jsonSerializable;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['data' => ['nested' => 'data', 'count' => 42]], $result);
    }

    public function testSerializesNestedDto(): void
    {
        $innerDto = new class extends AbstractPlainDto {
            public function getInner(): string
            {
                return 'value';
            }
        };

        $dto = new class ($innerDto) extends AbstractPlainDto {
            public function __construct(
                private AbstractPlainDto $innerDto,
            ) {
            }

            public function getNested(): AbstractPlainDto
            {
                return $this->innerDto;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nested' => ['inner' => 'value']], $result);
    }

    public function testSerializesArrayOfPrimitives(): void
    {
        $dto = new class extends AbstractPlainDto {
            /**
             * @return string[]
             */
            public function getTags(): array
            {
                return ['php', 'symfony'];
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['tags' => ['php', 'symfony']], $result);
    }

    public function testSerializesArrayOfEnums(): void
    {
        $dto = new class extends AbstractPlainDto {
            /**
             * @return StringBackedTestEnum[]
             */
            public function getStatuses(): array
            {
                return [StringBackedTestEnum::ACTIVE, StringBackedTestEnum::INACTIVE];
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
                private array $items,
            ) {
            }

            /**
             * @return JsonSerializable[]
             */
            public function getItems(): array
            {
                return $this->items;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['items' => [['id' => 1], ['id' => 2]]], $result);
    }

    public function testSerializesNullableMethodWithValue(): void
    {
        $dto = new class extends AbstractPlainDto {
            /** @phpstan-ignore return.unusedType */
            public function getNullable(): ?string
            {
                return 'has value';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nullable' => 'has value'], $result);
    }

    public function testSerializesNullableMethodWithNull(): void
    {
        $dto = new class extends AbstractPlainDto {
            /** @phpstan-ignore return.unusedType */
            public function getNullable(): ?string
            {
                return null;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['nullable' => null], $result);
    }

    public function testSerializesComplexDto(): void
    {
        $innerDto = new class extends AbstractPlainDto {
            public function getName(): string
            {
                return 'inner';
            }
        };

        $dto = new class ('Test', 5, StringBackedTestEnum::ACTIVE, $innerDto, ['a', 'b']) extends AbstractPlainDto {
            /**
             * @param string[] $tags
             */
            public function __construct(
                private string $title,
                private int $count,
                private StringBackedTestEnum $status,
                private AbstractPlainDto $child,
                private array $tags,
            ) {
            }

            public function getTitle(): string
            {
                return $this->title;
            }

            public function getCount(): int
            {
                return $this->count;
            }

            public function getStatus(): StringBackedTestEnum
            {
                return $this->status;
            }

            public function getChild(): AbstractPlainDto
            {
                return $this->child;
            }

            /**
             * @return string[]
             */
            public function getTags(): array
            {
                return $this->tags;
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
            public function getName(): string
            {
                return 'Test';
            }

            public function getValue(): int
            {
                return 42;
            }
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

    public function testPreservesCamelCaseMethodNames(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getSomeField(): string
            {
                return 'value';
            }

            public function getAnotherLongFieldName(): string
            {
                return 'another';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertArrayHasKey('someField', $result);
        $this->assertArrayHasKey('anotherLongFieldName', $result);
    }

    public function testMethodsWithoutGetPrefixUseMethodNameAsKey(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function title(): string
            {
                return 'my title';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['title' => 'my title'], $result);
    }

    public function testIsMethodPrefixIsStripped(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function isActive(): bool
            {
                return true;
            }

            public function isEnabled(): bool
            {
                return false;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['active' => true, 'enabled' => false], $result);
    }

    public function testHasMethodPrefixIsStripped(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function hasItems(): bool
            {
                return true;
            }

            public function hasPermission(): bool
            {
                return false;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['items' => true, 'permission' => false], $result);
    }

    public function testMethodsWithOptionalParametersAreIncluded(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getName(string $default = 'default'): string
            {
                return $default;
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['name' => 'default'], $result);
    }

    public function testSkipsUndefinedPropertyValues(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $name = 'John';
            public string|Undefined $email;

            public function __construct()
            {
                $this->email = new Undefined();
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['name' => 'John'], $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function testSkipsUndefinedMethodReturnValues(): void
    {
        $dto = new class extends AbstractPlainDto {
            public function getName(): string
            {
                return 'John';
            }

            /** @phpstan-ignore return.unusedType */
            public function getEmail(): string|Undefined
            {
                return new Undefined();
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['name' => 'John'], $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function testSkipsUndefinedInMixedScenario(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $propertySet = 'value';
            public string|Undefined $propertyUndefined;

            public function __construct()
            {
                $this->propertyUndefined = new Undefined();
            }

            public function getMethodSet(): string
            {
                return 'method value';
            }

            /** @phpstan-ignore return.unusedType */
            public function getMethodUndefined(): string|Undefined
            {
                return new Undefined();
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame([
            'propertySet' => 'value',
            'methodSet' => 'method value',
        ], $result);
        $this->assertArrayNotHasKey('propertyUndefined', $result);
        $this->assertArrayNotHasKey('methodUndefined', $result);
    }

    public function testMethodOverridesPropertyWithUndefined(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string $title = 'from property';

            /** @phpstan-ignore return.unusedType */
            public function getTitle(): string|Undefined
            {
                return new Undefined();
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertArrayNotHasKey('title', $result);
    }

    public function testMethodOverridesUndefinedPropertyWithValue(): void
    {
        $dto = new class extends AbstractPlainDto {
            public string|Undefined $title;

            public function __construct()
            {
                $this->title = new Undefined();
            }

            public function getTitle(): string
            {
                return 'from method';
            }
        };

        $result = $dto->jsonSerialize();

        $this->assertSame(['title' => 'from method'], $result);
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
