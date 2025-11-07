<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\EventListener\ValidationExceptionListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[CoversClass(ValidationExceptionListener::class)]
final class ValidationExceptionListenerTest extends TestCase
{
    private ValidationExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ValidationExceptionListener();
    }

    public function testHandlesValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Name is required',
                null,
                [],
                'root',
                'name',
                'invalid-value'
            ),
        ]);

        $exception = new ValidationFailedException('value', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame('validation_failed', $content['message']);
        $this->assertSame(422, $content['code']);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(1, $content['errors']);
        $this->assertSame('name', $content['errors'][0]['property']);
        $this->assertSame('invalid-value', $content['errors'][0]['value']);
        $this->assertSame('Name is required', $content['errors'][0]['message']);
    }

    public function testHandlesMultipleValidationErrors(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Name is required',
                null,
                [],
                'root',
                'name',
                ''
            ),
            new ConstraintViolation(
                'Email is invalid',
                null,
                [],
                'root',
                'email',
                'not-an-email'
            ),
            new ConstraintViolation(
                'Age must be positive',
                null,
                [],
                'root',
                'age',
                -5
            ),
        ]);

        $exception = new ValidationFailedException('value', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertCount(3, $content['errors']);
        $this->assertSame('name', $content['errors'][0]['property']);
        $this->assertSame('email', $content['errors'][1]['property']);
        $this->assertSame('age', $content['errors'][2]['property']);
    }

    public function testIgnoresNonValidationExceptions(): void
    {
        $exception = new \RuntimeException('Some other error');
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testHandlesEmptyViolationList(): void
    {
        $violations = new ConstraintViolationList();
        $exception = new ValidationFailedException('value', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertCount(0, $content['errors']);
    }

    public function testResponseContainsMandatoryFields(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Error', null, [], 'root', 'field', 'value'),
        ]);

        $exception = new ValidationFailedException('value', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('code', $content);
        $this->assertArrayHasKey('errorCode', $content);
    }

    public function testValidationErrorPreservesPropertyPath(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Error',
                null,
                [],
                'root',
                'user.address.street',
                'value'
            ),
        ]);

        $exception = new ValidationFailedException('value', $violations);
        $event = $this->createExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertSame('user.address.street', $content['errors'][0]['property']);
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
