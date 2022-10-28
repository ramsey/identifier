<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake\InstagramSnowflake;
use Ramsey\Identifier\Snowflake\InstagramSnowflakeFactory;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

use const PHP_INT_SIZE;

class InstagramSnowflakeTest extends TestCase
{
    private const SNOWFLAKE_INT = 2147483647;
    private const SNOWFLAKE_STRING = '2147483647';

    private InstagramSnowflake $snowflakeWithInt;
    private InstagramSnowflake $snowflakeWithString;

    protected function setUp(): void
    {
        $this->snowflakeWithInt = new InstagramSnowflake(self::SNOWFLAKE_INT);
        $this->snowflakeWithString = new InstagramSnowflake(self::SNOWFLAKE_STRING);
    }

    /**
     * @param int | numeric-string $value
     *
     * @dataProvider invalidSnowflakesProvider
     */
    public function testConstructorThrowsExceptionForInvalidSnowflake(int | string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $value));

        new InstagramSnowflake($value);
    }

    /**
     * @return array<array{value: int | string}>
     */
    public function invalidSnowflakesProvider(): array
    {
        return [
            ['value' => ''],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00"],
            ['value' => -1],
            ['value' => '-1'],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";s:10:"2147483647";}';
        $serialized = serialize($this->snowflakeWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForInt(): void
    {
        $expected = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";s:10:"2147483647";}';
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
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";s:10:"2147483647";}';
        $snowflake = unserialize($serialized);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame(self::SNOWFLAKE_STRING, (string) $snowflake);
    }

    public function testUnserializeForInt(): void
    {
        $serialized = 'O:46:"Ramsey\\Identifier\\Snowflake\\InstagramSnowflake":1:{s:9:"snowflake";i:2147483647;}';
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
     * @dataProvider compareToProvider
     */
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
                $this->markAsRisky();

                break;
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public function compareToProvider(): array
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
            'with Snowflake from string' => [new InstagramSnowflake(self::SNOWFLAKE_STRING), Comparison::Equal],
            'with Snowflake from int' => [new InstagramSnowflake(self::SNOWFLAKE_INT), Comparison::Equal],
            'with BinaryIdentifier' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x7f\xff\xff\xff"),
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

    /**
     * @dataProvider equalsProvider
     */
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
                $this->markAsRisky();

                break;
        }
    }

    /**
     * @return array<string, array{mixed, Comparison}>
     */
    public function equalsProvider(): array
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
            'with Snowflake from string' => [new InstagramSnowflake(self::SNOWFLAKE_STRING), Comparison::Equal],
            'with Snowflake from int' => [new InstagramSnowflake(self::SNOWFLAKE_INT), Comparison::Equal],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x7f\xff\xff\xff"),
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
        $this->assertSame("\x00\x00\x00\x00\x7f\xff\xff\xff", $this->snowflakeWithString->toBytes());
        $this->assertSame("\x00\x00\x00\x00\x7f\xff\xff\xff", $this->snowflakeWithInt->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame('000000007fffffff', $this->snowflakeWithString->toHexadecimal());
        $this->assertSame('000000007fffffff', $this->snowflakeWithInt->toHexadecimal());
    }

    public function testToInteger(): void
    {
        if (PHP_INT_SIZE >= 8) {
            $this->assertSame(2147483647, $this->snowflakeWithString->toInteger());
            $this->assertSame(2147483647, $this->snowflakeWithInt->toInteger());
        } else {
            $this->assertSame('2147483647', $this->snowflakeWithString->toInteger());
            $this->assertSame('2147483647', $this->snowflakeWithInt->toInteger());
        }
    }

    public function testGetDateTime(): void
    {
        $dateTime = new DateTimeImmutable();
        $snowflake = (new InstagramSnowflakeFactory(123))->createFromDateTime($dateTime);
        $snowflakeDate = $snowflake->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $snowflakeDate);
        $this->assertNotSame($dateTime, $snowflakeDate);
        $this->assertSame($dateTime->format('Y-m-d H:i:s.v'), $snowflakeDate->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromStringSnowflake(): void
    {
        $dateTime = $this->snowflakeWithString->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2011-08-24 21:07:01.976', $dateTime->format('Y-m-d H:i:s.v'));
    }

    public function testGetDateTimeFromIntSnowflake(): void
    {
        $dateTime = $this->snowflakeWithInt->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2011-08-24 21:07:01.976', $dateTime->format('Y-m-d H:i:s.v'));
    }
}
