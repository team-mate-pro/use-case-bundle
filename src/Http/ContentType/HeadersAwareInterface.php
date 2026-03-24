<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

interface HeadersAwareInterface
{
    public function getHeader(string $name): ?string;
}
