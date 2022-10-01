<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Ulid as StaticUlid;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
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
        $this->expectException(InvalidArgumentException::class);
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUlid(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->ulidWithString->compareTo($other));
        $this->assertSame($expected, $this->ulidWithHex->compareTo($other));
        $this->assertSame($expected, $this->ulidWithBytes->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 1],
            'with int' => [123, -1],
            'with float' => [123.456, -1],
            'with string' => ['foobar', -1],
            'with string Nil ULID' => [StaticUlid::nil()->toString(), 1],
            'with same string ULID' => [self::ULID_STRING, 0],
            'with same string ULID all lowercase' => [strtoupper(self::ULID_STRING), 0],
            'with same hex ULID' => [self::ULID_HEX, 0],
            'with same hex ULID all caps' => [strtoupper(self::ULID_HEX), 0],
            'with same bytes ULID' => [self::ULID_BYTES, 0],
            'with string Max ULID' => [StaticUlid::max()->toString(), -1],
            'with string Max ULID all lowercase' => [strtolower(StaticUlid::max()->toString()), -1],
            'with bool true' => [true, -1],
            'with bool false' => [false, 1],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                -1,
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
                0,
            ],
            'with NilUlid' => [new NilUlid(), 1],
            'with Ulid from string' => [new Ulid(self::ULID_STRING), 0],
            'with Ulid from hex' => [new Ulid(self::ULID_HEX), 0],
            'with Ulid from bytes' => [new Ulid(self::ULID_BYTES), 0],
            'with MaxUlid' => [new MaxUlid(), -1],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->ulidWithString->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->ulidWithString->equals($other));
        $this->assertSame($expected, $this->ulidWithHex->equals($other));
        $this->assertSame($expected, $this->ulidWithBytes->equals($other));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public function equalsProvider(): array
    {
        return [
            'with null' => [null, false],
            'with int' => [123, false],
            'with float' => [123.456, false],
            'with string' => ['foobar', false],
            'with string Nil ULID' => [StaticUlid::nil()->toString(), false],
            'with same string ULID' => [self::ULID_STRING, true],
            'with same string ULID all lowercase' => [strtolower(self::ULID_STRING), true],
            'with same hex ULID' => [self::ULID_HEX, true],
            'with same hex ULID all caps' => [strtoupper(self::ULID_HEX), true],
            'with same bytes ULID' => [self::ULID_BYTES, true],
            'with string Max ULID' => [StaticUlid::max()->toString(), false],
            'with string Max ULID all lowercase' => [strtolower(StaticUlid::max()->toString()), false],
            'with bool true' => [true, false],
            'with bool false' => [false, false],
            'with Stringable class' => [
                new class {
                    public function __toString(): string
                    {
                        return 'foobar';
                    }
                },
                false,
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
                true,
            ],
            'with NilUlid' => [new NilUlid(), false],
            'with Ulid from string' => [new Ulid(self::ULID_STRING),true],
            'with Ulid from hex' => [new Ulid(self::ULID_HEX), true],
            'with Ulid from bytes' => [new Ulid(self::ULID_BYTES), true],
            'with MaxUlid' => [new MaxUlid(), false],
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
}
