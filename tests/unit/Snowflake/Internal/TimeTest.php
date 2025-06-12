<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Snowflake\Epoch;
use Ramsey\Identifier\Snowflake\Internal\Time;
use Ramsey\Identifier\Snowflake\TwitterSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

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
}
