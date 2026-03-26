<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TeamMatePro\UseCaseBundle\Http\RequestDependencies;

final class RequestDependenciesTest extends TestCase
{
    #[Test]
    public function constructorStoresAllDependencies(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $requestStack = new RequestStack();
        $security = $this->createMock(Security::class);
        $serializer = $this->createMock(SerializerInterface::class);

        $deps = new RequestDependencies($validator, $requestStack, $security, $serializer);

        self::assertSame($validator, $deps->validator);
        self::assertSame($requestStack, $deps->requestStack);
        self::assertSame($security, $deps->security);
        self::assertSame($serializer, $deps->serializer);
    }

    #[Test]
    public function serializerIsOptional(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $requestStack = new RequestStack();
        $security = $this->createMock(Security::class);

        $deps = new RequestDependencies($validator, $requestStack, $security);

        self::assertNull($deps->serializer);
    }
}
