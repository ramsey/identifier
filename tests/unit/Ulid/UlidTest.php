<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid\DefaultUlidFactory;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtolower;
use function strtoupper;
use function unserialize;

class UlidTest extends TestCase
{
    private const ULID_STRING = '01FWHE4YDGFK1SHH6W1G60EECF';
    private const ULID_HEX = '017f22e279b07cc398c4dc0c0c07398f';
    private const ULID_BYTES = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f";

    private Ulid $ulidWithString;
    private Ulid $ulidWithHex;
    private Ulid $ulidWithBytes;

    protected function setUp(): void
    {
        $this->ulidWithString = new Ulid(self::ULID_STRING);
        $this->ulidWithHex = new Ulid(self::ULID_HEX);
        $this->ulidWithBytes = new Ulid(self::ULID_BYTES);
    }

    /**
     * @dataProvider invalidUlidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid ULID: "%s"', $value));

        new Ulid($value);
    }

    /**
     * @return array<array{value: string, messageValue?: string}>
     */
    public function invalidUlidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 25 characters:
            ['value' => '0000000000000000000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These contain invalid characters:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '0000000000000000000000000000000g'],
            ['value' => '7ZZZZZZILOUZZZZZZZZZZZZZZZ'],

            // Out of bounds ULIDs:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            ['value' => '80000000000000000000000000'],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"01FWHE4YDGFK1SHH6W1G60EECF";}';
        $serialized = serialize($this->ulidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForStringWithExcludedCharacters(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"7Z1Z1Z1Z1Z1Z0Z0Z0ZZZZZZZZZ";}';

        $ulid = new Ulid('7Z1ZIZiZLZlZ0ZOZoZZZZZZZZZ');
        $serialized = serialize($ulid);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $serialized = serialize($this->ulidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $serialized = serialize($this->ulidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithString);
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithHex);
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"01FWHE4YDGFK1SHH6W1G60EECF";}';
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"7ZZZZZZILOUZZZZZZZZZZZZZZZ";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: "7ZZZZZZILOUZZZZZZZZZZZZZZZ');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->ulidWithString->compareTo($other));
                $this->assertSame(0, $this->ulidWithHex->compareTo($other));
                $this->assertSame(0, $this->ulidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->ulidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->ulidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->ulidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->ulidWithString->compareTo($other));
                $this->assertLessThan(0, $this->ulidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->ulidWithBytes->compareTo($other));

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
        $factory = new DefaultUlidFactory();

        return [
            'with null' => [null, Comparison::GreaterThan],
            'with int' => [123, Comparison::LessThan],
            'with float' => [123.456, Comparison::LessThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with string Nil ULID' => [$factory->nil()->toString(), Comparison::GreaterThan],
            'with same string ULID' => [self::ULID_STRING, Comparison::Equal],
            'with same string ULID all lowercase' => [strtoupper(self::ULID_STRING), Comparison::Equal],
            'with same hex ULID' => [self::ULID_HEX, Comparison::Equal],
            'with same hex ULID all caps' => [strtoupper(self::ULID_HEX), Comparison::Equal],
            'with same bytes ULID' => [self::ULID_BYTES, Comparison::Equal],
            'with string Max ULID' => [$factory->max()->toString(), Comparison::LessThan],
            'with string Max ULID all lowercase' => [strtolower($factory->max()->toString()), Comparison::LessThan],
            'with bool true' => [true, Comparison::LessThan],
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
            'with Stringable class returning ULID bytes' => [
                new class (self::ULID_BYTES) {
                    public function __construct(private readonly string $ulidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->ulidBytes;
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new NilUlid(), Comparison::GreaterThan],
            'with Ulid from string' => [new Ulid(self::ULID_STRING), Comparison::Equal],
            'with Ulid from hex' => [new Ulid(self::ULID_HEX), Comparison::Equal],
            'with Ulid from bytes' => [new Ulid(self::ULID_BYTES), Comparison::Equal],
            'with MaxUlid' => [new MaxUlid(), Comparison::LessThan],
            'with excluded symbols string' => ['0Ifwhe4ydgfkishh6wLg6Oeecf', Comparison::Equal],
            'with excluded symbols Ulid' => [new Ulid('0Ifwhe4ydgfkishh6wLg6Oeecf'), Comparison::Equal],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::ULID_BYTES), Comparison::Equal],
            'with UuidV7' => [new UuidV7(self::ULID_BYTES), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->ulidWithString->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->ulidWithString->equals($other));
                $this->assertTrue($this->ulidWithHex->equals($other));
                $this->assertTrue($this->ulidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->ulidWithString->equals($other));
                $this->assertFalse($this->ulidWithHex->equals($other));
                $this->assertFalse($this->ulidWithBytes->equals($other));

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
        $factory = new DefaultUlidFactory();

        return [
            'with null' => [null, Comparison::NotEqual],
            'with int' => [123, Comparison::NotEqual],
            'with float' => [123.456, Comparison::NotEqual],
            'with string' => ['foobar', Comparison::NotEqual],
            'with string Nil ULID' => [$factory->nil()->toString(), Comparison::NotEqual],
            'with same string ULID' => [self::ULID_STRING, Comparison::Equal],
            'with same string ULID all lowercase' => [strtolower(self::ULID_STRING), Comparison::Equal],
            'with same hex ULID' => [self::ULID_HEX, Comparison::Equal],
            'with same hex ULID all caps' => [strtoupper(self::ULID_HEX), Comparison::Equal],
            'with same bytes ULID' => [self::ULID_BYTES, Comparison::Equal],
            'with string Max ULID' => [$factory->max()->toString(), Comparison::NotEqual],
            'with string Max ULID all lowercase' => [strtolower($factory->max()->toString()), Comparison::NotEqual],
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
            'with Stringable class returning ULID bytes' => [
                new class (self::ULID_BYTES) {
                    public function __construct(private readonly string $ulidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->ulidBytes;
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new NilUlid(), Comparison::NotEqual],
            'with Ulid from string' => [new Ulid(self::ULID_STRING), Comparison::Equal],
            'with Ulid from hex' => [new Ulid(self::ULID_HEX), Comparison::Equal],
            'with Ulid from bytes' => [new Ulid(self::ULID_BYTES), Comparison::Equal],
            'with MaxUlid' => [new MaxUlid(), Comparison::NotEqual],
            'with excluded symbols string' => ['0Ifwhe4ydgfkishh6wLg6Oeecf', Comparison::Equal],
            'with excluded symbols Ulid' => [new Ulid('0Ifwhe4ydgfkishh6wLg6Oeecf'), Comparison::Equal],
            'with BinaryIdentifier class' => [new MockBinaryIdentifier(self::ULID_BYTES), Comparison::Equal],
            'with UuidV7' => [new UuidV7(self::ULID_BYTES), Comparison::Equal],
        ];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithString));
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithHex));
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::ULID_STRING, $this->ulidWithString->toString());
        $this->assertSame(self::ULID_STRING, $this->ulidWithHex->toString());
        $this->assertSame(self::ULID_STRING, $this->ulidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::ULID_BYTES, $this->ulidWithString->toBytes());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithHex->toBytes());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::ULID_HEX, $this->ulidWithString->toHexadecimal());
        $this->assertSame(self::ULID_HEX, $this->ulidWithHex->toHexadecimal());
        $this->assertSame(self::ULID_HEX, $this->ulidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '1989357241971137676463954034883508623';

        $this->assertSame($int, $this->ulidWithString->toInteger());
        $this->assertSame($int, $this->ulidWithHex->toInteger());
        $this->assertSame($int, $this->ulidWithBytes->toInteger());
    }

    /**
     * @dataProvider valuesForUppercaseConversionTestProvider
     */
    public function testUppercaseConversion(string $value, string $expected): void
    {
        $ulid = new Ulid($value);

        $this->assertTrue($ulid->equals($value));
        $this->assertSame($expected, $ulid->toString());
    }

    /**
     * @return array<array{value: string, expected: string}>
     */
    public function valuesForUppercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => '01fwhe4ydgfk1shh6w1g60eecf',
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
            [
                'value' => '017f22e279b07cc398c4dc0c0c07398f',
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
            [
                'value' => "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f",
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
        ];
    }

    public function testGetDateTimeFromStringUlid(): void
    {
        $dateTime = $this->ulidWithString->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromHexUlid(): void
    {
        $dateTime = $this->ulidWithHex->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromBytesUlid(): void
    {
        $dateTime = $this->ulidWithBytes->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testConversionOfExcludedSymbols(): void
    {
        $ulid = new Ulid('7Z1ZIZiZLZlZ0ZOZoZZZZZZZZZ');

        $this->assertSame('7Z1Z1Z1Z1Z1Z0Z0Z0ZZZZZZZZZ', $ulid->toString());
    }

    public function testToHexadecimalAndBytesFromLowercaseUlid(): void
    {
        $ulid = new Ulid('01gg5qhjkp09jx275b1x4jj25f');

        $this->assertSame('01GG5QHJKP09JX275B1X4JJ25F', $ulid->toString());
        $this->assertSame('01840b78ca760265d11cab0f492908af', $ulid->toHexadecimal());
        $this->assertSame("\x01\x84\x0b\x78\xca\x76\x02\x65\xd1\x1c\xab\x0f\x49\x29\x08\xaf", $ulid->toBytes());
    }
}
