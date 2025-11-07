<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\EventListener\AuthorizationExceptionListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversClass(AuthorizationExceptionListener::class)]
final class AuthorizationExceptionListenerTest extends TestCase
{
    private AuthorizationExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AuthorizationExceptionListener();
    }

    public function testHandlesAccessDeniedExceptionForJsonRequests(): void
    {
        $exception = new AccessDeniedException('Access denied');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = json_decode($response->getContent(), true);
        $this->assertSame('Access denied', $content['message']);
    }

    public function testUsesExceptionCodeInResponse(): void
    {
        $exception = new AccessDeniedException('Forbidden');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();

        // AccessDeniedException returns 403 by default in Symfony
        $this->assertSame(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame(403, $content['code']);
    }

    public function testIgnoresNonJsonRequests(): void
    {
        $exception = new AccessDeniedException('Access denied');
        $event = $this->createNonJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresNonAccessDeniedExceptions(): void
    {
        $exception = new \RuntimeException('Some other error');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testResponseContainsMandatoryFields(): void
    {
        $exception = new AccessDeniedException('Unauthorized');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('code', $content);
        $this->assertArrayHasKey('errorCode', $content);
    }

    public function testHandlesAccessDeniedWithDefaultCode(): void
    {
        $exception = new AccessDeniedException('Access denied');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();

        // AccessDeniedException default code is 403 in Symfony
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testHandlesAccessDeniedWithCustomMessage(): void
    {
        $exception = new AccessDeniedException('You do not have permission to access this resource');
        $event = $this->createJsonExceptionEvent($exception);

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertSame('You do not have permission to access this resource', $content['message']);
    }

    public function testDoesNotHandleHtmlRequests(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('Content-Type', 'text/html');

        $exception = new AccessDeniedException('Access denied');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoesNotHandleXmlRequests(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('Content-Type', 'application/xml');

        $exception = new AccessDeniedException('Access denied');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    private function createJsonExceptionEvent(\Throwable $exception): ExceptionEvent
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

    private function createNonJsonExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('Content-Type', 'text/html');

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
