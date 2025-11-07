<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

trait UseSerializerTrait
{
    protected function getPopulateStrategy(): string
    {
        return AbstractValidatedRequest::SERIALIZER_STRATEGY;
    }
}
