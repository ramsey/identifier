<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use Ramsey\Identifier\Snowflake;
use Ramsey\Test\Identifier\ComparisonTests;

/**
 * Adds the comparison cases that every snowflake shares
 */
trait SnowflakeComparisonTests
{
    use ComparisonTests;

    /**
     * Returns a snowflake of the class under test
     *
     * @param int<0, max> | numeric-string $value
     */
    abstract protected static function createSnowflake(int | string $value): Snowflake;

    /**
     * @return array<string, string>
     */
    protected static function notComparableValuesOfKind(): array
    {
        return [
            'a string with a leading zero' => '0123',
            'a string of 26 digits' => '12345678901234567890123456',
            'a string one more than the largest snowflake' => '18446744073709551616',
            'a negative number as a string' => '-1',
        ];
    }

    public function testCompareToOrdersByNumberAndNotByText(): void
    {
        $snowflake = static::createSnowflake(10);

        $this->assertGreaterThan(0, $snowflake->compareTo('9'));
        $this->assertFalse($snowflake->equals('9'));
        $this->assertGreaterThan(0, $snowflake->compareTo(static::createSnowflake(9)));
        $this->assertLessThan(0, static::createSnowflake(9)->compareTo($snowflake));
    }

    public function testCompareToAcceptsTheLargestSnowflakeString(): void
    {
        $snowflake = static::createSnowflake(10);

        $this->assertLessThan(0, $snowflake->compareTo('18446744073709551615'));
        $this->assertGreaterThan(0, $snowflake->compareTo('0'));
    }
}
