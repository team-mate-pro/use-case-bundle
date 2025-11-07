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
}
