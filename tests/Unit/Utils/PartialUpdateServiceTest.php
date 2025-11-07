<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeObjectToPopulate;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeObjectToPopulateWithSetters;
use TeamMatePro\Contracts\Dto\Undefined;
use TeamMatePro\UseCaseBundle\Utils\PartialUpdateService;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(PartialUpdateService::class)]
final class PartialUpdateServiceTest extends TestCase
{
    private PartialUpdateService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new PartialUpdateService();
    }

    public function testUpdatesOneObjectWithAnotherOnAllFieldsMatch(): void
    {
        $objA = new FakeObjectToPopulate();
        $objB = new FakeObjectToPopulate();

        $objA->name = 'test';
        $objA->age = 11;
        $objA->date = new DateTimeImmutable();
        $objB->name = null;
        $objB->age = null;
        $this->sut->map($objA, $objB);

        $this->assertSame('test', $objA->name);
        $this->assertSame(11, $objA->age);
        $this->assertSame('test', $objB->name);
        $this->assertSame(11, $objB->age);
        $this->assertInstanceOf(DateTimeInterface::class, $objB->date);
    }

    public function testUpdatesOneObjectWithAnotherOnSkippingUndefinedValues(): void
    {
        $objA = new FakeObjectToPopulate();
        $objB = new FakeObjectToPopulate();

        $objA->name = new Undefined();
        $objA->age = 11;
        $objB->name = null;
        $objB->age = null;
        $this->sut->map($objA, $objB);

        $this->assertNull($objB->name);
        $this->assertSame(11, $objB->age);
    }

    public function testUpdatesOneObjectWithAnotherOnSkippingUndefinedValuesUsingSetters(): void
    {
        $objA = new FakeObjectToPopulate();
        $objB = new FakeObjectToPopulateWithSetters();

        // test is adding extra values on setters
        $objA->name = 'name';
        $objA->age = 11;
        $this->sut->map($objA, $objB);

        $this->assertSame($objB->getName(), $objA->name . '_test');
        $this->assertSame(12, $objB->getAge());
    }

    public function testFailsWhenSomeFieldIsMissingInTargetObjectAndStrictModeEnabled(): void
    {
        $objA = new FakeObjectToPopulate();
        $objB = new FakeObjectToPopulateWithSetters();

        // test is adding extra values on setters
        $objA->name = 'name';
        $objA->age = 11;
        $objA->unmappedField = 12;

        $this->expectException(InvalidArgumentException::class);

        $this->sut->map($objA, $objB, true);
    }
}
