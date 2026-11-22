<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake\Internal\Format;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MIN;

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
            [
                'value' => '18446744073709551615',
                'to' => Format::Hex,
                'expected' => 'ffffffffffffffff',
            ],
            [
                'value' => '18446744073709551615',
                'to' => Format::Bytes,
                'expected' => "\xff\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'value' => '18446744073709551615',
                'to' => null,
                'expected' => '18446744073709551615',
            ],
        ];
    }

    #[TestWith([''])]
    #[TestWith(['007'])]
    #[TestWith(['00'])]
    #[TestWith(['+1'])]
    #[TestWith(['-1'])]
    #[TestWith(['1.0'])]
    #[TestWith(['1e3'])]
    #[TestWith([' 1'])]
    #[TestWith(['0x1f'])]
    #[TestWith(['foobar'])]
    #[TestWith(['18446744073709551616'])]
    #[TestWith([-1])]
    #[TestWith([PHP_INT_MIN])]
    public function testFormatThrowsExceptionForValueThatIsNotASnowflakeInteger(int | string $value): void
    {
        foreach ([null, Format::Hex, Format::Bytes] as $to) {
            try {
                Format::format($value, $to);
                $this->fail('Format::format() did not throw for ' . $value);
            } catch (InvalidArgument $exception) {
                $this->assertSame('Invalid integer: "' . $value . '"', $exception->getMessage());
            }
        }
    }
}
