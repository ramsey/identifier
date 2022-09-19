<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Identifier\Uuid\Variant;
use Identifier\Uuid\Version;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function strtoupper;
use function unserialize;

class UuidV8Test extends TestCase
{
    private const UUID_V8_STRING = '27433d43-011d-8a6a-9161-1550863792c9';
    private const UUID_V8_HEX = '27433d43011d8a6a91611550863792c9';
    private const UUID_V8_BYTES = "\x27\x43\x3d\x43\x01\x1d\x8a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV8 $uuidWithString;
    private Uuid\UuidV8 $uuidWithHex;
    private Uuid\UuidV8 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV8(self::UUID_V8_STRING);
        $this->uuidWithHex = new Uuid\UuidV8(self::UUID_V8_HEX);
        $this->uuidWithBytes = new Uuid\UuidV8(self::UUID_V8_BYTES);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: ""');

        new Uuid\UuidV8('');
    }

    public function testConstructorThrowsExceptionForInvalidStringUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV8('27433d43-011d-9a6a-9161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidHexUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV8('27433d43011d9a6a91611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidBytesUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV8("\x27\x43\x3d\x43\x01\x1d\x9a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-8a6a-c161-1550863792c9"');

        new Uuid\UuidV8('27433d43-011d-8a6a-c161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-8a6a-c161-1550863792c9"');

        new Uuid\UuidV8('27433d43011d8a6ac1611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-8a6a-c161-1550863792c9"');

        new Uuid\UuidV8("\x27\x43\x3d\x43\x01\x1d\x8a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:36:"27433d43-011d-8a6a-9161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:32:"27433d43011d8a6a91611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x8a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V8_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V8_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V8_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:36:"27433d43-011d-8a6a-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV8::class, $uuid);
        $this->assertSame(self::UUID_V8_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:32:"27433d43011d8a6a91611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV8::class, $uuid);
        $this->assertSame(self::UUID_V8_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x8a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV8::class, $uuid);
        $this->assertSame(self::UUID_V8_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->uuidWithString->compareTo($other));
        $this->assertSame($expected, $this->uuidWithHex->compareTo($other));
        $this->assertSame($expected, $this->uuidWithBytes->compareTo($other));
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
            'with string Nil UUID' => [Uuid::NIL, 1],
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), 1],
            'with same string UUID' => [self::UUID_V8_STRING, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V8_STRING), 0],
            'with same hex UUID' => [self::UUID_V8_HEX, 0],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V8_HEX), 0],
            'with same bytes UUID' => [self::UUID_V8_BYTES, 0],
            'with string Max UUID' => [Uuid::MAX, -1],
            'with string Max UUID all caps' => [strtoupper(Uuid::MAX), -1],
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
            'with Stringable class returning UUID bytes' => [
                new class (self::UUID_V8_BYTES) {
                    public function __construct(private readonly string $uuidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->uuidBytes;
                    }
                },
                0,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), 1],
            'with UuidV8 from string' => [new Uuid\UuidV8(self::UUID_V8_STRING), 0],
            'with UuidV8 from hex' => [new Uuid\UuidV8(self::UUID_V8_HEX), 0],
            'with UuidV8 from bytes' => [new Uuid\UuidV8(self::UUID_V8_BYTES), 0],
            'with MaxUuid' => [new Uuid\MaxUuid(), -1],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->uuidWithString->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->uuidWithString->equals($other));
        $this->assertSame($expected, $this->uuidWithHex->equals($other));
        $this->assertSame($expected, $this->uuidWithBytes->equals($other));
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
            'with string Nil UUID' => [Uuid::NIL, false],
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), false],
            'with same string UUID' => [self::UUID_V8_STRING, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V8_STRING), true],
            'with same hex UUID' => [self::UUID_V8_HEX, true],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V8_HEX), true],
            'with same bytes UUID' => [self::UUID_V8_BYTES, true],
            'with string Max UUID' => [Uuid::MAX, false],
            'with string Max UUID all caps' => [strtoupper(Uuid::MAX), false],
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
            'with Stringable class returning UUID bytes' => [
                new class (self::UUID_V8_BYTES) {
                    public function __construct(private readonly string $uuidBytes)
                    {
                    }

                    public function __toString(): string
                    {
                        return $this->uuidBytes;
                    }
                },
                true,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), false],
            'with UuidV8 from string' => [new Uuid\UuidV8(self::UUID_V8_STRING), true],
            'with UuidV8 from hex' => [new Uuid\UuidV8(self::UUID_V8_HEX), true],
            'with UuidV8 from bytes' => [new Uuid\UuidV8(self::UUID_V8_BYTES), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->uuidWithString->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->uuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->uuidWithBytes->getVariant());
    }

    public function testGetVersion(): void
    {
        $this->assertSame(Version::Custom, $this->uuidWithString->getVersion());
        $this->assertSame(Version::Custom, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::Custom, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V8_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V8_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V8_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V8_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V8_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V8_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V8_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V8_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V8_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260751461212961852937641366217';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V8_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V8_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V8_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV8($value);

        $this->assertTrue($uuid->equals($value));
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return array<array{value: string, expected: string}>
     */
    public function valuesForLowercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => '27433D43-011D-8A6A-9161-1550863792C9',
                'expected' => '27433d43-011d-8a6a-9161-1550863792c9',
            ],
            [
                'value' => '27433D43011D8A6A91611550863792C9',
                'expected' => '27433d43-011d-8a6a-9161-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x8A\x6A\x91\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-8a6a-9161-1550863792c9',
            ],
        ];
    }

    public function testGetCustomFieldA(): void
    {
        $this->assertSame('27433d43011d', $this->uuidWithString->getCustomFieldA());
        $this->assertSame('27433d43011d', $this->uuidWithHex->getCustomFieldA());
        $this->assertSame('27433d43011d', $this->uuidWithBytes->getCustomFieldA());
    }

    public function testGetCustomFieldB(): void
    {
        $this->assertSame('a6a', $this->uuidWithString->getCustomFieldB());
        $this->assertSame('a6a', $this->uuidWithHex->getCustomFieldB());
        $this->assertSame('a6a', $this->uuidWithBytes->getCustomFieldB());
    }

    public function testGetCustomFieldC(): void
    {
        $this->assertSame('11611550863792c9', $this->uuidWithString->getCustomFieldC());
        $this->assertSame('11611550863792c9', $this->uuidWithHex->getCustomFieldC());
        $this->assertSame('11611550863792c9', $this->uuidWithBytes->getCustomFieldC());
    }
}
