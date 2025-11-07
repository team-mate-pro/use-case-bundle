<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data;

use DateTimeInterface;
use TeamMatePro\UseCaseBundle\Serializer\SerializationGroup;
use TeamMatePro\Contracts\Dto\Undefined;
use Symfony\Component\Serializer\Attribute\Groups;

final class FakeObjectToPopulate
{
    #[Groups([SerializationGroup::CSV])]
    public string|Undefined|null $name = null;

    #[Groups([SerializationGroup::CSV])]
    public ?int $age = null;

    public ?int $unmappedField = null;
    public ?DateTimeInterface $date = null;

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function getName(): Undefined|string|null
    {
        return $this->name;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function getUnmappedField(): ?int
    {
        return $this->unmappedField;
    }
}
