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
}
