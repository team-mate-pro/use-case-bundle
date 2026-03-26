<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Serializer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Serializer\SerializationGroup;

final class SerializationGroupTest extends TestCase
{
    #[Test]
    public function csvConstantIsDefined(): void
    {
        self::assertIsString(SerializationGroup::CSV);
    }
}
