<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use TeamMatePro\Contracts\Collection\ResultType;

interface HttpStatusCodeResolverInterface
{
    public function resolve(ResultType $type): int;
}
