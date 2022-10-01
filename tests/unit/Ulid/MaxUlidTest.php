<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Ulid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strtoupper;
use function unserialize;

class MaxUlidTest extends TestCase
{
    private Ulid\MaxUlid $maxUlid;
    private Ulid\MaxUlid $maxUlidWithString;
    private Ulid\MaxUlid $maxUlidWithHex;
    private Ulid\MaxUlid $maxUlidWithBytes;

    protected function setUp(): void
    {
        $this->maxUlid = new Ulid\MaxUlid();
        $this->maxUlidWithString = new Ulid\MaxUlid('7ZZZZZZZZZZZZZZZZZZZZZZZZZ');
        $this->maxUlidWithHex = new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff');
        $this->maxUlidWithBytes = new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @dataProvider invalidUlidsProvider
     */
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
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
        $this->assertSame(Ulid::max()->toString(), (string) $this->maxUlid);
        $this->assertSame(Ulid::max()->toString(), (string) $this->maxUlidWithString);
        $this->assertSame(Ulid::max()->toString(), (string) $this->maxUlidWithHex);
        $this->assertSame(Ulid::max()->toString(), (string) $this->maxUlidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:26:"7ZZZZZZZZZZZZZZZZZZZZZZZZZ";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(Ulid::max()->toString(), (string) $maxUlid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(Ulid::max()->toString(), (string) $maxUlid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(Ulid::max()->toString(), (string) $maxUlid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Max ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->maxUlid->compareTo($other));
        $this->assertSame($expected, $this->maxUlidWithString->compareTo($other));
        $this->assertSame($expected, $this->maxUlidWithHex->compareTo($other));
        $this->assertSame($expected, $this->maxUlidWithBytes->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 1],
            'with int' => [123, 1],
            'with float' => [123.456, 1],
            'with string' => ['foobar', -1],
            'with string Nil ULID' => [Ulid::nil()->toString(), 1],
            'with string Max ULID' => [Ulid::max()->toString(), 0],
            'with string Max ULID all caps' => [strtoupper(Ulid::max()->toString()), 0],
            'with hex Max ULID' => ['ffffffffffffffffffffffffffffffff', 0],
            'with hex Max ULID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', 0],
            'with bytes Max ULID' => ["\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff", 0],
            'with bool true' => [true, 1],
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
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                0,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), 1],
            'with MaxUlid' => [new Ulid\MaxUlid(), 0],
            'with MaxUlid from string' => [new Ulid\MaxUlid('7ZZZZZZZZZZZZZZZZZZZZZZZZZ'), 0],
            'with MaxUlid from hex' => [new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff'), 0],
            'with MaxUlid from bytes' => [
                new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                0,
            ],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->maxUlid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->maxUlid->equals($other));
        $this->assertSame($expected, $this->maxUlidWithString->equals($other));
        $this->assertSame($expected, $this->maxUlidWithHex->equals($other));
        $this->assertSame($expected, $this->maxUlidWithBytes->equals($other));
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
            'with string Nil ULID' => [Ulid::nil()->toString(), false],
            'with string Max ULID' => [Ulid::max()->toString(), true],
            'with string Max ULID all caps' => [strtoupper(Ulid::max()->toString()), true],
            'with hex Max ULID' => ['ffffffffffffffffffffffffffffffff', true],
            'with hex Max ULID all caps' => ['FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', true],
            'with bytes Max ULID' => ["\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff", true],
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
                new class {
                    public function __toString(): string
                    {
                        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
                    }
                },
                true,
            ],
            'with NilUlid' => [new Ulid\NilUlid(), false],
            'with MaxUlid' => [new Ulid\MaxUlid(), true],
            'with MaxUlid from string' => [new Ulid\MaxUlid('7ZZZZZZZZZZZZZZZZZZZZZZZZZ'), true],
            'with MaxUlid from hex' => [new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff'), true],
            'with MaxUlid from bytes' => [
                new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
                true,
            ],
            'with array' => [[], false],
        ];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . Ulid::max()->toString() . '"', json_encode($this->maxUlid));
        $this->assertSame('"' . Ulid::max()->toString() . '"', json_encode($this->maxUlidWithString));
        $this->assertSame('"' . Ulid::max()->toString() . '"', json_encode($this->maxUlidWithHex));
        $this->assertSame('"' . Ulid::max()->toString() . '"', json_encode($this->maxUlidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(Ulid::max()->toString(), $this->maxUlid->toString());
        $this->assertSame(Ulid::max()->toString(), $this->maxUlidWithString->toString());
        $this->assertSame(Ulid::max()->toString(), $this->maxUlidWithHex->toString());
        $this->assertSame(Ulid::max()->toString(), $this->maxUlidWithBytes->toString());
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
                'expected' => '7ZZZZZZZZZZZZZZZZZZZZZZZZZ',
            ],
            [
                'value' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
                'expected' => '7ZZZZZZZZZZZZZZZZZZZZZZZZZ',
            ],
            [
                'value' => 'ffffffffffffffffffffffffffffffff',
                'expected' => '7ZZZZZZZZZZZZZZZZZZZZZZZZZ',
            ],
        ];
    }
}
