<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Test\Identifier\ComparisonTests;

/**
 * Adds the comparison cases that every ULID shares
 */
trait UlidComparisonTests
{
    use ComparisonTests;

    /**
     * @return array<string, UntypedUuid>
     */
    protected static function sameSortKeyOfAnotherKind(): array
    {
        $values = [];

        foreach (static::comparisonSubjects() as $subject) {
            $values['an UntypedUuid with the same bytes'] = new UntypedUuid($subject->getSortKey());
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    protected static function notComparableValuesOfKind(): array
    {
        $values = [
            'a 26-character string starting with 8' => '80000000000000000000000000',
            'a 26-character string starting with Z' => 'ZZZZZZZZZZZZZZZZZZZZZZZZZZ',
        ];

        foreach (static::comparisonSubjects() as $subject) {
            $values['its bytes'] = $subject->toBytes();
            $values['its hexadecimal form'] = $subject->toHexadecimal();
            $values['its string form followed by another character'] = $subject->toString() . '-';
        }

        return $values;
    }
}
