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

    // Type casting tests

    public function testCastsIntToString(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = 42;

        $result = $sut->getValueAsString('mixedStringInt');

        $this->assertSame('42', $result);
        $this->assertIsString($result);
    }

    public function testCastsFloatToString(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringFloat = 3.14;

        $result = $sut->getValueAsString('mixedStringFloat');

        $this->assertSame('3.14', $result);
        $this->assertIsString($result);
    }

    public function testCastsBoolToString(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringBool = true;

        $resultTrue = $sut->getValueAsString('mixedStringBool');
        $this->assertSame('1', $resultTrue);

        $sut->mixedStringBool = false;
        $resultFalse = $sut->getValueAsString('mixedStringBool');
        $this->assertSame('0', $resultFalse);
    }

    public function testCastsStringToInt(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = '123';

        $result = $sut->getValueAsInt('mixedStringInt');

        $this->assertSame(123, $result);
        $this->assertIsInt($result);
    }

    public function testCastsFloatToInt(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedIntFloat = 42.7;

        $result = $sut->getValueAsInt('mixedIntFloat');

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function testCastsBoolToInt(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedIntBool = true;

        $resultTrue = $sut->getValueAsInt('mixedIntBool');
        $this->assertSame(1, $resultTrue);

        $sut->mixedIntBool = false;
        $resultFalse = $sut->getValueAsInt('mixedIntBool');
        $this->assertSame(0, $resultFalse);
    }

    public function testCastsStringToFloat(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringFloat = '3.14159';

        $result = $sut->getValueAsFloat('mixedStringFloat');

        $this->assertSame(3.14159, $result);
        $this->assertIsFloat($result);
    }

    public function testCastsIntToFloat(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedIntFloat = 42;

        $result = $sut->getValueAsFloat('mixedIntFloat');

        $this->assertSame(42.0, $result);
        $this->assertIsFloat($result);
    }

    public function testCastsStringToBool(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $sut->mixedStringBool = '1';
        $this->assertTrue($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = 'true';
        $this->assertTrue($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = 'yes';
        $this->assertTrue($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = 'on';
        $this->assertTrue($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = '0';
        $this->assertFalse($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = 'false';
        $this->assertFalse($sut->getValueAsBool('mixedStringBool'));

        $sut->mixedStringBool = 'no';
        $this->assertFalse($sut->getValueAsBool('mixedStringBool'));
    }

    public function testCastsIntToBool(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $sut->mixedIntBool = 1;
        $this->assertTrue($sut->getValueAsBool('mixedIntBool'));

        $sut->mixedIntBool = 42;
        $this->assertTrue($sut->getValueAsBool('mixedIntBool'));

        $sut->mixedIntBool = 0;
        $this->assertFalse($sut->getValueAsBool('mixedIntBool'));

        $sut->mixedIntBool = -1;
        $this->assertTrue($sut->getValueAsBool('mixedIntBool'));
    }

    public function testCastsFloatToBool(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedIntFloat = 1.5;

        $result = $sut->getValueAsBool('mixedIntFloat');

        $this->assertTrue($result);

        $sut->mixedIntFloat = 0.0;
        $this->assertFalse($sut->getValueAsBool('mixedIntFloat'));
    }

    public function testDoesNotCastWhenTypeAlreadyMatches(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = 'already_a_string';

        $result = $sut->getValueAsString('mixedStringInt');

        $this->assertSame('already_a_string', $result);
        $this->assertIsString($result);
    }

    public function testCastingWithNullableReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = 42;

        $result = $sut->getValueAsStringOrNull('mixedStringInt');

        $this->assertSame('42', $result);
        $this->assertIsString($result);
    }

    public function testReturnsOriginalValueWhenCastingNotPossible(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = 'not_numeric';

        // Trying to cast non-numeric string to int should return original value
        $result = $sut->getValuePublic('mixedStringInt');

        $this->assertSame('not_numeric', $result);
        $this->assertIsString($result);
    }

    public function testCastsNumericStringToInt(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = '999';

        $result = $sut->getValueAsInt('mixedStringInt');

        $this->assertSame(999, $result);
    }

    public function testCastsNumericStringToFloat(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringFloat = '123.456';

        $result = $sut->getValueAsFloat('mixedStringFloat');

        $this->assertSame(123.456, $result);
    }

    public function testCastingPreservesOriginalWhenNoCallerReturnType(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringInt = 42;

        // getValueWithNoReturnType has no return type (mixed)
        $result = $sut->getValueWithNoReturnType('mixedStringInt');

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function testCastingHandlesZeroValues(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $sut->mixedStringInt = 0;
        $this->assertSame('0', $sut->getValueAsString('mixedStringInt'));

        $sut->mixedStringInt = '0';
        $this->assertSame(0, $sut->getValueAsInt('mixedStringInt'));
    }

    public function testCastingHandlesNegativeNumbers(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $sut->mixedStringInt = -42;
        $this->assertSame('-42', $sut->getValueAsString('mixedStringInt'));

        $sut->mixedStringInt = '-42';
        $this->assertSame(-42, $sut->getValueAsInt('mixedStringInt'));
    }

    public function testCastingHandlesFloatStrings(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());
        $sut->mixedStringFloat = '99.99';

        $resultFloat = $sut->getValueAsFloat('mixedStringFloat');
        $this->assertSame(99.99, $resultFloat);
        $this->assertIsFloat($resultFloat);
    }
}
