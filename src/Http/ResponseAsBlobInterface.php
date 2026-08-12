<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use TeamMatePro\Contracts\Collection\Result;
use Symfony\Component\HttpFoundation\Response;

interface ResponseAsBlobInterface
{
    /**
     * @template TResult of array<int|string, mixed>|object
     * @param Result<TResult> $result
     */
    public function createBlobResponse(Result $result): Response;
}
