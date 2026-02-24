<?php

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface IsPdfRequest
{
    public function isPdfRequest(HeadersAwareInterface $request): bool;
}
