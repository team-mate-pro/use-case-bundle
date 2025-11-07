<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\EventListener;

use TeamMatePro\UseCaseBundle\Http\HttpUtils;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AuthorizationExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!HttpUtils::isJson($event->getRequest())) {
            return;
        }

        // Check if the exception is a ValidationFailedException
        if ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse(
                ResultRestRenderer::renderMandatory(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                ), $exception->getCode()
            );

            $event->setResponse($response);
        }
    }
}
