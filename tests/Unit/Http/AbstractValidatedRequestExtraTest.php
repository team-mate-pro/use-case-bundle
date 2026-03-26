<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\UseCaseBundle\Http\RequestDependencies;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeDependencies;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeTestingRequest;

final class AbstractValidatedRequestExtraTest extends TestCase
{
    #[Test]
    public function hasHeaderReturnsTrueWhenPresent(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        self::assertTrue($sut->hasHeader('accept'));
    }

    #[Test]
    public function hasHeaderReturnsFalseWhenMissing(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        self::assertFalse($sut->hasHeader('x-nonexistent'));
    }

    #[Test]
    public function getHeaderReturnsValueWhenPresent(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        self::assertSame('application/json', $sut->getHeader('accept'));
    }

    #[Test]
    public function getHeaderReturnsNullWhenMissing(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        self::assertNull($sut->getHeader('x-nonexistent'));
    }

    #[Test]
    public function magicGetReturnsSecurityDependency(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        self::assertNotNull($sut->security);
    }

    #[Test]
    public function magicGetThrowsForUnknownProperty(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Property not found');

        $sut->nonExistentDep; // @phpstan-ignore property.notFound, expr.resultUnused
    }

    #[Test]
    public function magicCallThrowsForUnknownMethod(): void
    {
        $sut = new FakeTestingRequest(new FakeDependencies());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method not found');

        /** @phpstan-ignore method.notFound */
        $sut->unknownMethod();
    }

    #[Test]
    public function securityCheckFailureThrowsAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        new class (new FakeDependencies()) extends AbstractValidatedRequest {
            protected function securityCheck(): bool
            {
                return false;
            }
        };
    }

    #[Test]
    public function autoValidateRequestCanBeDisabled(): void
    {
        $sut = new class (new FakeDependencies()) extends AbstractValidatedRequest {
            public string $required = '';

            protected function autoValidateRequest(): bool
            {
                return false;
            }
        };

        self::assertInstanceOf(AbstractValidatedRequest::class, $sut); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    #[Test]
    public function getUserIdMagicCallThrowsWhenPropertyMissing(): void
    {
        $sut = new class (new FakeDependencies()) extends AbstractValidatedRequest {
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('userId property is required');

        $sut->getUserId();
    }

    #[Test]
    public function populateWithSerializerStrategyThrowsWithoutSerializer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Serializer is required');

        new class (new FakeDependencies()) extends AbstractValidatedRequest {
            protected function getPopulateStrategy(): string
            {
                return self::SERIALIZER_STRATEGY;
            }
        };
    }

    #[Test]
    public function populateWithUnknownStrategyThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown populate strategy');

        new class (new FakeDependencies()) extends AbstractValidatedRequest {
            protected function getPopulateStrategy(): string
            {
                return 'unknown';
            }
        };
    }
}
