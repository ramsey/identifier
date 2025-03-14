<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Snowflake\Utility\Format;
use Ramsey\Test\Identifier\TestCase;

class FormatTest extends TestCase
{
    /**
     * @param int<0, max> | numeric-string $value
     * @param int<0, max> | string $expected
     */
    #[DataProvider('formatProvider')]
    public function testFormat(int | string $value, ?Format $to, int | string $expected): void
    {
        $this->assertSame($expected, Format::format($value, $to));
    }

    /**
     * @return list<array{value: int<0, max> | numeric-string, to: Format | null, expected: int<0, max> | string}>
     */
    public static function formatProvider(): array
    {
        return [
            [
                'value' => '1541815603606036480',
                'to' => Format::Bytes,
                'expected' => "\x15\x65\xa1\x1f\x62\x17\xa0\x00",
            ],
            [
                'value' => '1541815603606036480',
                'to' => Format::Hex,
                'expected' => '1565a11f6217a000',
            ],
            [
                'value' => '9223372036854775808',
                'to' => Format::Bytes,
                'expected' => "\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'value' => '9223372036854775808',
                'to' => Format::Hex,
                'expected' => '8000000000000000',
            ],
            [
                'value' => '9223372036854775808',
                'to' => null,
                'expected' => '9223372036854775808',
            ],
            [
                'value' => 2147483647,
                'to' => Format::Bytes,
                'expected' => "\x00\x00\x00\x00\x7f\xff\xff\xff",
            ],
            [
                'value' => 2147483647,
                'to' => Format::Hex,
                'expected' => '000000007fffffff',
            ],
            [
                'value' => '9223372036854775807',
                'to' => null,
                'expected' => 9223372036854775807,
            ],
            [
                'value' => '2147483647',
                'to' => null,
                'expected' => 2147483647,
            ],
            [
                'value' => '0',
                'to' => null,
                'expected' => 0,
            ],
        ];
    }
}
