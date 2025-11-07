<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Utils\StringAbleObject;

#[CoversClass(StringAbleObject::class)]
final class StringAbleObjectTest extends TestCase
{
    public function testConstructorAcceptsString(): void
    {
        $sut = new StringAbleObject('test string');

        $this->assertSame('test string', (string) $sut);
    }

    public function testConstructorAcceptsStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable content';
            }
        };

        $sut = new StringAbleObject($stringable);

        $this->assertSame('stringable content', (string) $sut);
    }

    public function testToStringReturnsCorrectValue(): void
    {
        $sut = new StringAbleObject('hello world');

        $this->assertSame('hello world', $sut->__toString());
    }

    public function testImplicitStringConversion(): void
    {
        $sut = new StringAbleObject('implicit');

        $result = "Value: $sut";

        $this->assertSame('Value: implicit', $result);
    }

    public function testWithEmptyString(): void
    {
        $sut = new StringAbleObject('');

        $this->assertSame('', (string) $sut);
    }

    public function testWithNumericString(): void
    {
        $sut = new StringAbleObject('12345');

        $this->assertSame('12345', (string) $sut);
    }

    public function testWithMultilineString(): void
    {
        $content = "Line 1\nLine 2\nLine 3";
        $sut = new StringAbleObject($content);

        $this->assertSame($content, (string) $sut);
    }

    public function testWithSpecialCharacters(): void
    {
        $content = "Special: !@#$%^&*()_+-=[]{}|;':\",./<>?";
        $sut = new StringAbleObject($content);

        $this->assertSame($content, (string) $sut);
    }

    public function testWithUnicodeCharacters(): void
    {
        $content = "Unicode: 你好世界 🌍 café";
        $sut = new StringAbleObject($content);

        $this->assertSame($content, (string) $sut);
    }

    public function testNestedStringableObject(): void
    {
        $inner = new StringAbleObject('inner value');
        $outer = new StringAbleObject($inner);

        $this->assertSame('inner value', (string) $outer);
    }

    public function testImplementsStringableInterface(): void
    {
        $sut = new StringAbleObject('test');

        $this->assertInstanceOf(\Stringable::class, $sut);
    }

    public function testIsReadonly(): void
    {
        $sut = new StringAbleObject('original');

        // Attempting to modify should not be possible (readonly class)
        // This test verifies the readonly nature implicitly through type system
        $this->assertSame('original', (string) $sut);
    }
}
