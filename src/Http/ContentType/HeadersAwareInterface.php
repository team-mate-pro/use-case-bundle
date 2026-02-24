<?php

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface HeadersAwareInterface
{
    public function getHeader(string $name): ?string;
}