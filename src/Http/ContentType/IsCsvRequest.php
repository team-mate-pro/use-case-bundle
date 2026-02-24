<?php

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface IsCsvRequest
{
    public function isCsvRequest(HeadersAwareInterface $request): bool;
}