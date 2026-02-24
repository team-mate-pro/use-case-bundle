<?php

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface isPdfRequest
{
    public function isPdfRequest(HeadersAwareInterface $request): bool;
}
