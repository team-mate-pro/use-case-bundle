<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Utils;

use Stringable;

final readonly class StringAbleObject implements Stringable
{
    public function __construct(private Stringable|string $stringable)
    {
    }

    public function __toString(): string
    {
        return (string) $this->stringable;
    }
}