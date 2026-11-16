<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Identifier\Identifier;
use Identifier\StringIdentifier;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\GenericSnowflake;
use Ramsey\Identifier\Snowflake\InstagramSnowflake;
use Ramsey\Identifier\Snowflake\MastodonSnowflake;
use Ramsey\Identifier\Snowflake\TwitterSnowflake;
use Ramsey\Identifier\Ulid as UlidInterface;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;
use Throwable;
use stdClass;

use function array_filter;
use function array_values;
use function bin2hex;
use function count;
use function get_debug_type;
use function is_object;
use function sprintf;
use function strcmp;
use function var_export;

/**
 * Tests the comparison rules that every identifier must follow
 *
 * Each test checks one group of rules from the Identifier specification across
 * a sample that has an instance of every identifier class, identifiers that
 * share a sort key, and identifiers from another implementation.
 */
class ComparisonRulesTest extends TestCase
{
    private const SHARED_UUID = '017f22e2-79b0-7cc3-98c4-dc0c0c07398f';
    private const SHARED_HEX = '017f22e279b07cc398c4dc0c0c07398f';
    private const SHARED_BYTES = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f";
    private const SHARED_FIRST_EIGHT_BYTES_AS_INTEGER = 107843272179743939;

    public function testAnIdentifierIsEqualToItself(): void
    {
        $checked = 0;

        foreach (self::receivers() as $a) {
            $label = self::describe($a);

            self::assertSame(0, $a->compareTo($a), $label);
            self::assertTrue($a->equals($a), $label);

            $checked++;
        }

        self::assertGreaterThan(20, $checked);
    }

    public function testCompareToIsTheSameInBothDirections(): void
    {
        $receivers = self::receivers();
        $labels = self::describeAll($receivers);
        $checked = 0;

        foreach ($receivers as $i => $a) {
            foreach ($receivers as $j => $b) {
                $forward = self::sign($a, $b);
                $backward = self::sign($b, $a);

                self::assertSame(
                    $forward === null,
                    $backward === null,
                    sprintf('%s and %s: only one direction throws', $labels[$i], $labels[$j]),
                );

                if ($forward === null || $backward === null) {
                    continue;
                }

                self::assertSame(
                    -$forward,
                    $backward,
                    sprintf('%s and %s: the directions disagree', $labels[$i], $labels[$j]),
                );

                $checked++;
            }
        }

        self::assertGreaterThan(500, $checked);
    }

    public function testCompareToIsTransitive(): void
    {
        $receivers = self::receivers();
        $labels = self::describeAll($receivers);
        $checked = 0;

        foreach ($receivers as $i => $a) {
            foreach ($receivers as $j => $b) {
                $ab = self::sign($a, $b);

                if ($ab === null) {
                    continue;
                }

                foreach ($receivers as $k => $c) {
                    $bc = self::sign($b, $c);
                    $ac = self::sign($a, $c);

                    if ($bc === null || $ac === null || $ab !== $bc) {
                        continue;
                    }

                    self::assertSame(
                        $ab,
                        $ac,
                        sprintf('%s, %s, %s: not transitive', $labels[$i], $labels[$j], $labels[$k]),
                    );

                    $checked++;
                }
            }
        }

        self::assertGreaterThan(1000, $checked);
    }

    public function testSortKeysMatchCompareTo(): void
    {
        $sample = self::sample();
        $labels = self::describeAll($sample);
        $checked = 0;

        foreach (self::receivers() as $a) {
            foreach ($sample as $j => $b) {
                $sign = self::sign($a, $b);

                self::assertNotNull($sign, sprintf('%s: %s is not comparable', self::describe($a), $labels[$j]));
                self::assertSame(
                    strcmp($a->getSortKey(), $b->getSortKey()) <=> 0,
                    $sign,
                    sprintf('%s and %s: compareTo() disagrees with the sort keys', self::describe($a), $labels[$j]),
                );

                $checked++;
            }
        }

        self::assertGreaterThan(500, $checked);
    }

    public function testEqualsIsTheSameInBothDirections(): void
    {
        $receivers = self::receivers();
        $labels = self::describeAll($receivers);
        $equalPairs = 0;

        foreach ($receivers as $i => $a) {
            foreach ($receivers as $j => $b) {
                if (!$a->equals($b)) {
                    continue;
                }

                self::assertTrue($b->equals($a), sprintf('%s equals %s, not the reverse', $labels[$i], $labels[$j]));

                $equalPairs++;
            }
        }

        // Every identifier is equal to itself, and some are equal to others.
        self::assertGreaterThan(count($receivers), $equalPairs);
    }

    public function testEqualsIsTransitive(): void
    {
        $receivers = self::receivers();
        $labels = self::describeAll($receivers);
        $checked = 0;

        foreach ($receivers as $i => $a) {
            foreach ($receivers as $j => $b) {
                if (!$a->equals($b)) {
                    continue;
                }

                foreach ($receivers as $k => $c) {
                    if (!$b->equals($c)) {
                        continue;
                    }

                    self::assertTrue(
                        $a->equals($c),
                        sprintf('%s, %s, %s: equals() is not transitive', $labels[$i], $labels[$j], $labels[$k]),
                    );

                    $checked++;
                }
            }
        }

        self::assertGreaterThan(count($receivers), $checked);
    }

    public function testEqualsImpliesCompareToIsZero(): void
    {
        $values = self::everyValue();
        $checked = 0;
        $equal = 0;

        foreach (self::receivers() as $a) {
            foreach ($values as $value) {
                $isEqual = $a->equals($value);
                $sign = self::sign($a, $value);
                $label = sprintf('%s and %s', self::describe($a), self::describeValue($value));

                if ($isEqual) {
                    self::assertSame(0, $sign, $label . ': equals() is true, compareTo() is not zero');

                    $equal++;
                }

                if ($sign !== 0) {
                    self::assertFalse($isEqual, $label . ': compareTo() is not zero, equals() is true');
                }

                $checked++;
            }
        }

        self::assertGreaterThan(1000, $checked);
        self::assertGreaterThan(50, $equal);
    }

    public function testEqualsThrowsNothing(): void
    {
        $values = self::everyValue();
        $checked = 0;

        foreach (self::receivers() as $a) {
            foreach ($values as $value) {
                try {
                    $a->equals($value);
                } catch (Throwable $exception) {
                    self::fail(sprintf(
                        '%s and %s: equals() threw %s',
                        self::describe($a),
                        self::describeValue($value),
                        $exception::class,
                    ));
                }

                $checked++;
            }
        }

        $this->addToAssertionCount($checked);

        self::assertGreaterThan(1000, $checked);
    }

    public function testResultsDoNotChangeBetweenCalls(): void
    {
        $values = self::everyValue();
        $checked = 0;

        foreach (self::receivers() as $a) {
            foreach ($values as $value) {
                $label = sprintf('%s and %s', self::describe($a), self::describeValue($value));

                self::assertSame(self::sign($a, $value), self::sign($a, $value), $label . ': compareTo() changed');
                self::assertSame($a->equals($value), $a->equals($value), $label . ': equals() changed');

                $checked++;
            }
        }

        self::assertGreaterThan(1000, $checked);
    }

    public function testRulesForValuesThatAreNotIdentifiers(): void
    {
        $receivers = self::receivers();
        $values = self::everyValue();
        $labels = self::describeAll($receivers);
        $checked = 0;
        $skipped = 0;

        foreach ($receivers as $i => $a) {
            foreach ($receivers as $j => $b) {
                $ab = self::sign($a, $b);

                if ($ab === null) {
                    $skipped += count($values);

                    continue;
                }

                foreach ($values as $value) {
                    $bv = self::sign($b, $value);
                    $av = self::sign($a, $value);

                    if ($bv === null || $av === null) {
                        $skipped++;

                        continue;
                    }

                    $label = sprintf('%s, %s, %s', $labels[$i], $labels[$j], self::describeValue($value));

                    if ($ab < 0 && $bv < 0) {
                        self::assertLessThan(0, $av, $label . ': less than, less than');
                    }

                    if ($ab > 0 && $bv > 0) {
                        self::assertGreaterThan(0, $av, $label . ': greater than, greater than');
                    }

                    if ($ab === 0) {
                        self::assertSame($av, $bv, $label . ': a and b are equal, value differs');
                    }

                    if ($bv === 0) {
                        self::assertSame($ab, $av, $label . ': b and value are equal, a differs');
                    }

                    $checked++;
                }
            }
        }

        self::assertGreaterThan(10000, $checked);
        self::assertGreaterThan(0, $skipped);
    }

    public function testAStringIsReadByOneKindOnly(): void
    {
        $snowflakes = 0;
        $others = 0;

        foreach (self::receivers() as $a) {
            $label = self::describe($a);

            if ($a instanceof Snowflake) {
                self::assertNotComparable($a, '12345678901234567890123456', $label);

                $snowflakes++;
            }

            if ($a instanceof Uuid || $a instanceof UlidInterface) {
                self::assertNotComparable($a, '1234567890123456', $label);

                $others++;
            }

            if ($a instanceof StringIdentifier) {
                self::assertSame(0, $a->compareTo($a->toString()), $label . ': its own string form');
                self::assertTrue($a->equals($a->toString()), $label . ': its own string form');
            }
        }

        self::assertGreaterThan(5, $snowflakes);
        self::assertGreaterThan(15, $others);
    }

    /**
     * Returns the sign of the result of compareTo(), or null when it throws
     * NotComparable
     *
     * @return -1 | 0 | 1 | null
     */
    private static function sign(Identifier $a, mixed $b): ?int
    {
        try {
            return $a->compareTo($b) <=> 0;
        } catch (NotComparable) {
            return null;
        }
    }

    private static function assertNotComparable(Identifier $a, string $value, string $label): void
    {
        try {
            $a->compareTo($value);
        } catch (NotComparable) {
            self::assertFalse($a->equals($value), $label . ': equals() is true for "' . $value . '"');

            return;
        }

        self::fail($label . ': compareTo() accepted "' . $value . '"');
    }

    /**
     * Returns every identifier that has compareTo() and equals() of its own
     *
     * @return list<Identifier>
     */
    private static function receivers(): array
    {
        return array_values(array_filter(
            self::sample(),
            static fn (Identifier $identifier): bool => !$identifier instanceof MockIdentifier,
        ));
    }

    /**
     * Returns the sample, then the values that are not identifiers
     *
     * @return list<mixed>
     */
    private static function everyValue(): array
    {
        return [...self::sample(), ...self::valuesThatAreNotIdentifiers()];
    }

    /**
     * Returns an instance of every identifier class and the identifiers that
     * share sort keys on purpose
     *
     * @return list<Identifier>
     */
    private static function sample(): array
    {
        return [
            // The same 16 bytes in four kinds of identifier.
            new UuidV7(self::SHARED_UUID),
            new Ulid(self::SHARED_HEX),
            new UntypedUuid(self::SHARED_UUID),
            new MicrosoftGuid(self::SHARED_UUID),

            new UuidV1('ebd42a4a-7a6c-11ef-a5b7-0242ac120002'),
            new UuidV2('000001f5-5cde-21ea-8400-0242ac130003'),
            new UuidV3('6fa459ea-ee8a-3ca4-894e-db77e160355e'),
            new UuidV4('9a3e4e4d-8f55-4c5c-8e6c-3d1f9a1b2c3d'),
            new UuidV5('886313e1-3b8a-5372-9b90-0c9aee199e5d'),
            new UuidV6('1ef7a6c0-ebd4-6a4a-a5b7-0242ac120002'),
            new UuidV8('00000000-0000-8000-8000-000000000001'),
            new NonstandardUuid('017f22e2-79b0-7cc3-f8c4-dc0c0c07398f'),
            new NilUuid(),
            new MaxUuid(),
            new Ulid(self::SHARED_BYTES),
            new Ulid('01FWHE4YDGFK1SHH6W1G60EECF'),
            new NilUlid(),
            new MaxUlid(),

            // The same number in two kinds of snowflake.
            new TwitterSnowflake(1234567890123456789),
            new DiscordSnowflake(1234567890123456789),
            new InstagramSnowflake(5),
            new MastodonSnowflake(5),
            new GenericSnowflake(0, 0),
            new GenericSnowflake(9, 0),
            new GenericSnowflake(10, 0),
            new DiscordSnowflake('18446744073709551615'),

            // The 8 bytes of this snowflake are the first 8 bytes of a UUID.
            new GenericSnowflake(self::SHARED_FIRST_EIGHT_BYTES_AS_INTEGER, 0),

            // Identifiers from another implementation.
            new MockIdentifier(self::SHARED_BYTES),
            new MockIdentifier("\x00\x00\x00\x00\x00\x00\x00\x05"),
        ];
    }

    /**
     * Returns values that are not identifiers
     *
     * @return list<mixed>
     */
    private static function valuesThatAreNotIdentifiers(): array
    {
        $values = [];

        foreach (self::sample() as $identifier) {
            if ($identifier instanceof StringIdentifier) {
                $values[] = $identifier->toString();
            }
        }

        return [
            ...$values,
            'foobar',
            '',
            '1234567890123456',
            '12345678901234567890123456',
            null,
            0,
            1.5,
            true,
            [],
            new stdClass(),
        ];
    }

    private static function describe(Identifier $identifier): string
    {
        return sprintf('%s(%s)', get_debug_type($identifier), bin2hex($identifier->getSortKey()));
    }

    /**
     * @param list<Identifier> $identifiers
     *
     * @return list<string>
     */
    private static function describeAll(array $identifiers): array
    {
        $labels = [];

        foreach ($identifiers as $identifier) {
            $labels[] = self::describe($identifier);
        }

        return $labels;
    }

    private static function describeValue(mixed $value): string
    {
        if ($value instanceof Identifier) {
            return self::describe($value);
        }

        if (is_object($value) || $value === []) {
            return get_debug_type($value);
        }

        return var_export($value, true);
    }
}
