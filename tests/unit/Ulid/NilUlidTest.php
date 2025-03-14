<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class NilUlidTest extends TestCase
{
    private const NIL_ULID = '00000000000000000000000000';

    private Ulid\NilUlid $nilUlid;
    private Ulid\NilUlid $nilUlidWithString;
    private Ulid\NilUlid $nilUlidWithHex;
    private Ulid\NilUlid $nilUlidWithBytes;

    protected function setUp(): void
    {
        $this->nilUlid = new Ulid\NilUlid();
        $this->nilUlidWithString = new Ulid\NilUlid(self::NIL_ULID);
        $this->nilUlidWithHex = new Ulid\NilUlid('00000000000000000000000000000000');
        $this->nilUlidWithBytes = new Ulid\NilUlid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }

    #[DataProvider('invalidUlidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Nil ULID: "%s"', $value));

        new Ulid\NilUlid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUlidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 25 characters:
            ['value' => '0000000000000000000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These 16 bytes don't form a standard ULID:
            ['value' => 'foobarbazquux123'],

            // This is out of bounds for a ULID:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],

            // These contain invalid characters:
            ['value' => '7ZZZZZILOUZZZZZZZZZZZZZZZZ'],
            ['value' => 'fffffffffffffffffffffffffffffffg'],
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],

            // Valid Max ULID:
            ['value' => '7ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // Valid dULID:
            ['value' => '01BX5ZZKBKACTAV9WEVGEMMVS0'],
            ['value' => '015f4bffcd735334ada78edc1d4a6f20'],
            ['value' => "\x01\x5f\x4b\xff\xcd\x73\x53\x34\xad\xa7\x8e\xdc\x1d\x4a\x6f\x20"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:26:"00000000000000000000000000";}';
        $serialized = serialize($this->nilUlidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:32:"00000000000000000000000000000000";}';
        $serialized = serialize($this->nilUlidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $serialized = serialize($this->nilUlidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithString);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithHex);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:26:"00000000000000000000000000";}';
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:32:"00000000000000000000000000000000";}';
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    #[DataProvider('compareToProvider')]
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->nilUlid->compareTo($other));
                $this->assertSame(0, $this->nilUlidWithString->compareTo($other));
                $this->assertSame(0, $this->nilUlidWithHex->compareTo($other));
                $this->assertSame(0, $this->nilUlidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->nilUlid->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUlidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUlidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->nilUlidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->nilUlid->compareTo($other));
                $this->assertLessThan(0, $this->nilUlidWithString->compareTo($other));
                $this->assertLessThan(0, $this->nilUlidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->nilUlidWithBytes->compareTo($other));

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
            'with int' => [123, Comparison::LessThan],
            'with float' => [123.456, Comparison::LessThan],
            'with string' => ['foobar', Comparison::LessThan],
            'with string Nil ULID' => [self::NIL_ULID, Comparison::Equal],
            'with string Max ULID' => ['7FFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::LessThan],
            'with string Max ULID all lower' => ['7fffffffffffffffffffffffff', Comparison::LessThan],
            'with hex Nil ULID' => ['00000000000000000000000000000000', Comparison::Equal],
            'with bytes Nil ULID' => [
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                Comparison::Equal,
            ],
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
                new class {
                    public function __toString(): string
                    {
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), Comparison::Equal],
            'with MaxUlid' => [new Ulid\MaxUlid(), Comparison::LessThan],
            'with NilUlid from string' => [new Ulid\NilUlid(self::NIL_ULID), Comparison::Equal],
            'with NilUlid from hex' => [new Ulid\NilUlid('00000000000000000000000000000000'), Comparison::Equal],
            'with NilUlid from bytes' => [
                new Ulid\NilUlid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with NilUuid' => [new NilUuid(), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->nilUlid->compareTo([]);
    }

    #[DataProvider('equalsProvider')]
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->nilUlid->equals($other));
                $this->assertTrue($this->nilUlidWithString->equals($other));
                $this->assertTrue($this->nilUlidWithHex->equals($other));
                $this->assertTrue($this->nilUlidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->nilUlid->equals($other));
                $this->assertFalse($this->nilUlidWithString->equals($other));
                $this->assertFalse($this->nilUlidWithHex->equals($other));
                $this->assertFalse($this->nilUlidWithBytes->equals($other));

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
            'with string Nil ULID' => [self::NIL_ULID, Comparison::Equal],
            'with string Max ULID' => ['7FFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::NotEqual],
            'with string Max ULID all lower' => ['7fffffffffffffffffffffffff', Comparison::NotEqual],
            'with hex Nil ULID' => ['00000000000000000000000000000000', Comparison::Equal],
            'with bytes Nil ULID' => [
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                Comparison::Equal,
            ],
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
                new class {
                    public function __toString(): string
                    {
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), Comparison::Equal],
            'with MaxUlid' => [new Ulid\MaxUlid(), Comparison::NotEqual],
            'with NilUlid from string' => [new Ulid\NilUlid(self::NIL_ULID), Comparison::Equal],
            'with NilUlid from hex' => [new Ulid\NilUlid('00000000000000000000000000000000'), Comparison::Equal],
            'with NilUlid from bytes' => [
                new Ulid\NilUlid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                Comparison::Equal,
            ],
            'with NilUuid' => [new NilUuid(), Comparison::Equal],
        ];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlid));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithString));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithHex));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::NIL_ULID, $this->nilUlid->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithString->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithHex->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

        $this->assertSame($bytes, $this->nilUlid->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithString->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithHex->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = '00000000000000000000000000000000';

        $this->assertSame($hex, $this->nilUlid->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithString->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = 0;

        $this->assertSame($int, $this->nilUlid->toInteger());
        $this->assertSame($int, $this->nilUlidWithString->toInteger());
        $this->assertSame($int, $this->nilUlidWithHex->toInteger());
        $this->assertSame($int, $this->nilUlidWithBytes->toInteger());
    }

    public function testToUuid(): void
    {
        $uuid = $this->nilUlid->toUuid();

        $this->assertInstanceOf(NilUuid::class, $uuid);
        $this->assertTrue($this->nilUlid->equals($uuid));
    }

    public function testToUuidV7(): void
    {
        $expectedBytes = "\x00\x00\x00\x00\x00\x00\x70\x00\x80\x00\x00\x00\x00\x00\x00\x00";
        $uuid = $this->nilUlid->toUuidV7();

        $this->assertSame($expectedBytes, $uuid->toBytes());
        $this->assertFalse($this->nilUlid->equals($uuid));
    }
}
