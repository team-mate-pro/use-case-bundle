<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data;

use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\Contracts\Dto\Undefined;

final class FakeTestingRequest extends AbstractValidatedRequest
{
    public Undefined|null|int $undefined;

    public string|null $userId = null;

    public string|null $nullableProperty = null;

    public string $setProperty = 'valid_value';

    public Undefined|string $undefinedProperty;

    // Properties for type casting tests
    public string|int|null $mixedStringInt = null;

    public string|float|null $mixedStringFloat = null;

    public string|bool|null $mixedStringBool = null;

    public int|float|null $mixedIntFloat = null;

    public int|bool|null $mixedIntBool = null;

    public function getValuePublic(string $property): mixed
    {
        return $this->getValue($property);
    }

    public function getValueReturningNullable(string $property): string|null
    {
        return $this->getValue($property);
    }

    public function getValueReturningUndefinable(string $property): Undefined|string
    {
        return $this->getValue($property);
    }

    public function getValueReturningString(string $property): string
    {
        return $this->getValue($property);
    }

    public function getValueWithNoReturnType(string $property)
    {
        return $this->getValue($property);
    }

    public function getValueReturningNullableOrUndefined(string $property): Undefined|string|null
    {
        return $this->getValue($property);
    }

    // Type casting getter methods
    public function getValueAsString(string $property): string
    {
        return $this->getValue($property);
    }

    public function getValueAsInt(string $property): int
    {
        return $this->getValue($property);
    }

    public function getValueAsFloat(string $property): float
    {
        return $this->getValue($property);
    }

    public function getValueAsBool(string $property): bool
    {
        return $this->getValue($property);
    }

    public function getValueAsStringOrNull(string $property): string|null
    {
        return $this->getValue($property);
    }

    public function getValueAsIntOrNull(string $property): int|null
    {
        return $this->getValue($property);
    }

    public function getValueAsFloatOrNull(string $property): float|null
    {
        return $this->getValue($property);
    }

    public function getValueAsBoolOrNull(string $property): bool|null
    {
        return $this->getValue($property);
    }
}
