<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\EventListener;

use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use function array_map;
use function iterator_to_array;

final class ValidationExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

//        if (!HttpUtils::isJson($event->getRequest())) {
//            return;
//        }

        // Check if the exception is a ValidationFailedException
        if ($exception instanceof ValidationFailedException) {
            $response = new JsonResponse(
                ResultRestRenderer::renderMandatory(
                    message: 'validation_failed',
                    code: 422,
                    extra: [
                        'errors' => array_map(
                            fn($message) => [
                                'property' => $message->getPropertyPath(),
                                'value' => $message->getInvalidValue(),
                                'message' => $message->getMessage(),
                            ],
                            iterator_to_array($exception->getViolations())
                        )
                    ]
                ), 422
            );

            $event->setResponse($response);
        }
    }
}
