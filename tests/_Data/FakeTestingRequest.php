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
}
