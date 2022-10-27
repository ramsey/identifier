<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Utility;

use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Snowflake\TwitterSnowflakeFactory;
use Ramsey\Identifier\Snowflake\Utility\Time;
use Ramsey\Test\Identifier\TestCase;

class TimeTest extends TestCase
{
    /**
     * @param int | numeric-string $epochOffset
     *
     * @dataProvider getDateTimeForSnowflakeProvider
     */
    public function testGetDateTimeForSnowflake(
        string $snowflake,
        int | string $epochOffset,
        string $expectedTime,
    ): void {
        $snowflake = (new TwitterSnowflakeFactory(123))->createFromString($snowflake);
        $time = new Time();

        $this->assertSame(
            $expectedTime,
            $time->getDateTimeForSnowflake($snowflake, $epochOffset)->format('Y-m-d H:i:s.u'),
        );
    }

    /**
     * @param int | numeric-string $epochOffset
     *
     * @dataProvider getDateTimeForSnowflakeProvider
     */
    public function testGetDateTimeForSnowflakeOn32Bit(
        string $snowflake,
        int | string $epochOffset,
        string $expectedTime,
    ): void {
        $os = $this->mockery(Os::class, [
            'getIntSize' => 4,
        ]);

        $snowflake = (new TwitterSnowflakeFactory(123))->createFromString($snowflake);
        $time = new Time($os);

        $this->assertSame(
            $expectedTime,
            $time->getDateTimeForSnowflake($snowflake, $epochOffset)->format('Y-m-d H:i:s.u'),
        );
    }

    /**
     * @return array<array{snowflake: string, epochOffset: int | string, expectedTime: string}>
     */
    public function getDateTimeForSnowflakeProvider(): array
    {
        return [
            [
                'snowflake' => '1541815603606036480',
                'epochOffset' => '1288834974657',
                'expectedTime' => '2022-06-28 16:07:40.105000',
            ],
            [
                'snowflake' => '1585489802600888797',
                'epochOffset' => '1288834974657',
                'expectedTime' => '2022-10-27 04:33:20.573000',
            ],
            [
                'snowflake' => '6991255492144641159',
                'epochOffset' => 0,
                'expectedTime' => '2022-10-27 04:33:20.573000',
            ],
        ];
    }
}
