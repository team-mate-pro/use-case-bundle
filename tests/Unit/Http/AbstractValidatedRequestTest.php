<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\UseCaseBundle\Http\Exception\HttpMalformedRequestException;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeDependencies;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeTestingRequest;
use TeamMatePro\Contracts\Dto\Undefined;

#[CoversClass(AbstractValidatedRequest::class)]
final class AbstractValidatedRequestTest extends TestCase
{
    public function testUndefinedIsAutomaticallySet(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $this->assertInstanceOf(Undefined::class, $sut->undefined);
    }

    public function testReturnsUserIdMagically(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $this->assertSame($sut->userId, $sut->getUserId());
    }

    public function testReturnsIsGrantedMagically(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $this->assertTrue($sut->isGranted('ROLE_USER'));
    }

    public function testGetMimeType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $this->assertSame('application/json', $sut->getMimeType());
    }

    public function testGetValueReturnsValueWhenPropertyIsSet(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $result = $sut->getValuePublic('setProperty');
        $this->assertSame('valid_value', $result);
    }

    public function testGetValueThrowsExceptionWhenPropertyDoesNotExist(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $this->expectException(HttpMalformedRequestException::class);
        $this->expectExceptionMessage('Property "nonExistentProperty" does not exist');

        $sut->getValuePublic('nonExistentProperty');
    }

    public function testGetValueThrowsExceptionWhenPropertyIsNullAndCallerDoesNotAllowNull(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $this->expectException(HttpMalformedRequestException::class);
        $this->expectExceptionMessage('Property "nullableProperty" is null');

        $sut->getValueReturningString('nullableProperty');
    }

    public function testGetValueThrowsExceptionWhenPropertyIsUndefinedAndCallerDoesNotAllowUndefined(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        // The undefinedProperty should be automatically set to Undefined in populate()

        $this->expectException(HttpMalformedRequestException::class);
        $this->expectExceptionMessage('Property "undefinedProperty" is undefined');

        $sut->getValueReturningString('undefinedProperty');
    }

    public function testGetValueReturnsValueWhenPropertyIsValidString(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = 'test_value';

        $result = $sut->getValuePublic('nullableProperty');

        $this->assertSame('test_value', $result);
    }

    public function testGetValueReturnsValueWhenPropertyIsValidInteger(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->undefined = 42;

        $result = $sut->getValuePublic('undefined');

        $this->assertSame(42, $result);
    }

    public function testGetValueThrowsExceptionWithCorrectMessageForNonExistentProperty(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        try {
            $sut->getValuePublic('invalidProp');
            $this->fail('Expected HttpMalformedRequestException was not thrown');
        } catch (HttpMalformedRequestException $e) {
            $this->assertStringContainsString('invalidProp', $e->getMessage());
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    public function testGetValueReturnsNullWhenPropertyAndCallerAllowNull(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $result = $sut->getValueReturningNullable('nullableProperty');

        $this->assertNull($result);
    }

    public function testGetValueReturnsUndefinedWhenPropertyAndCallerAllowUndefined(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        // undefinedProperty is automatically set to Undefined in populate()

        $result = $sut->getValueReturningUndefinable('undefinedProperty');

        $this->assertInstanceOf(Undefined::class, $result);
    }

    public function testGetValueReturnsNullWhenCallerHasMixedReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $result = $sut->getValuePublic('nullableProperty');

        $this->assertNull($result);
    }

    public function testGetValueReturnsUndefinedWhenCallerHasMixedReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $result = $sut->getValuePublic('undefinedProperty');

        $this->assertInstanceOf(Undefined::class, $result);
    }

    public function testGetValueReturnsNullWhenCallerHasNoReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $result = $sut->getValueWithNoReturnType('nullableProperty');

        $this->assertNull($result);
    }

    public function testGetValueReturnsUndefinedWhenCallerHasNoReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $result = $sut->getValueWithNoReturnType('undefinedProperty');

        $this->assertInstanceOf(Undefined::class, $result);
    }

    public function testGetValueReturnsNullOrUndefinedWhenCallerAllowsBoth(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $resultNull = $sut->getValueReturningNullableOrUndefined('nullableProperty');
        $this->assertNull($resultNull);

        $resultUndefined = $sut->getValueReturningNullableOrUndefined('undefinedProperty');
        $this->assertInstanceOf(Undefined::class, $resultUndefined);
    }

    public function testGetValueThrowsExceptionWhenPropertyAllowsNullButCallerDoesNot(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->nullableProperty = null;

        $this->expectException(HttpMalformedRequestException::class);
        $this->expectExceptionMessage('Property "nullableProperty" is null');

        // getValueReturningString has return type string (no null)
        $sut->getValueReturningString('nullableProperty');
    }

    public function testGetValueThrowsExceptionWhenPropertyAllowsUndefinedButCallerDoesNot(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $this->expectException(HttpMalformedRequestException::class);
        $this->expectExceptionMessage('Property "undefinedProperty" is undefined');

        // getValueReturningNullable has return type string|null (no Undefined)
        $sut->getValueReturningNullable('undefinedProperty');
    }
}
