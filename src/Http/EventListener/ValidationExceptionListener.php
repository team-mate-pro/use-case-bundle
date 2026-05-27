<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http\EventListener;

use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function array_map;
use function iterator_to_array;

final class ValidationExceptionListener
{
    public function __construct(
        private readonly ResultRendererInterface $resultRenderer,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationFailedException) {
            $response = new JsonResponse(
                $this->resultRenderer->renderMandatory(
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
                ),
                422
            );

            $event->setResponse($response);
        }
    }
}
