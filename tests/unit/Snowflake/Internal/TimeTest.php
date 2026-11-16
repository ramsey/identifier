<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Snowflake\Epoch;
use Ramsey\Identifier\Snowflake\GenericSnowflake;
use Ramsey\Identifier\Snowflake\Internal\Time;
use Ramsey\Identifier\Snowflake\TwitterSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class TimeTest extends TestCase
{
    /**
     * @param numeric-string $snowflake
     */
    #[DataProvider('getDateTimeForSnowflakeProvider')]
    public function testGetDateTimeForSnowflake(
        string $snowflake,
        int $epochOffset,
        int $rightShifts,
        string $expectedTime,
    ): void {
        $snowflake = (new TwitterSnowflakeFactory(123))->createFromString($snowflake);
        $time = new Time();

        $this->assertSame(
            $expectedTime,
            $time->getDateTimeForSnowflake($snowflake, $epochOffset, $rightShifts)->format('Y-m-d H:i:s.u'),
        );
    }

    /**
     * @return list<array{snowflake: string, epochOffset: int | string, rightShifts: int, expectedTime: string}>
     */
    public static function getDateTimeForSnowflakeProvider(): array
    {
        return [
            [
                'snowflake' => '1541815603606036480',
                'epochOffset' => Epoch::Twitter->value,
                'rightShifts' => 22,
                'expectedTime' => '2022-06-28 16:07:40.105000',
            ],
            [
                'snowflake' => '1585489802600888797',
                'epochOffset' => Epoch::Twitter->value,
                'rightShifts' => 22,
                'expectedTime' => '2022-10-27 04:33:20.573000',
            ],
            [
                'snowflake' => '6991255492144641159',
                'epochOffset' => 0,
                'rightShifts' => 22,
                'expectedTime' => '2022-10-27 04:33:20.573000',
            ],
            [
                'snowflake' => '2958557661194367726',
                'epochOffset' => Epoch::Instagram->value,
                'rightShifts' => 23,
                'expectedTime' => '2022-10-27 21:52:58.607000',
            ],
            [
                'snowflake' => '175928847299117063',
                'epochOffset' => Epoch::Discord->value,
                'rightShifts' => 22,
                'expectedTime' => '2016-04-30 11:18:25.796000',
            ],
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('unrepresentableDateTimeProvider')]
    public function testGetDateTimeForSnowflakeThrowsExceptionWhenNotRepresentable(
        int | string $value,
        int $epochOffset,
    ): void {
        $snowflake = new GenericSnowflake($value, $epochOffset);
        $time = new Time();

        $this->expectException(NotConvertible::class);
        $this->expectExceptionMessage('The date-time of the snowflake cannot be represented');

        $time->getDateTimeForSnowflake($snowflake, $epochOffset, 22);
    }

    /**
     * @return list<array{value: int<0, max> | numeric-string, epochOffset: int}>
     */
    public static function unrepresentableDateTimeProvider(): array
    {
        return [
            ['value' => '9223372036854775807', 'epochOffset' => PHP_INT_MAX],
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('negativeRightShiftsProvider')]
    public function testGetDateTimeForSnowflakeThrowsExceptionForNegativeRightShifts(int | string $value): void
    {
        $snowflake = new GenericSnowflake($value, 0);
        $time = new Time();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The number of right shifts must not be negative; received -1');

        $time->getDateTimeForSnowflake($snowflake, 0, -1);
    }

    /**
     * @return list<array{value: int<0, max> | numeric-string}>
     */
    public static function negativeRightShiftsProvider(): array
    {
        return [
            ['value' => 5],
            ['value' => '18446744073709551615'],
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('largeRightShiftsProvider')]
    public function testGetDateTimeForSnowflakeWithLargeRightShifts(
        int | string $value,
        int $epochOffset,
        int $rightShifts,
        string $expected,
    ): void {
        $snowflake = new GenericSnowflake($value, $epochOffset);
        $time = new Time();

        $this->assertSame(
            $expected,
            $time->getDateTimeForSnowflake($snowflake, $epochOffset, $rightShifts)->format('Y-m-d H:i:s.v'),
        );
    }

    /**
     * @return list<array{value: int<0, max> | numeric-string, epochOffset: int, rightShifts: int, expected: string}>
     */
    public static function largeRightShiftsProvider(): array
    {
        $cases = [];

        foreach ([5, PHP_INT_MAX, '18446744073709551615'] as $value) {
            foreach ([64, 100, 2147483647, PHP_INT_MAX] as $rightShifts) {
                $cases[] = [
                    'value' => $value,
                    'epochOffset' => 0,
                    'rightShifts' => $rightShifts,
                    'expected' => '1970-01-01 00:00:00.000',
                ];
                $cases[] = [
                    'value' => $value,
                    'epochOffset' => 1500,
                    'rightShifts' => $rightShifts,
                    'expected' => '1970-01-01 00:00:01.500',
                ];
            }
        }

        $cases[] = [
            'value' => '18446744073709551615',
            'epochOffset' => 0,
            'rightShifts' => 63,
            'expected' => '1970-01-01 00:00:00.001',
        ];
        $cases[] = [
            'value' => PHP_INT_MAX,
            'epochOffset' => 0,
            'rightShifts' => 63,
            'expected' => '1970-01-01 00:00:00.000',
        ];

        return $cases;
    }

    #[DataProvider('millisecondsBeforeEpochProvider')]
    public function testGetDateTimeForSnowflakeBeforeUnixEpoch(int $epochOffset, string $expected): void
    {
        $time = new Time();

        $this->assertSame(
            $expected,
            $time->getDateTimeForSnowflake(new GenericSnowflake(0, $epochOffset), $epochOffset, 0)
                ->format('Y-m-d H:i:s.v'),
        );
        $this->assertSame(
            $expected,
            $time->getDateTimeForSnowflake(new GenericSnowflake('0', $epochOffset), $epochOffset, 22)
                ->format('Y-m-d H:i:s.v'),
        );
    }

    /**
     * @return list<array{epochOffset: int, expected: string}>
     */
    public static function millisecondsBeforeEpochProvider(): array
    {
        return [
            ['epochOffset' => -1, 'expected' => '1969-12-31 23:59:59.999'],
            ['epochOffset' => -500, 'expected' => '1969-12-31 23:59:59.500'],
            ['epochOffset' => -999, 'expected' => '1969-12-31 23:59:59.001'],
            ['epochOffset' => -1000, 'expected' => '1969-12-31 23:59:59.000'],
            ['epochOffset' => -1001, 'expected' => '1969-12-31 23:59:58.999'],
            ['epochOffset' => -1500, 'expected' => '1969-12-31 23:59:58.500'],
        ];
    }

    public function testGetDateTimeForSnowflakeReturnsDateTimeForMinimumOffset(): void
    {
        $snowflake = new GenericSnowflake(0, PHP_INT_MIN);
        $time = new Time();

        $this->assertSame(
            '-292275055-05-16 16:47:04.192',
            $time->getDateTimeForSnowflake($snowflake, PHP_INT_MIN, 22)->format('Y-m-d H:i:s.v'),
        );
    }

    public function testGetDateTimeForSnowflakeReturnsDateTimeForMaximumValueAndOffset(): void
    {
        $snowflake = new GenericSnowflake('18446744073709551615', PHP_INT_MAX);
        $time = new Time();

        $this->assertSame(
            '292279133-12-30 14:48:06.910',
            $time->getDateTimeForSnowflake($snowflake, PHP_INT_MAX, 22)->format('Y-m-d H:i:s.v'),
        );
    }
}
