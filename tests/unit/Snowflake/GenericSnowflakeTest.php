<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\GenericSnowflake;
use Ramsey\Identifier\Snowflake\GenericSnowflakeFactory;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class GenericSnowflakeTest extends TestCase
{
    use SnowflakeComparisonTests;

    private const int SNOWFLAKE_INT = 9223372036854775807;
    private const string SNOWFLAKE_STRING = '9223372036854775807';
    private const int EPOCH_OFFSET = 1662744255000;

    private GenericSnowflake $snowflakeWithInt;
    private GenericSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new GenericSnowflake(self::SNOWFLAKE_INT, self::EPOCH_OFFSET);
        $this->snowflakeWithString = new GenericSnowflake(self::SNOWFLAKE_STRING, self::EPOCH_OFFSET);
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('invalidSnowflakesProvider')]
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new GenericSnowflake($value, self::EPOCH_OFFSET);
    }

    /**
     * @return list<array{value: int | string}>
     */
    public static function invalidSnowflakesProvider(): array
    {
        return [
            ['value' => ''],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00"],
            ['value' => -1],
            ['value' => '-1'],

            // This value is out of bounds.
            ['value' => '18446744073709551616'],
        ];
    }

    /**
     * @param numeric-string $value
     */
    #[DataProvider('refusedStringsProvider')]
    public function testConstructorRefusesInvalidString(string $value): void
    {
        $this->expectException(InvalidArgument::class);

        new GenericSnowflake($value, 0);
    }

    /**
     * @return list<array{value: numeric-string}>
     */
    public static function refusedStringsProvider(): array
    {
        return [
            ['value' => '0123'],
            ['value' => '00'],
            ['value' => '018446744073709551616'],
            ['value' => '18446744073709551616'],
            ['value' => '99999999999999999999'],
        ];
    }

    /**
     * @param numeric-string $value
     */
    #[DataProvider('acceptedStringsProvider')]
    public function testConstructorAcceptsValidString(string $value): void
    {
        $snowflake = new GenericSnowflake($value, 0);

        $this->assertSame($value, $snowflake->toString());
        $this->assertSame(8, strlen($snowflake->toBytes()));
    }

    /**
     * @return list<array{value: numeric-string}>
     */
    public static function acceptedStringsProvider(): array
    {
        return [
            ['value' => '0'],
            ['value' => '123'],
            ['value' => '18446744073709551615'],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";s:11:"epochOffset";i:1662744255000;}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";i:9223372036854775807;s:11:"epochOffset";i:1662744255000;}';
        $serialized = serialize($this->snowflakeWithInt);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $this->snowflakeWithString);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $this->snowflakeWithInt);
    }

    public function testUnserializeForString(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";s:11:"epochOffset";i:1662744255000;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(GenericSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";i:9223372036854775807;s:11:"epochOffset";i:1662744255000;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(GenericSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeFailsWhenSnowflakeIsAnEmptyString(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";s:0:"";s:11:"epochOffset";i:1662744255000;}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['d:1.5;'], 'a float')]
    #[TestWith(['b:1;'], 'a boolean')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenSnowflakeIsNotAnIntegerOrString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":2:{s:9:"snowflake";%ss:11:"epochOffset";i:0;}',
            strlen(GenericSnowflake::class),
            GenericSnowflake::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenSnowflakeIsMissing(): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:11:"epochOffset";i:0;}',
            strlen(GenericSnowflake::class),
            GenericSnowflake::class,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['s:1:"0";'], 'a string of digits')]
    #[TestWith(['d:1.5;'], 'a float')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenEpochOffsetIsNotAnInteger(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":2:{s:9:"snowflake";i:1;s:11:"epochOffset";%s}',
            strlen(GenericSnowflake::class),
            GenericSnowflake::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer value for "epochOffset"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenEpochOffsetIsMissing(): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:9:"snowflake";i:1;}',
            strlen(GenericSnowflake::class),
            GenericSnowflake::class,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer value for "epochOffset"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidSnowflake(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\GenericSnowflake":2:'
            . '{s:9:"snowflake";s:6:"foobar";s:11:"epochOffset";i:1662744255000;}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "foobar"');

        unserialize($serialized);
    }

    /**
     * @return array<string, GenericSnowflake>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from an integer' => new GenericSnowflake(self::SNOWFLAKE_INT, self::EPOCH_OFFSET),
            'from a string' => new GenericSnowflake(self::SNOWFLAKE_STRING, self::EPOCH_OFFSET),
        ];
    }

    /**
     * @return array<string, GenericSnowflake | DiscordSnowflake>
     */
    protected static function equalValues(): array
    {
        return [
            'a GenericSnowflake with another epoch' => new GenericSnowflake(self::SNOWFLAKE_STRING, 0),
            'a DiscordSnowflake with the same number' => new DiscordSnowflake(self::SNOWFLAKE_STRING),
        ];
    }

    /**
     * @return array<string, GenericSnowflake | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser GenericSnowflake' => new GenericSnowflake(0, self::EPOCH_OFFSET),
            'a lesser snowflake string' => '9223372036854775806',
            'a lesser snowflake string with fewer digits' => '10',
            'a lesser snowflake string of one digit' => '9',
        ];
    }

    /**
     * @return array<string, GenericSnowflake | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater GenericSnowflake' => new GenericSnowflake('18446744073709551615', self::EPOCH_OFFSET),
            'a greater snowflake string' => '9223372036854775808',
            'the largest snowflake string' => '18446744073709551615',
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    protected static function createSnowflake(int | string $value): GenericSnowflake
    {
        return new GenericSnowflake($value, self::EPOCH_OFFSET);
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::SNOWFLAKE_STRING . '"', json_encode($this->snowflakeWithString));
        $this->assertSame('"' . self::SNOWFLAKE_STRING . '"', json_encode($this->snowflakeWithInt));
    }

    public function testToString(): void
    {
        $this->assertSame(self::SNOWFLAKE_STRING, $this->snowflakeWithString->toString());
        $this->assertSame(self::SNOWFLAKE_STRING, $this->snowflakeWithInt->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame("\x7f\xff\xff\xff\xff\xff\xff\xff", $this->snowflakeWithString->toBytes());
        $this->assertSame("\x7f\xff\xff\xff\xff\xff\xff\xff", $this->snowflakeWithInt->toBytes());
    }

    public function testGetSortKey(): void
    {
        $this->assertSame("\x7f\xff\xff\xff\xff\xff\xff\xff", $this->snowflakeWithString->getSortKey());
        $this->assertSame("\x7f\xff\xff\xff\xff\xff\xff\xff", $this->snowflakeWithInt->getSortKey());
        $this->assertSame(8, strlen($this->snowflakeWithInt->getSortKey()));
    }

    public function testGetSortKeyForZeroAndMaximumValues(): void
    {
        $zero = new GenericSnowflake(0, self::EPOCH_OFFSET);
        $max = new GenericSnowflake('18446744073709551615', self::EPOCH_OFFSET);

        $this->assertSame("\x00\x00\x00\x00\x00\x00\x00\x00", $zero->getSortKey());
        $this->assertSame("\xff\xff\xff\xff\xff\xff\xff\xff", $max->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame('7fffffffffffffff', $this->snowflakeWithString->toHexadecimal());
        $this->assertSame('7fffffffffffffff', $this->snowflakeWithInt->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $this->assertSame(9223372036854775807, $this->snowflakeWithString->toInteger());
        $this->assertSame(9223372036854775807, $this->snowflakeWithInt->toInteger());
    }

    public function testGetDateTime(): void
    {
        $dateTime = new DateTimeImmutable();
        $snowflake = (new GenericSnowflakeFactory(123, 0))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertSame('2092-05-16 09:11:50.551', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertSame('2092-05-16 09:11:50.551', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeForMaxIdentifier(): void
    {
        $snowflake = new GenericSnowflake('18446744073709551615', self::EPOCH_OFFSET);
        $dateTime = $snowflake->getDateTime();

        $this->assertSame('2162-01-22 00:59:26.103', $dateTime->format('Y-m-d H:i:s.v'));
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('unrepresentableDateTimeProvider')]
    public function testGetDateTimeThrowsExceptionWhenNotRepresentable(int | string $value, int $epochOffset): void
    {
        $snowflake = new GenericSnowflake($value, $epochOffset);

        $this->expectException(NotConvertible::class);
        $this->expectExceptionMessage('The date-time of the snowflake cannot be represented');

        $snowflake->getDateTime();
    }

    /**
     * @return list<array{value: int<0, max> | numeric-string, epochOffset: int}>
     */
    public static function unrepresentableDateTimeProvider(): array
    {
        return [
            ['value' => '9223372036854775807', 'epochOffset' => PHP_INT_MAX],
        ];
    }

    #[DataProvider('millisecondsBeforeEpochProvider')]
    public function testGetDateTimeBeforeUnixEpoch(int $epochOffset, string $expected): void
    {
        $snowflake = new GenericSnowflake(0, $epochOffset);

        $this->assertSame($expected, $snowflake->getDateTime()->format('Y-m-d H:i:s.v'));
    }

    /**
     * @return list<array{epochOffset: int, expected: string}>
     */
    public static function millisecondsBeforeEpochProvider(): array
    {
        return [
            ['epochOffset' => -1, 'expected' => '1969-12-31 23:59:59.999'],
            ['epochOffset' => -500, 'expected' => '1969-12-31 23:59:59.500'],
            ['epochOffset' => -999, 'expected' => '1969-12-31 23:59:59.001'],
            ['epochOffset' => -1000, 'expected' => '1969-12-31 23:59:59.000'],
            ['epochOffset' => -1001, 'expected' => '1969-12-31 23:59:58.999'],
            ['epochOffset' => -1500, 'expected' => '1969-12-31 23:59:58.500'],
        ];
    }

    public function testGetDateTimeForMinimumOffset(): void
    {
        $snowflake = new GenericSnowflake(0, PHP_INT_MIN);

        $this->assertSame('-292275055-05-16 16:47:04.192', $snowflake->getDateTime()->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeForMaximumIdentifierAndMaximumOffset(): void
    {
        $snowflake = new GenericSnowflake('18446744073709551615', PHP_INT_MAX);

        $this->assertSame('292279133-12-30 14:48:06.910', $snowflake->getDateTime()->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeAtTheLargestMillisecondAnIntegerCanHold(): void
    {
        $snowflake = new GenericSnowflake(1 << 22, PHP_INT_MAX - 1);

        $this->assertSame('9223372036854775.807', $snowflake->getDateTime()->format('U.v'));
    }
}
