<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Identifier\Identifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Uuid;
use RuntimeException;
use stdClass;

use function get_debug_type;
use function is_string;
use function max;
use function min;
use function sprintf;
use function strcmp;
use function strtolower;
use function strtoupper;

/**
 * Tests compareTo() and equals() for an identifier class
 *
 * A test case using this trait gives the identifier under test, created in
 * each form its constructor accepts, and the values of its own kind that sort
 * before and after it. The trait adds the cases every identifier shares.
 */
trait ComparisonTests
{
    /**
     * Returns the identifier under test, created in each form its constructor
     * accepts, all with the same value
     *
     * @return array<string, Snowflake | Ulid | Uuid>
     */
    abstract protected static function comparisonSubjects(): array;

    /**
     * Returns values that are equal to the identifier under test, other than
     * the ones every identifier shares
     *
     * @return array<string, mixed>
     */
    abstract protected static function equalValues(): array;

    /**
     * Returns identifiers of the same kind, and strings in the accepted form,
     * that sort before the identifier under test
     *
     * @return array<string, Identifier | string>
     */
    abstract protected static function lesserValues(): array;

    /**
     * Returns identifiers of the same kind, and strings in the accepted form,
     * that sort after the identifier under test
     *
     * @return array<string, Identifier | string>
     */
    abstract protected static function greaterValues(): array;

    /**
     * Returns identifiers of another kind with the same sort key as the
     * identifier under test
     *
     * @return array<string, Identifier>
     */
    protected static function sameSortKeyOfAnotherKind(): array
    {
        return [];
    }

    /**
     * Returns values that only this kind of identifier refuses to compare
     *
     * @return array<string, mixed>
     */
    protected static function notComparableValuesOfKind(): array
    {
        return [];
    }

    #[DataProvider('equalValuesProvider')]
    public function testCompareToReturnsZeroAndEqualsReturnsTrue(Snowflake | Ulid | Uuid $subject, mixed $other): void
    {
        $this->assertSame(0, $subject->compareTo($other));
        $this->assertTrue($subject->equals($other));

        if (self::isPackageIdentifier($other)) {
            $this->assertSame(0, $other->compareTo($subject));
            $this->assertTrue($other->equals($subject));
        }
    }

    /**
     * @return iterable<string, array{Snowflake | Ulid | Uuid, mixed}>
     */
    public static function equalValuesProvider(): iterable
    {
        foreach (static::comparisonSubjects() as $subjectName => $subject) {
            $values = [
                'the same object' => $subject,
                'its string form in lowercase' => strtolower($subject->toString()),
                'its string form in uppercase' => strtoupper($subject->toString()),
                'a Stringable returning its string form' => new MockStringable($subject->toString()),
            ];

            foreach (static::comparisonSubjects() as $otherName => $other) {
                $values['another instance ' . $otherName] = $other;
            }

            foreach ($values + static::equalValues() as $name => $value) {
                yield $subjectName . ' with ' . $name => [$subject, $value];
            }
        }
    }

    #[DataProvider('orderedValuesProvider')]
    public function testCompareToOrdersBySortKey(
        Snowflake | Ulid | Uuid $subject,
        Identifier | string $other,
        int $expected,
    ): void {
        if ($other instanceof Identifier) {
            $this->assertSame($expected, self::sign(strcmp($subject->getSortKey(), $other->getSortKey())));
        }

        $this->assertSame($expected, self::sign($subject->compareTo($other)));
        $this->assertFalse($subject->equals($other));

        if (self::isPackageIdentifier($other)) {
            $this->assertSame(-$expected, self::sign($other->compareTo($subject)));
            $this->assertFalse($other->equals($subject));
        }
    }

    /**
     * @return iterable<string, array{Snowflake | Ulid | Uuid, Identifier | string, int}>
     */
    public static function orderedValuesProvider(): iterable
    {
        foreach (static::comparisonSubjects() as $subjectName => $subject) {
            $lowerKey = "\x00";
            $higherKey = $subject->getSortKey() . "\xff";

            $lesser = static::lesserValues() + [
                'a MockIdentifier with a lower sort key' => new MockIdentifier($lowerKey),
                'a MockBinaryIdentifier with a lower sort key' => new MockBinaryIdentifier($lowerKey),
            ];

            $greater = static::greaterValues() + [
                'a MockIdentifier with a higher sort key' => new MockIdentifier($higherKey),
                'a MockBinaryIdentifier with a higher sort key' => new MockBinaryIdentifier($higherKey),
            ];

            foreach ($lesser as $name => $value) {
                yield $subjectName . ' with ' . $name => [$subject, $value, 1];
            }

            foreach ($greater as $name => $value) {
                yield $subjectName . ' with ' . $name => [$subject, $value, -1];
            }
        }
    }

    #[DataProvider('sameSortKeyNotEqualProvider')]
    public function testCompareToReturnsZeroAndEqualsReturnsFalse(
        Snowflake | Ulid | Uuid $subject,
        Identifier $other,
    ): void {
        $this->assertSame(0, $subject->compareTo($other));
        $this->assertFalse($subject->equals($other));

        if (self::isPackageIdentifier($other)) {
            $this->assertSame(0, $other->compareTo($subject));
            $this->assertFalse($other->equals($subject));
        }
    }

    /**
     * @return iterable<string, array{Snowflake | Ulid | Uuid, Identifier}>
     */
    public static function sameSortKeyNotEqualProvider(): iterable
    {
        foreach (static::comparisonSubjects() as $subjectName => $subject) {
            $values = static::sameSortKeyOfAnotherKind() + [
                'a MockIdentifier with the same sort key' => new MockIdentifier($subject->getSortKey()),
                'a MockBinaryIdentifier with the same sort key' => new MockBinaryIdentifier($subject->getSortKey()),
            ];

            foreach ($values as $name => $value) {
                yield $subjectName . ' with ' . $name => [$subject, $value];
            }
        }
    }

    #[DataProvider('notComparableValuesProvider')]
    public function testCompareToThrowsAndEqualsReturnsFalse(
        Snowflake | Ulid | Uuid $subject,
        mixed $other,
        string $expectedMessage,
    ): void {
        $this->assertFalse($subject->equals($other));

        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage($expectedMessage);

        $subject->compareTo($other);
    }

    /**
     * @return iterable<string, array{Snowflake | Ulid | Uuid, mixed, string}>
     */
    public static function notComparableValuesProvider(): iterable
    {
        $values = [
            'null' => [null, self::typeMessage('null')],
            'an integer' => [123, self::typeMessage('int')],
            'a float' => [123.456, self::typeMessage('float')],
            'true' => [true, self::typeMessage('bool')],
            'false' => [false, self::typeMessage('bool')],
            'an array' => [[], self::typeMessage('array')],
            'a stdClass' => [new stdClass(), self::typeMessage(stdClass::class)],
            'the string foobar' => ['foobar', self::stringMessage('foobar')],
            'an empty string' => ['', self::stringMessage('')],
            'a Stringable returning foobar' => [new MockStringable('foobar'), self::stringMessage('foobar')],
            'a Stringable that throws' => [
                new MockStringable(new RuntimeException('Unable to convert to a string')),
                self::typeMessage(MockStringable::class),
            ],
        ];

        foreach (static::notComparableValuesOfKind() as $name => $value) {
            $values[$name] = [
                $value,
                is_string($value) ? self::stringMessage($value) : self::typeMessage(get_debug_type($value)),
            ];
        }

        foreach (static::comparisonSubjects() as $subjectName => $subject) {
            foreach ($values as $name => [$value, $message]) {
                yield $subjectName . ' with ' . $name => [$subject, $value, $message];
            }
        }
    }

    public function testCompareToKeepsTheExceptionFromAStringable(): void
    {
        $exception = new RuntimeException('Unable to convert to a string');

        foreach (static::comparisonSubjects() as $subject) {
            $caught = null;

            try {
                $subject->compareTo(new MockStringable($exception));
            } catch (NotComparable $notComparable) {
                $caught = $notComparable;
            }

            $this->assertNotNull($caught);
            $this->assertSame($exception, $caught->getPrevious());
        }
    }

    /**
     * @phpstan-assert-if-true Snowflake | Ulid | Uuid $value
     */
    private static function isPackageIdentifier(mixed $value): bool
    {
        return $value instanceof Snowflake || $value instanceof Ulid || $value instanceof Uuid;
    }

    private static function stringMessage(string $value): string
    {
        return sprintf('Comparison with the string "%s" is not supported', $value);
    }

    private static function typeMessage(string $type): string
    {
        return sprintf('Comparison with values of type "%s" is not supported', $type);
    }

    private static function sign(int $value): int
    {
        return max(-1, min(1, $value));
    }
}
