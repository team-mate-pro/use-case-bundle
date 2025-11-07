<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

interface CollectionAwareControllerInterface
{
    /**
     * @return string[]
     */
    public static function getCollectionSerializationGroups(): array;
}
