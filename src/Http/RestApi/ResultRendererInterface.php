<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use TeamMatePro\Contracts\Collection\Result;

interface ResultRendererInterface
{
    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return array<string, mixed>
     */
    public function render(Result $result): array;

    /**
     * Minimal response envelope used before a `Result` exists
     * (e.g. in validation / authorization exception listeners).
     *
     * @param array<string, mixed> $extra
     * @return array{message: ?string, code: int, errorCode: ?string, ...}
     */
    public function renderMandatory(
        ?string $message = null,
        int $code = 200,
        ?string $errorCode = null,
        array $extra = []
    ): array;
}
