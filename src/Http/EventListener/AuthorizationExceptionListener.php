<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\EventListener;

use TeamMatePro\UseCaseBundle\Http\HttpUtils;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AuthorizationExceptionListener
{
    public function __construct(
        private readonly ResultRendererInterface $resultRenderer,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!HttpUtils::isJson($event->getRequest())) {
            return;
        }

        if ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse(
                $this->resultRenderer->renderMandatory(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                ),
                $exception->getCode()
            );

            $event->setResponse($response);
        }
    }
}
