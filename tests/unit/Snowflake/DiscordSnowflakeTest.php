<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\DiscordSnowflakeFactory;
use Ramsey\Identifier\Snowflake\Epoch;
use Ramsey\Identifier\Snowflake\GenericSnowflake;
use Ramsey\Identifier\Snowflake\TwitterSnowflake;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class DiscordSnowflakeTest extends TestCase
{
    use SnowflakeComparisonTests;

    private const SNOWFLAKE_INT = 9223372036854775807;
    private const SNOWFLAKE_STRING = '9223372036854775807';

    private DiscordSnowflake $snowflakeWithInt;
    private DiscordSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new DiscordSnowflake(self::SNOWFLAKE_INT);
        $this->snowflakeWithString = new DiscordSnowflake(self::SNOWFLAKE_STRING);
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('invalidSnowflakesProvider')]
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new DiscordSnowflake($value);
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

        new DiscordSnowflake($value);
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
        $snowflake = new DiscordSnowflake($value);

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
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:'
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
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(DiscordSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:'
            . '{s:9:"snowflake";i:9223372036854775807;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(DiscordSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeFailsWhenSnowflakeIsAnEmptyString(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:{s:9:"snowflake";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidSnowflake(): void
    {
        $serialized = 'O:44:"Ramsey\\Identifier\\Snowflake\\DiscordSnowflake":1:{s:9:"snowflake";s:6:"foobar";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "foobar"');

        unserialize($serialized);
    }

    /**
     * @return array<string, DiscordSnowflake>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from an integer' => new DiscordSnowflake(self::SNOWFLAKE_INT),
            'from a string' => new DiscordSnowflake(self::SNOWFLAKE_STRING),
        ];
    }

    /**
     * @return array<string, TwitterSnowflake | GenericSnowflake>
     */
    protected static function equalValues(): array
    {
        return [
            'a TwitterSnowflake with the same number' => new TwitterSnowflake(self::SNOWFLAKE_STRING),
            'a GenericSnowflake with the same number' => new GenericSnowflake(self::SNOWFLAKE_INT, Epoch::Twitter),
        ];
    }

    /**
     * @return array<string, DiscordSnowflake | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser DiscordSnowflake' => new DiscordSnowflake(0),
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
            'a greater DiscordSnowflake' => new DiscordSnowflake('18446744073709551615'),
            'a greater snowflake string' => '9223372036854775808',
            'the largest snowflake string' => '18446744073709551615',
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    protected static function createSnowflake(int | string $value): DiscordSnowflake
    {
        return new DiscordSnowflake($value);
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
        $snowflake = (new DiscordSnowflakeFactory(12, 31))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertSame('2084-09-06 15:47:35.551', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertSame('2084-09-06 15:47:35.551', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeForMaxIdentifier(): void
    {
        $snowflake = new DiscordSnowflake('18446744073709551615');
        $dateTime = $snowflake->getDateTime();

        $this->assertSame('2154-05-15 07:35:11.103', $dateTime->format('Y-m-d H:i:s.v'));
    }
}
