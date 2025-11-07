<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use TeamMatePro\Contracts\Collection\Result;
use Symfony\Component\HttpFoundation\Response;

interface ResponseAsBlobInterface
{
    public function createBlobResponse(Result $result): Response;
}