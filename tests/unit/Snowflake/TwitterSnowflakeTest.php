<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\Epoch;
use Ramsey\Identifier\Snowflake\GenericSnowflake;
use Ramsey\Identifier\Snowflake\TwitterSnowflake;
use Ramsey\Identifier\Snowflake\TwitterSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class TwitterSnowflakeTest extends TestCase
{
    use SnowflakeComparisonTests;

    private const SNOWFLAKE_INT = 9223372036854775807;
    private const SNOWFLAKE_STRING = '9223372036854775807';

    private TwitterSnowflake $snowflakeWithInt;
    private TwitterSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new TwitterSnowflake(self::SNOWFLAKE_INT);
        $this->snowflakeWithString = new TwitterSnowflake(self::SNOWFLAKE_STRING);
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('invalidSnowflakesProvider')]
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new TwitterSnowflake($value);
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
        ];
    }

    /**
     * @param numeric-string $value
     */
    #[DataProvider('refusedStringsProvider')]
    public function testConstructorRefusesInvalidString(string $value): void
    {
        $this->expectException(InvalidArgument::class);

        new TwitterSnowflake($value);
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
        $snowflake = new TwitterSnowflake($value);

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
            ['value' => '9223372036854775807'],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
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
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(TwitterSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:'
            . '{s:9:"snowflake";i:9223372036854775807;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(TwitterSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeFailsWhenSnowflakeIsAnEmptyString(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:{s:9:"snowflake";s:0:"";}';

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
            'O:%d:"%s":1:{s:9:"snowflake";%s}',
            strlen(TwitterSnowflake::class),
            TwitterSnowflake::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenSnowflakeIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(TwitterSnowflake::class), TwitterSnowflake::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidSnowflake(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\TwitterSnowflake":1:{s:9:"snowflake";s:6:"foobar";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "foobar"');

        unserialize($serialized);
    }

    /**
     * @return array<string, TwitterSnowflake>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from an integer' => new TwitterSnowflake(self::SNOWFLAKE_INT),
            'from a string' => new TwitterSnowflake(self::SNOWFLAKE_STRING),
        ];
    }

    /**
     * @return array<string, DiscordSnowflake | GenericSnowflake>
     */
    protected static function equalValues(): array
    {
        return [
            'a DiscordSnowflake with the same number' => new DiscordSnowflake(self::SNOWFLAKE_STRING),
            'a GenericSnowflake with the same number' => new GenericSnowflake(self::SNOWFLAKE_INT, Epoch::Discord),
        ];
    }

    /**
     * @return array<string, TwitterSnowflake | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser TwitterSnowflake' => new TwitterSnowflake(0),
            'a lesser snowflake string' => '9223372036854775806',
            'a lesser snowflake string with fewer digits' => '10',
            'a lesser snowflake string of one digit' => '9',
        ];
    }

    /**
     * @return array<string, DiscordSnowflake | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater DiscordSnowflake' => new DiscordSnowflake('9223372036854775808'),
            'a greater snowflake string' => '9223372036854775808',
            'the largest snowflake string' => '18446744073709551615',
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    protected static function createSnowflake(int | string $value): TwitterSnowflake
    {
        return new TwitterSnowflake($value);
    }

    public function testCompareToAcceptsAStringAboveTheLimitOfATwitterSnowflake(): void
    {
        $snowflake = new TwitterSnowflake(self::SNOWFLAKE_STRING);

        $this->assertLessThan(0, $snowflake->compareTo('9223372036854775808'));
        $this->assertFalse($snowflake->equals('9223372036854775808'));
        $this->assertLessThan(0, $snowflake->compareTo('18446744073709551615'));
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
        $snowflake = (new TwitterSnowflakeFactory(123))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertSame('2080-07-10 17:30:30.208', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertSame('2080-07-10 17:30:30.208', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testIdentifierGreaterThanMax(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Twitter Snowflakes are limited to a 41-bit timestamp; '
            . 'the timestamp in "9223372036854775808" is greater than 41-bits',
        );

        new TwitterSnowflake('9223372036854775808');
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('knownTwitterSnowflakesProvider')]
    public function testKnownTwitterSnowflakes(int | string $value, string $expectedDate): void
    {
        $snowflake = new TwitterSnowflake($value);

        $this->assertSame($expectedDate, $snowflake->getDateTime()->format('Y-m-d H:i:s.v'));
    }

    /**
     * @return array<array{value: int | string, expectedDate: string}>
     */
    public static function knownTwitterSnowflakesProvider(): array
    {
        return [
            [
                // Twitter Snowflake maximum value.
                'value' => 0x7fffffffffffffff,
                'expectedDate' => '2080-07-10 17:30:30.208',
            ],
            [
                // https://twitter.com/wikipedia/status/1541815603606036480
                'value' => 1541815603606036480,
                'expectedDate' => '2022-06-28 16:07:40.105',
            ],
        ];
    }
}
