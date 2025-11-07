<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

interface ItemAwareControllerInterface
{
    /**
     * @return string[]
     */
    public static function getItemSerializationGroups(): array;
}
