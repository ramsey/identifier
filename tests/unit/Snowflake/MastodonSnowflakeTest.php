<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\MastodonSnowflake;
use Ramsey\Identifier\Snowflake\MastodonSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class MastodonSnowflakeTest extends TestCase
{
    use SnowflakeComparisonTests;

    private const SNOWFLAKE_INT = 9223372036854775807;
    private const SNOWFLAKE_STRING = '9223372036854775807';

    private MastodonSnowflake $snowflakeWithInt;
    private MastodonSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new MastodonSnowflake(self::SNOWFLAKE_INT);
        $this->snowflakeWithString = new MastodonSnowflake(self::SNOWFLAKE_STRING);
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('invalidSnowflakesProvider')]
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new MastodonSnowflake($value);
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

        new MastodonSnowflake($value);
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
        $snowflake = new MastodonSnowflake($value);

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
        $expected = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:'
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
        $serialized = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(MastodonSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:'
            . '{s:9:"snowflake";i:9223372036854775807;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(MastodonSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeFailsWhenSnowflakeIsAnEmptyString(): void
    {
        $serialized = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:{s:9:"snowflake";s:0:"";}';

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
            strlen(MastodonSnowflake::class),
            MastodonSnowflake::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenSnowflakeIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(MastodonSnowflake::class), MastodonSnowflake::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have an integer or string value for "snowflake"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidSnowflake(): void
    {
        $serialized = 'O:45:"Ramsey\\Identifier\\Snowflake\\MastodonSnowflake":1:{s:9:"snowflake";s:6:"foobar";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "foobar"');

        unserialize($serialized);
    }

    /**
     * @return array<string, MastodonSnowflake>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from an integer' => new MastodonSnowflake(self::SNOWFLAKE_INT),
            'from a string' => new MastodonSnowflake(self::SNOWFLAKE_STRING),
        ];
    }

    /**
     * @return array<string, DiscordSnowflake>
     */
    protected static function equalValues(): array
    {
        return [
            'a DiscordSnowflake with the same number' => new DiscordSnowflake(self::SNOWFLAKE_STRING),
        ];
    }

    /**
     * @return array<string, MastodonSnowflake | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser MastodonSnowflake' => new MastodonSnowflake(0),
            'a lesser snowflake string' => '9223372036854775806',
            'a lesser snowflake string with fewer digits' => '10',
            'a lesser snowflake string of one digit' => '9',
        ];
    }

    /**
     * @return array<string, MastodonSnowflake | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater MastodonSnowflake' => new MastodonSnowflake('18446744073709551615'),
            'a greater snowflake string' => '9223372036854775808',
            'the largest snowflake string' => '18446744073709551615',
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    protected static function createSnowflake(int | string $value): MastodonSnowflake
    {
        return new MastodonSnowflake($value);
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
        $snowflake = (new MastodonSnowflakeFactory('a_database_table'))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertSame('6429-10-17 02:45:55.327', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertSame('6429-10-17 02:45:55.327', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeForMaxIdentifier(): void
    {
        $snowflake = new MastodonSnowflake('18446744073709551615');
        $dateTime = $snowflake->getDateTime();

        $this->assertSame('10889-08-02 05:31:50.655', $dateTime->format('Y-m-d H:i:s.v'));
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('knownMastodonSnowflakesProvider')]
    public function testKnownMastodonSnowflakes(int | string $value, string $expectedDate): void
    {
        $snowflake = new MastodonSnowflake($value);

        $this->assertSame($expectedDate, $snowflake->getDateTime()->format('Y-m-d H:i:s.v'));
    }

    /**
     * @return array<array{value: int | string, expectedDate: string}>
     */
    public static function knownMastodonSnowflakesProvider(): array
    {
        return [
            [
                // Mastodon Snowflake integer maximum value.
                'value' => 9223372036854775807,
                'expectedDate' => '6429-10-17 02:45:55.327',
            ],
            [
                // Mastodon Snowflake string maximum value.
                'value' => '18446744073709551615',
                'expectedDate' => '10889-08-02 05:31:50.655',
            ],
            [
                // https://mastodon.social/@Mastodon/98850828346879853
                'value' => 98850828346879853,
                'expectedDate' => '2017-10-18 16:25:38.398',
            ],
            [
                // https://phpc.social/@ramsey/114690712746586764
                'value' => 114690712746586764,
                'expectedDate' => '2025-06-16 02:36:33.227',
            ],
        ];
    }
}
