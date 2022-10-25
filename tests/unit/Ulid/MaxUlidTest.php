<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Test\Identifier\Comparison;
use Ramsey\Test\Identifier\MockBinaryIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtolower;
use function unserialize;

class MaxUlidTest extends TestCase
{
    private const MAX_ULID = '7ZZZZZZZZZZZZZZZZZZZZZZZZZ';

    private Ulid\MaxUlid $maxUlid;
    private Ulid\MaxUlid $maxUlidWithString;
    private Ulid\MaxUlid $maxUlidWithHex;
    private Ulid\MaxUlid $maxUlidWithBytes;

    protected function setUp(): void
    {
        $this->maxUlid = new Ulid\MaxUlid();
        $this->maxUlidWithString = new Ulid\MaxUlid(self::MAX_ULID);
        $this->maxUlidWithHex = new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff');
        $this->maxUlidWithBytes = new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @dataProvider invalidUlidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Max ULID: "%s"', $value));

        new Ulid\MaxUlid($value);
    }

    /**
     * @return array<array{value: string, messageValue?: string}>
     */
    public function invalidUlidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 25 characters:
            ['value' => '7ZZZZZZZZZZZZZZZZZZZZZZZZ'],

            // This is 31 characters:
            ['value' => 'ffffffffffffffffffffffffffffff'],

            // This is 15 bytes:
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // These 16 bytes don't form a standard ULID:
            ['value' => 'foobarbazquux123'],

            // This is out of bounds for a ULID:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],

            // These contain invalid characters:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            ['value' => 'fffffffffffffffffffffffffffffffg'],
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],

            // Valid Nil ULID:
            ['value' => '00000000000000000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // Valid dULID:
            ['value' => '01BX5ZZKBKACTAV9WEVGEMMVS0'],
            ['value' => '015f4bffcd735334ada78edc1d4a6f20'],
            ['value' => "\x01\x5f\x4b\xff\xcd\x73\x53\x34\xad\xa7\x8e\xdc\x1d\x4a\x6f\x20"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:26:"7ZZZZZZZZZZZZZZZZZZZZZZZZZ";}';
        $serialized = serialize($this->maxUlidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $serialized = serialize($this->maxUlidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $serialized = serialize($this->maxUlidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithString);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithHex);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:26:"7ZZZZZZZZZZZZZZZZZZZZZZZZZ";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertSame(0, $this->maxUlid->compareTo($other));
                $this->assertSame(0, $this->maxUlidWithString->compareTo($other));
                $this->assertSame(0, $this->maxUlidWithHex->compareTo($other));
                $this->assertSame(0, $this->maxUlidWithBytes->compareTo($other));

                break;
            case Comparison::GreaterThan:
                $this->assertGreaterThan(0, $this->maxUlid->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUlidWithString->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUlidWithHex->compareTo($other));
                $this->assertGreaterThan(0, $this->maxUlidWithBytes->compareTo($other));

                break;
            case Comparison::LessThan:
                $this->assertLessThan(0, $this->maxUlid->compareTo($other));
                $this->assertLessThan(0, $this->maxUlidWithString->compareTo($other));
                $this->assertLessThan(0, $this->maxUlidWithHex->compareTo($other));
                $this->assertLessThan(0, $this->maxUlidWithBytes->compareTo($other));

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
            'with string Nil ULID' => ['00000000000000000000000000', Comparison::GreaterThan],
            'with string Max ULID' => [self::MAX_ULID, Comparison::Equal],
            'with string Max ULID all lower' => [strtolower(self::MAX_ULID), Comparison::Equal],
            'with hex Max ULID' => ['ffffffffffffffffffffffffffffffff', Comparison::Equal],
            'with hex Max ULID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::Equal],
            'with bytes Max ULID' => [
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                Comparison::Equal,
            ],
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
            'with Stringable class returning ULID bytes' => [
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), Comparison::GreaterThan],
            'with MaxUlid' => [new Ulid\MaxUlid(), Comparison::Equal],
            'with MaxUlid from string' => [new Ulid\MaxUlid(self::MAX_ULID), Comparison::Equal],
            'with MaxUlid from hex' => [new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff'), Comparison::Equal],
            'with MaxUlid from bytes' => [
                new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with MaxUuid' => [new MaxUuid(), Comparison::Equal],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparable::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->maxUlid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, Comparison $comparison): void
    {
        switch ($comparison) {
            case Comparison::Equal:
                $this->assertTrue($this->maxUlid->equals($other));
                $this->assertTrue($this->maxUlidWithString->equals($other));
                $this->assertTrue($this->maxUlidWithHex->equals($other));
                $this->assertTrue($this->maxUlidWithBytes->equals($other));

                break;
            case Comparison::NotEqual:
                $this->assertFalse($this->maxUlid->equals($other));
                $this->assertFalse($this->maxUlidWithString->equals($other));
                $this->assertFalse($this->maxUlidWithHex->equals($other));
                $this->assertFalse($this->maxUlidWithBytes->equals($other));

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
            'with string Nil ULID' => ['00000000000000000000000000', Comparison::NotEqual],
            'with string Max ULID' => [self::MAX_ULID, Comparison::Equal],
            'with string Max ULID all lower' => [strtolower(self::MAX_ULID), Comparison::Equal],
            'with hex Max ULID' => ['ffffffffffffffffffffffffffffffff', Comparison::Equal],
            'with hex Max ULID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', Comparison::Equal],
            'with bytes Max ULID' => [
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
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
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                Comparison::Equal,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), Comparison::NotEqual],
            'with MaxUlid' => [new Ulid\MaxUlid(), Comparison::Equal],
            'with MaxUlid from string' => [new Ulid\MaxUlid(self::MAX_ULID), Comparison::Equal],
            'with MaxUlid from hex' => [new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff'), Comparison::Equal],
            'with MaxUlid from bytes' => [
                new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with array' => [[], Comparison::NotEqual],
            'with BinaryIdentifier class' => [
                new MockBinaryIdentifier("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                Comparison::Equal,
            ],
            'with MaxUuid' => [new MaxUuid(), Comparison::Equal],
        ];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlid));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithString));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithHex));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::MAX_ULID, $this->maxUlid->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithString->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithHex->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUlid->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithString->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithHex->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = 'ffffffffffffffffffffffffffffffff';

        $this->assertSame($hex, $this->maxUlid->toHexadecimal());
        $this->assertSame($hex, $this->maxUlidWithString->toHexadecimal());
        $this->assertSame($hex, $this->maxUlidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->maxUlidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '340282366920938463463374607431768211455';

        $this->assertSame($int, $this->maxUlid->toInteger());
        $this->assertSame($int, $this->maxUlidWithString->toInteger());
        $this->assertSame($int, $this->maxUlidWithHex->toInteger());
        $this->assertSame($int, $this->maxUlidWithBytes->toInteger());
    }

    /**
     * @dataProvider valuesForUppercaseConversionTestProvider
     */
    public function testUppercaseConversion(string $value, string $expected): void
    {
        $ulid = new Ulid\MaxUlid($value);

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
                'value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'expected' => self::MAX_ULID,
            ],
            [
                'value' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
                'expected' => self::MAX_ULID,
            ],
            [
                'value' => 'ffffffffffffffffffffffffffffffff',
                'expected' => self::MAX_ULID,
            ],
        ];
    }

    public function testToUuid(): void
    {
        $uuid = $this->maxUlid->toUuid();

        $this->assertInstanceOf(MaxUuid::class, $uuid);
        $this->assertTrue($this->maxUlid->equals($uuid));
    }

    public function testToUuidV7(): void
    {
        $expectedBytes = "\xff\xff\xff\xff\xff\xff\x7f\xff\xbf\xff\xff\xff\xff\xff\xff\xff";
        $uuid = $this->maxUlid->toUuidV7();

        $this->assertSame($expectedBytes, $uuid->toBytes());
        $this->assertFalse($this->maxUlid->equals($uuid));
    }
}
