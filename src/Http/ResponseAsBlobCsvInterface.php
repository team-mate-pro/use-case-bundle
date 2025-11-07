<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use TeamMatePro\Contracts\Collection\Result;
use Symfony\Component\HttpFoundation\Response;

interface ResponseAsBlobCsvInterface
{
    public function createCsvResponse(Result $result, bool $base64 = true, string $delimiter = ';', array|string|null $serializationGroups = null): Response;
}