<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\UseCaseBundle\Http\UseSerializerTrait;

final class UseSerializerTraitTest extends TestCase
{
    #[Test]
    public function traitReturnsSerializerStrategy(): void
    {
        $stub = new class {
            use UseSerializerTrait;

            public function getStrategy(): string
            {
                return $this->getPopulateStrategy();
            }
        };

        self::assertSame(AbstractValidatedRequest::SERIALIZER_STRATEGY, $stub->getStrategy());
    }
}
