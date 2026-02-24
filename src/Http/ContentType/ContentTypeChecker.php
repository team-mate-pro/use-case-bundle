<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\ContentType;

final class ContentTypeChecker implements IsCsvRequest, IsPdfRequest
{
    private const CSV_MIME_TYPES = [
        'text/csv',
        'application/csv',
        'text/comma-separated-values',
    ];

    private const PDF_MIME_TYPES = [
        'application/pdf',
    ];

    public function isCsvRequest(HeadersAwareInterface $request): bool
    {
        return $this->matchesMimeType($request, self::CSV_MIME_TYPES);
    }

    public function isPdfRequest(HeadersAwareInterface $request): bool
    {
        return $this->matchesMimeType($request, self::PDF_MIME_TYPES);
    }

    /**
     * @param array<string> $mimeTypes
     */
    private function matchesMimeType(HeadersAwareInterface $request, array $mimeTypes): bool
    {
        $accept = $request->getHeader('accept');

        if ($accept === null) {
            return false;
        }

        $accept = strtolower(trim($accept));

        foreach ($mimeTypes as $mimeType) {
            if (str_contains($accept, $mimeType)) {
                return true;
            }
        }

        return false;
    }
}
