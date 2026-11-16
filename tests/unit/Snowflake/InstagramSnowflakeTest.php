<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\InstagramSnowflake;
use Ramsey\Identifier\Snowflake\InstagramSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class InstagramSnowflakeTest extends TestCase
{
    use SnowflakeComparisonTests;

    private const SNOWFLAKE_INT = 9223372036854775807;
    private const SNOWFLAKE_STRING = '9223372036854775807';

    private InstagramSnowflake $snowflakeWithInt;
    private InstagramSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new InstagramSnowflake(self::SNOWFLAKE_INT);
        $this->snowflakeWithString = new InstagramSnowflake(self::SNOWFLAKE_STRING);
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('invalidSnowflakesProvider')]
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new InstagramSnowflake($value);
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

        new InstagramSnowflake($value);
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
        $snowflake = new InstagramSnowflake($value);

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
        $expected = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:'
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
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:'
            . '{s:9:"snowflake";s:19:"9223372036854775807";}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:'
            . '{s:9:"snowflake";i:9223372036854775807;}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeFailsWhenSnowflakeIsAnEmptyString(): void
    {
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidSnowflake(): void
    {
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";s:6:"foobar";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "foobar"');

        unserialize($serialized);
    }

    /**
     * @return array<string, InstagramSnowflake>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from an integer' => new InstagramSnowflake(self::SNOWFLAKE_INT),
            'from a string' => new InstagramSnowflake(self::SNOWFLAKE_STRING),
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
     * @return array<string, InstagramSnowflake | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser InstagramSnowflake' => new InstagramSnowflake(0),
            'a lesser snowflake string' => '9223372036854775806',
            'a lesser snowflake string with fewer digits' => '10',
            'a lesser snowflake string of one digit' => '9',
        ];
    }

    /**
     * @return array<string, InstagramSnowflake | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater InstagramSnowflake' => new InstagramSnowflake('18446744073709551615'),
            'a greater snowflake string' => '9223372036854775808',
            'the largest snowflake string' => '18446744073709551615',
        ];
    }

    /**
     * @param int<0, max> | numeric-string $value
     */
    protected static function createSnowflake(int | string $value): InstagramSnowflake
    {
        return new InstagramSnowflake($value);
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
        $snowflake = (new InstagramSnowflakeFactory(123))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertSame('2046-06-27 17:00:49.496', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertSame('2046-06-27 17:00:49.496', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeForMaxIdentifier(): void
    {
        $snowflake = new InstagramSnowflake('18446744073709551615');
        $dateTime = $snowflake->getDateTime();

        $this->assertSame('2081-04-30 12:54:37.272', $dateTime->format('Y-m-d H:i:s.v'));
    }
}
