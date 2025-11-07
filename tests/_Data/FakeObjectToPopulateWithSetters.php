<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data;

use TeamMatePro\Contracts\Dto\Undefined;

final class FakeObjectToPopulateWithSetters
{
    private string|Undefined|null $name = null;

    private ?int $age = null;

    public function getName(): Undefined|string|null
    {
        return $this->name;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setName(Undefined|string|null $name): FakeObjectToPopulateWithSetters
    {
        $this->name = $name . '_test';
        return $this;
    }

    public function setAge(?int $age): FakeObjectToPopulateWithSetters
    {
        $this->age = $age + 1;
        return $this;
    }
}
