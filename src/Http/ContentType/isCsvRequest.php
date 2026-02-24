<?php

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface isCsvRequest
{
    public function isCsvRequest(HeadersAwareInterface $request): bool;
}