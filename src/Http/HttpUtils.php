<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use Symfony\Component\HttpFoundation\Request;

final class HttpUtils
{
    public static function isJson(Request $request): bool
    {
        $contentType = $request->headers->get('Content-Type');

        return $contentType === 'application/json';
    }
}
