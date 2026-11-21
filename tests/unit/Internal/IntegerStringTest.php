<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Internal\IntegerString;
use Ramsey\Test\Identifier\TestCase;

class IntegerStringTest extends TestCase
{
    #[DataProvider('validProvider')]
    public function testIsValidReturnsTrue(string $value): void
    {
        $this->assertTrue(IntegerString::isValid($value));
    }

    /**
     * @return list<array{value: string}>
     */
    public static function validProvider(): array
    {
        return [
            ['value' => '0'],
            ['value' => '1'],
            ['value' => '10'],
            ['value' => '9223372036854775807'],
            ['value' => '9223372036854775808'],
            ['value' => '340282366920938463463374607431768211455'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testIsValidReturnsFalse(string $value): void
    {
        $this->assertFalse(IntegerString::isValid($value));
    }

    /**
     * @return list<array{value: string}>
     */
    public static function invalidProvider(): array
    {
        return [
            ['value' => ''],
            ['value' => '00'],
            ['value' => '0123'],
            ['value' => '+1'],
            ['value' => '-1'],
            ['value' => '-0'],
            ['value' => '1.0'],
            ['value' => '1e3'],
            ['value' => ' 1'],
            ['value' => '1 '],
            ['value' => "1\n"],
            ['value' => '0x1f'],
            ['value' => '1_000'],
            ['value' => 'foobar'],
        ];
    }

    #[DataProvider('isNegativeProvider')]
    public function testIsNegative(string $value, bool $expected): void
    {
        $this->assertSame($expected, IntegerString::isNegative($value));
    }

    /**
     * @return list<array{value: string, expected: bool}>
     */
    public static function isNegativeProvider(): array
    {
        return [
            ['value' => '-1', 'expected' => true],
            ['value' => '-10', 'expected' => true],
            ['value' => '-9223372036854775809', 'expected' => true],
            ['value' => '-0', 'expected' => false],
            ['value' => '-01', 'expected' => false],
            ['value' => '-', 'expected' => false],
            ['value' => '--1', 'expected' => false],
            ['value' => '-1.0', 'expected' => false],
            ['value' => '-1e3', 'expected' => false],
            ['value' => '- 1', 'expected' => false],
            ['value' => '-foobar', 'expected' => false],
            ['value' => '', 'expected' => false],
            ['value' => '0', 'expected' => false],
            ['value' => '1', 'expected' => false],
            ['value' => '+1', 'expected' => false],
        ];
    }

    #[DataProvider('compareProvider')]
    public function testCompare(string $first, string $second, int $expected): void
    {
        $this->assertSame($expected, IntegerString::compare($first, $second) <=> 0);
        $this->assertSame(-$expected, IntegerString::compare($second, $first) <=> 0);
    }

    /**
     * @return list<array{first: string, second: string, expected: int}>
     */
    public static function compareProvider(): array
    {
        return [
            ['first' => '0', 'second' => '0', 'expected' => 0],
            ['first' => '0', 'second' => '1', 'expected' => -1],
            ['first' => '9', 'second' => '10', 'expected' => -1],
            ['first' => '100', 'second' => '99', 'expected' => 1],
            ['first' => '9223372036854775807', 'second' => '9223372036854775807', 'expected' => 0],
            ['first' => '9223372036854775807', 'second' => '9223372036854775808', 'expected' => -1],
            ['first' => '9223372036854775808', 'second' => '9223372036854775809', 'expected' => -1],
            ['first' => '18446744073709551616', 'second' => '18446744073709551615', 'expected' => 1],
            [
                'first' => '340282366920938463463374607431768211455',
                'second' => '340282366920938463463374607431768211454',
                'expected' => 1,
            ],
        ];
    }

    #[DataProvider('fitsNativeIntegerProvider')]
    public function testFitsNativeInteger(string $value, bool $expected): void
    {
        $this->assertSame($expected, IntegerString::fitsNativeInteger($value));
    }

    /**
     * @return list<array{value: string, expected: bool}>
     */
    public static function fitsNativeIntegerProvider(): array
    {
        return [
            ['value' => '0', 'expected' => true],
            ['value' => '9223372036854775806', 'expected' => true],
            ['value' => '9223372036854775807', 'expected' => true],
            ['value' => '9223372036854775808', 'expected' => false],
            ['value' => '9223372036854775809', 'expected' => false],
            ['value' => '9223372036854776832', 'expected' => false],
            ['value' => '10000000000000000000', 'expected' => false],
            ['value' => '340282366920938463463374607431768211455', 'expected' => false],
        ];
    }
}
