<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeObjectToPopulate;
use TeamMatePro\UseCaseBundle\Utils\PartialUpdateService;

final class PartialUpdateServiceSkipsTest extends TestCase
{
    #[Test]
    public function mapSkipsPropertiesInSkipList(): void
    {
        $sut = new PartialUpdateService();

        $from = new FakeObjectToPopulate();
        $from->name = 'John';
        $from->age = 30;

        $into = new FakeObjectToPopulate();
        $into->name = 'Original';
        $into->age = 0;

        $sut->map($from, $into, skips: ['name']);

        self::assertSame('Original', $into->name);
        self::assertSame(30, $into->age);
    }

    #[Test]
    public function mapSkipsMethodsWithRequiredParamsAndNonGetters(): void
    {
        $from = new class {
            public function getName(): string
            {
                return 'value';
            }
            // has required params - skipped
            public function setStuff(string $val): void
            {
            }

            // not a getter - skipped
            public function doSomething(): string
            {
                return 'ignored';
            }
        };

        $into = new class {
            public string $name = '';
        };

        $sut = new PartialUpdateService();
        $sut->map($from, $into);

        self::assertSame('value', $into->name);
    }

    #[Test]
    public function mapWithPrivatePropertyAndNoSetterInNonStrictMode(): void
    {
        $from = new FakeObjectToPopulate();
        $from->name = 'test';
        $from->unmappedField = 999;

        $into = new class {
            public string $name = '';
        };

        $sut = new PartialUpdateService();
        $result = $sut->map($from, $into);

        self::assertSame('test', $result->name);
    }
}
