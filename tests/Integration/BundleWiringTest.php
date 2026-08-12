<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Integration;

use TeamMatePro\TestsBundle\AbstractKernelTestCase;
use TeamMatePro\UseCaseBundle\Http\EventListener\AuthorizationExceptionListener;
use TeamMatePro\UseCaseBundle\Http\EventListener\ValidationExceptionListener;
use TeamMatePro\UseCaseBundle\Http\RestApi\HttpStatusCodeResolver;
use TeamMatePro\UseCaseBundle\Http\RestApi\HttpStatusCodeResolverInterface;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRendererInterface;
use TeamMatePro\UseCaseBundle\Http\RestApi\ResultRestRenderer;

/**
 * Guards the contract that consumers of `team-mate-pro/use-case-bundle` need
 * zero wiring after install: registering the bundle in bundles.php must be enough
 * for every public abstraction to be autowireable.
 */
final class BundleWiringTest extends AbstractKernelTestCase
{
    public function testResultRendererInterfaceIsAliasedToResultRestRenderer(): void
    {
        self::bootKernel();

        $service = self::getContainer()->get(ResultRendererInterface::class);

        self::assertInstanceOf(ResultRestRenderer::class, $service);
    }

    public function testHttpStatusCodeResolverInterfaceIsAliasedToImplementation(): void
    {
        self::bootKernel();

        $service = self::getContainer()->get(HttpStatusCodeResolverInterface::class);

        self::assertInstanceOf(HttpStatusCodeResolver::class, $service);
    }

    public function testExceptionListenersAreRegisteredAndAutowired(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(
            AuthorizationExceptionListener::class,
            $container->get(AuthorizationExceptionListener::class),
        );
        self::assertInstanceOf(
            ValidationExceptionListener::class,
            $container->get(ValidationExceptionListener::class),
        );
    }
}
