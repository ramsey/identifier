<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake\DiscordSnowflake;
use Ramsey\Identifier\Snowflake\DiscordSnowflakeFactory;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class DiscordSnowflakeTest extends TestCase
{
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

    #[DataProvider('compareToProvider')]
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->snowflakeWithString->compareTo($other));
                $this->assertSame(0, $this->snowflakeWithInt->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->snowflakeWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->snowflakeWithInt->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->snowflakeWithString->compareTo($other));
                $this->assertLessThan(0, $this->snowflakeWithInt->compareTo($other));

                break;
            default:
                throw new Exception('Invalid comparison');
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public static function compareToProvider(): array
    {
        return [
            'with null' => [null, Comparison::GreaterThan],
            'with int' => [123, Comparison::GreaterThan],
            'with float' => [123.456, Comparison::GreaterThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with same string Snowflake' => [self::SNOWFLAKE_STRING, Comparison::Equal],
            'with same integer Snowflake' => [self::SNOWFLAKE_INT, Comparison::Equal],
            'with bool true' => [true, Comparison::GreaterThan],
            'with bool false' => [false, Comparison::GreaterThan],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                Comparison::LessThan,
            ],
            'with Stringable class returning Snowflake string' => [
                new class (self::SNOWFLAKE_STRING) {
                    public function __construct(private readonly string $snowflake)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->snowflake;
                    }
                },
                Comparison::Equal,
            ],
            'with Snowflake from string' => [new DiscordSnowflake(self::SNOWFLAKE_STRING), Comparison::Equal],
            'with Snowflake from int' => [new DiscordSnowflake(self::SNOWFLAKE_INT), Comparison::Equal],
            'with BinaryIdentifier' => [
                new MockBinaryIdentifier("\x7f\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->snowflakeWithString->compareTo([]);
    }

    #[DataProvider('equalsProvider')]
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->snowflakeWithString->equals($other));
                $this->assertTrue($this->snowflakeWithInt->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->snowflakeWithString->equals($other));
                $this->assertFalse($this->snowflakeWithInt->equals($other));

                break;
            default:
                throw new Exception('Invalid comparison');
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public static function equalsProvider(): array
    {
        return [
            'with null' => [null, Comparison::NotEqual],
            'with int' => [123, Comparison::NotEqual],
            'with float' => [123.456, Comparison::NotEqual],
            'with string' => ['foobar', Comparison::NotEqual],
            'with same string Snowflake' => [self::SNOWFLAKE_STRING, Comparison::Equal],
            'with same integer Snowflake' => [self::SNOWFLAKE_INT, Comparison::Equal],
            'with bool true' => [true, Comparison::NotEqual],
            'with bool false' => [false, Comparison::NotEqual],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                Comparison::NotEqual,
            ],
            'with Stringable class returning Snowflake string' => [
                new class (self::SNOWFLAKE_STRING) {
                    public function __construct(private readonly string $snowflake)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->snowflake;
                    }
                },
                Comparison::Equal,
            ],
            'with Snowflake from string' => [new DiscordSnowflake(self::SNOWFLAKE_STRING), Comparison::Equal],
            'with Snowflake from int' => [new DiscordSnowflake(self::SNOWFLAKE_INT), Comparison::Equal],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier' => [
                new MockBinaryIdentifier("\x7f\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
        ];
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
