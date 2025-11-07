<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\RestApi;

use TeamMatePro\Contracts\Collection\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use function array_merge;
use function is_string;
use function sprintf;

abstract class AbstractRestApiController extends AbstractController
{
    public function response(
        Result $result,
        array|string|null $serializationGroups = null,
        array  $headers = []
    ): JsonResponse
    {
        $context = is_string(
            $serializationGroups
        ) ? ['groups' => [$serializationGroups]] : ['groups' => $serializationGroups];

        return $this->json(
            data: ResultRestRenderer::render($result),
            status: ResultRestRenderer::getHttpStatusCode($result->getType()),
            headers: $headers,
            context: $context,
        );
    }

    /**
     * @param string[]|string|null $serializationGroups
     * @param int[]|int|null $cacheInSeconds - if array passed, second param will cache in the browser
     */
    public function responseWithCache(
        Result         $result,
        int|array|null $cacheInSeconds = 3600,
        array|string|null $serializationGroups = null,
        array          $headers = []
    ): JsonResponse
    {
        $sMaxAge = 0;
        $maxAge = 0;

        if (is_int($cacheInSeconds)) {
            $sMaxAge = $cacheInSeconds;
        } elseif (is_array($cacheInSeconds)) {
            $sMaxAge = $cacheInSeconds[0] ?? 0;
            $maxAge = $cacheInSeconds[1] ?? 0;
        }

        $cacheHeader = ($sMaxAge > 0 || $maxAge > 0)
            ? ['Cache-Control' => sprintf('public, s-maxage=%d, max-age=%d', $sMaxAge, $maxAge)]
            : [];

        return $this->response($result, $serializationGroups, array_merge($cacheHeader, $headers));
    }

}
