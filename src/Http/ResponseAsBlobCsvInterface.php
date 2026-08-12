<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use TeamMatePro\Contracts\Collection\Result;
use Symfony\Component\HttpFoundation\Response;

interface ResponseAsBlobCsvInterface
{
    /**
     * @template TResult of array<int|string, mixed>|object
     * @param Result<TResult> $result
     * @param list<string>|string|null $serializationGroups
     */
    public function createCsvResponse(Result $result, bool $base64 = true, string $delimiter = ';', array|string|null $serializationGroups = null): Response;
}
