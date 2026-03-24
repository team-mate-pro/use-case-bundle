<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface IsCsvRequest
{
    public function isCsvRequest(HeadersAwareInterface $request): bool;
}
