<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\EventListener\ValidationExceptionListener;
use TeamMatePro\UseCaseBundle\Http\RestApi\HttpStatusCodeResolver;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $this->listener = new ValidationExceptionListener(
            new ResultRestRenderer(new HttpStatusCodeResolver())
        );
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

        $content = $this->decodeJsonResponse($response);
        $this->assertSame('validation_failed', $content['message']);
        $this->assertSame(422, $content['code']);
        $this->assertArrayHasKey('errors', $content);
        self::assertIsArray($content['errors']);
        $this->assertCount(1, $content['errors']);
        self::assertIsArray($content['errors'][0]);
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
        $content = $this->decodeJsonResponse($response);

        self::assertIsArray($content['errors']);
        $this->assertCount(3, $content['errors']);
        self::assertIsArray($content['errors'][0]);
        self::assertIsArray($content['errors'][1]);
        self::assertIsArray($content['errors'][2]);
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

        $content = $this->decodeJsonResponse($response);
        $this->assertArrayHasKey('errors', $content);
        self::assertIsArray($content['errors']);
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
        $content = $this->decodeJsonResponse($response);

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
        $content = $this->decodeJsonResponse($response);

        self::assertIsArray($content['errors']);
        self::assertIsArray($content['errors'][0]);
        $this->assertSame('user.address.street', $content['errors'][0]['property']);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeJsonResponse(?Response $response): array
    {
        self::assertNotNull($response);
        $body = $response->getContent();
        self::assertIsString($body);
        $decoded = json_decode($body, true);
        self::assertIsArray($decoded);

        return $decoded;
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
