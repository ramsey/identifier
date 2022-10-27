<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Utility;

use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Snowflake\Utility\Format;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;
use const PHP_INT_SIZE;

class FormatTest extends TestCase
{
    /**
     * @param int<0, max> | numeric-string $value
     * @param int<0, max> | string $expected
     *
     * @psalm-param Format::FORMAT_* $to
     *
     * @dataProvider formatProvider
     */
    public function testFormat(int | string $value, int $to, int | string $expected): void
    {
        $format = new Format();

        $this->assertSame($expected, $format->format($value, $to));
    }

    /**
     * @param int<0, max> | numeric-string $value
     * @param int<0, max> | string $expected
     *
     * @psalm-param Format::FORMAT_* $to
     *
     * @dataProvider formatProvider
     */
    public function testFormatOn32Bit(int | string $value, int $to, int | string $expected): void
    {
        $os = $this->mockery(Os::class, [
            'getIntSize' => 4,
        ]);

        $format = new Format($os);

        $this->assertSame($expected, $format->format($value, $to));
    }

    /**
     * @return array<array{value: int<0, max> | numeric-string, to: Format::FORMAT_*, expected: int<0, max> | string}>
     */
    public function formatProvider(): array
    {
        return [
            [
                'value' => '1541815603606036480',
                'to' => Format::FORMAT_BYTES,
                'expected' => "\x15\x65\xa1\x1f\x62\x17\xa0\x00",
            ],
            [
                'value' => '1541815603606036480',
                'to' => Format::FORMAT_HEX,
                'expected' => '1565a11f6217a000',
            ],
            [
                'value' => '9223372036854775808',
                'to' => Format::FORMAT_BYTES,
                'expected' => "\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'value' => '9223372036854775808',
                'to' => Format::FORMAT_HEX,
                'expected' => '8000000000000000',
            ],
            [
                'value' => '9223372036854775808',
                'to' => Format::FORMAT_INT,
                'expected' => '9223372036854775808',
            ],
            [
                'value' => 2147483647,
                'to' => Format::FORMAT_BYTES,
                'expected' => "\x00\x00\x00\x00\x7f\xff\xff\xff",
            ],
            [
                'value' => 2147483647,
                'to' => Format::FORMAT_HEX,
                'expected' => '000000007fffffff',
            ],
        ];
    }

    public function testIntegerFormatOn32Bit(): void
    {
        $os = $this->mockery(Os::class, [
            'getIntSize' => 4,
        ]);

        $format = new Format($os);

        $this->assertSame((string) PHP_INT_MAX, $format->format(PHP_INT_MAX, Format::FORMAT_INT));
        $this->assertSame('2147483647', $format->format(2147483647, Format::FORMAT_INT));
        $this->assertSame('0', $format->format(0, Format::FORMAT_INT));
    }

    public function testIntegerFormatOn64Bit(): void
    {
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('Test requires 64-bit system');
        }

        $format = new Format();

        $this->assertSame(9223372036854775807, $format->format('9223372036854775807', Format::FORMAT_INT));
        $this->assertSame(2147483647, $format->format('2147483647', Format::FORMAT_INT));
        $this->assertSame(0, $format->format('0', Format::FORMAT_INT));
    }
}
