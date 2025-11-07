<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\ValueObject;

use DateTimeImmutable;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TeamMatePro\Contracts\ValueObject\TimeRange;

#[CoversClass(TimeRange::class)]
final class TimeRangeTest extends TestCase
{
    #[DataProvider('currentYearProvider')]
    public function testCurrentYear(int $count, string $expectedStartYear, string $expectedEndYear): void
    {
        $timeRange = TimeRange::currentYear($count);

        $this->assertSame($expectedStartYear . '-01-01 00:00:00', $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame($expectedEndYear . '-12-31 23:59:59', $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }

    public static function currentYearProvider(): Generator
    {
        $currentYear = (int) date('Y');

        yield 'current year with default count' => [
            1,
            (string) $currentYear,
            (string) $currentYear
        ];

        yield 'current year plus 1 previous year (2 years total)' => [
            2,
            (string) ($currentYear - 1),
            (string) $currentYear
        ];

        yield 'current year plus 2 previous years (3 years total)' => [
            3,
            (string) ($currentYear - 2),
            (string) $currentYear
        ];

        yield 'current year plus 4 previous years (5 years total)' => [
            5,
            (string) ($currentYear - 4),
            (string) $currentYear
        ];
    }

    #[DataProvider('previousYearProvider')]
    public function testPreviousYear(int $count, string $expectedStartYear, string $expectedEndYear): void
    {
        $timeRange = TimeRange::previousYear($count);

        $this->assertSame($expectedStartYear . '-01-01 00:00:00', $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame($expectedEndYear . '-12-31 23:59:59', $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }

    public static function previousYearProvider(): Generator
    {
        $currentYear = (int) date('Y');
        $previousYear = $currentYear - 1;

        yield 'previous year with default count' => [
            1,
            (string) $previousYear,
            (string) $previousYear
        ];

        yield 'previous year plus 1 more year back (2 years total)' => [
            2,
            (string) ($previousYear - 1),
            (string) $previousYear
        ];

        yield 'previous year plus 2 more years back (3 years total)' => [
            3,
            (string) ($previousYear - 2),
            (string) $previousYear
        ];

        yield 'previous year plus 4 more years back (5 years total)' => [
            5,
            (string) ($previousYear - 4),
            (string) $previousYear
        ];
    }

    public function testCurrentYearReturnsCorrectTimeRange(): void
    {
        $timeRange = TimeRange::currentYear();

        $this->assertInstanceOf(TimeRange::class, $timeRange);
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getStart());
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getEnd());
        $this->assertLessThan($timeRange->getEnd(), $timeRange->getStart());
    }

    public function testPreviousYearReturnsCorrectTimeRange(): void
    {
        $timeRange = TimeRange::previousYear();

        $this->assertInstanceOf(TimeRange::class, $timeRange);
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getStart());
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getEnd());
        $this->assertLessThan($timeRange->getEnd(), $timeRange->getStart());
    }

    public function testCurrentYearStartTimeIsSetToMidnight(): void
    {
        $timeRange = TimeRange::currentYear();

        $this->assertSame('00:00:00', $timeRange->getStart()->format('H:i:s'));
    }

    public function testCurrentYearEndTimeIsSetToEndOfDay(): void
    {
        $timeRange = TimeRange::currentYear();

        $this->assertSame('23:59:59', $timeRange->getEnd()->format('H:i:s'));
    }

    public function testPreviousYearStartTimeIsSetToMidnight(): void
    {
        $timeRange = TimeRange::previousYear();

        $this->assertSame('00:00:00', $timeRange->getStart()->format('H:i:s'));
    }

    public function testPreviousYearEndTimeIsSetToEndOfDay(): void
    {
        $timeRange = TimeRange::previousYear();

        $this->assertSame('23:59:59', $timeRange->getEnd()->format('H:i:s'));
    }

    public function testCurrentYearWithCountOne(): void
    {
        $currentYear = (int) date('Y');
        $timeRange = TimeRange::currentYear(1);

        $this->assertSame($currentYear . '-01-01', $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame($currentYear . '-12-31', $timeRange->getEnd()->format('Y-m-d'));
    }

    public function testPreviousYearWithCountOne(): void
    {
        $previousYear = ((int) date('Y')) - 1;
        $timeRange = TimeRange::previousYear(1);

        $this->assertSame($previousYear . '-01-01', $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame($previousYear . '-12-31', $timeRange->getEnd()->format('Y-m-d'));
    }

    public function testFromString(): void
    {
        $timeRange = TimeRange::fromString('2024-01-01', '2024-12-31');

        $this->assertSame('2024-01-01', $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame('2024-12-31', $timeRange->getEnd()->format('Y-m-d'));
    }

    public function testFromDuration(): void
    {
        $start = new DateTimeImmutable('2024-01-01 10:00:00');
        $timeRange = TimeRange::fromDuration($start, 60);

        $this->assertSame('2024-01-01 10:00:00', $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-01 11:00:00', $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }

    public function testWeeksBelowDate(): void
    {
        $date = new DateTimeImmutable('2024-01-15');
        $timeRange = TimeRange::weeksBelowDate($date, 2);

        $this->assertSame('2024-01-01', $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame('2024-01-15', $timeRange->getEnd()->format('Y-m-d'));
    }

    public function testCurrentMonth(): void
    {
        $timeRange = TimeRange::currentMonth();

        $expectedStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $expectedEnd = (new DateTimeImmutable('last day of this month'))->format('Y-m-d');

        $this->assertSame($expectedStart, $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame($expectedEnd, $timeRange->getEnd()->format('Y-m-d'));
    }

    #[DataProvider('quarterProvider')]
    public function testQuarter(int $quarter, ?int $year, string $expectedStart, string $expectedEnd): void
    {
        $timeRange = TimeRange::quarter($quarter, $year);

        $this->assertSame($expectedStart, $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame($expectedEnd, $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }

    public static function quarterProvider(): Generator
    {
        $currentYear = (int) date('Y');

        yield 'Q1 with current year (default)' => [
            1,
            null,
            $currentYear . '-01-01 00:00:00',
            $currentYear . '-03-31 23:59:59'
        ];

        yield 'Q2 with current year (default)' => [
            2,
            null,
            $currentYear . '-04-01 00:00:00',
            $currentYear . '-06-30 23:59:59'
        ];

        yield 'Q3 with current year (default)' => [
            3,
            null,
            $currentYear . '-07-01 00:00:00',
            $currentYear . '-09-30 23:59:59'
        ];

        yield 'Q4 with current year (default)' => [
            4,
            null,
            $currentYear . '-10-01 00:00:00',
            $currentYear . '-12-31 23:59:59'
        ];

        yield 'Q1 2024' => [
            1,
            2024,
            '2024-01-01 00:00:00',
            '2024-03-31 23:59:59'
        ];

        yield 'Q2 2024' => [
            2,
            2024,
            '2024-04-01 00:00:00',
            '2024-06-30 23:59:59'
        ];

        yield 'Q3 2024' => [
            3,
            2024,
            '2024-07-01 00:00:00',
            '2024-09-30 23:59:59'
        ];

        yield 'Q4 2024' => [
            4,
            2024,
            '2024-10-01 00:00:00',
            '2024-12-31 23:59:59'
        ];

        yield 'Q1 2023' => [
            1,
            2023,
            '2023-01-01 00:00:00',
            '2023-03-31 23:59:59'
        ];

        yield 'Q4 2020' => [
            4,
            2020,
            '2020-10-01 00:00:00',
            '2020-12-31 23:59:59'
        ];
    }

    #[DataProvider('invalidQuarterProvider')]
    public function testQuarterThrowsExceptionForInvalidQuarter(int $invalidQuarter): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quarter must be between 1 and 4, got ' . $invalidQuarter);

        TimeRange::quarter($invalidQuarter);
    }

    public static function invalidQuarterProvider(): Generator
    {
        yield 'quarter 0' => [0];
        yield 'quarter 5' => [5];
        yield 'quarter -1' => [-1];
        yield 'quarter 10' => [10];
    }

    public function testQuarterWithCurrentYearByDefault(): void
    {
        $currentYear = (int) date('Y');
        $timeRange = TimeRange::quarter(1);

        $this->assertSame($currentYear . '-01-01', $timeRange->getStart()->format('Y-m-d'));
        $this->assertSame($currentYear . '-03-31', $timeRange->getEnd()->format('Y-m-d'));
    }

    public function testQuarterStartTimeIsSetToMidnight(): void
    {
        $timeRange = TimeRange::quarter(2, 2024);

        $this->assertSame('00:00:00', $timeRange->getStart()->format('H:i:s'));
    }

    public function testQuarterEndTimeIsSetToEndOfDay(): void
    {
        $timeRange = TimeRange::quarter(3, 2024);

        $this->assertSame('23:59:59', $timeRange->getEnd()->format('H:i:s'));
    }

    public function testQuarterReturnsCorrectTimeRange(): void
    {
        $timeRange = TimeRange::quarter(1, 2024);

        $this->assertInstanceOf(TimeRange::class, $timeRange);
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getStart());
        $this->assertInstanceOf(\DateTimeInterface::class, $timeRange->getEnd());
        $this->assertLessThan($timeRange->getEnd(), $timeRange->getStart());
    }

    public function testQuarterWithSpecificYear(): void
    {
        $timeRange = TimeRange::quarter(2, 2023);

        $this->assertSame('2023-04-01 00:00:00', $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2023-06-30 23:59:59', $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }

    public function testQuarterHandlesLeapYear(): void
    {
        $timeRange = TimeRange::quarter(1, 2024); // 2024 is a leap year

        $this->assertSame('2024-01-01 00:00:00', $timeRange->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2024-03-31 23:59:59', $timeRange->getEnd()->format('Y-m-d H:i:s'));
    }
}
