<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Test\Identifier\ComparisonTests;

/**
 * Adds the comparison cases that every UUID shares
 */
trait UuidComparisonTests
{
    use ComparisonTests;

    /**
     * @return array<string, Ulid>
     */
    protected static function sameSortKeyOfAnotherKind(): array
    {
        $values = [];

        foreach (static::comparisonSubjects() as $subject) {
            $values['a Ulid with the same bytes'] = new Ulid($subject->getSortKey());
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    protected static function notComparableValuesOfKind(): array
    {
        $values = ['a string of 16 digits' => '1234567890123456'];

        foreach (static::comparisonSubjects() as $subject) {
            $values['its bytes'] = $subject->toBytes();
            $values['its hexadecimal form'] = $subject->toHexadecimal();
            $values['its string form followed by a newline'] = $subject->toString() . "\n";
            $values['its string form in braces'] = '{' . $subject->toString() . '}';
        }

        return $values;
    }
}
