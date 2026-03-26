<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface IsPdfRequest
{
    public function isPdfRequest(HeadersAwareInterface $request): bool;
}
