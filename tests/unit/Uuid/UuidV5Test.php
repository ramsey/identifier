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

class UuidV5Test extends TestCase
{
    private const UUID_V5_STRING = '27433d43-011d-5a6a-9161-1550863792c9';
    private const UUID_V5_HEX = '27433d43011d5a6a91611550863792c9';
    private const UUID_V5_BYTES = "\x27\x43\x3d\x43\x01\x1d\x5a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV5 $uuidWithString;
    private Uuid\UuidV5 $uuidWithHex;
    private Uuid\UuidV5 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV5(self::UUID_V5_STRING);
        $this->uuidWithHex = new Uuid\UuidV5(self::UUID_V5_HEX);
        $this->uuidWithBytes = new Uuid\UuidV5(self::UUID_V5_BYTES);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: ""');

        new Uuid\UuidV5('');
    }

    public function testConstructorThrowsExceptionForInvalidStringUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV5('27433d43-011d-9a6a-9161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidHexUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV5('27433d43011d9a6a91611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidBytesUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV5("\x27\x43\x3d\x43\x01\x1d\x9a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-3a6a-c161-1550863792c9"');

        new Uuid\UuidV5('27433d43-011d-3a6a-c161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-3a6a-c161-1550863792c9"');

        new Uuid\UuidV5('27433d43011d3a6ac1611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-3a6a-c161-1550863792c9"');

        new Uuid\UuidV5("\x27\x43\x3d\x43\x01\x1d\x3a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:36:"27433d43-011d-5a6a-9161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:32:"27433d43011d5a6a91611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x5a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V5_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V5_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V5_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:36:"27433d43-011d-5a6a-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV5::class, $uuid);
        $this->assertSame(self::UUID_V5_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:32:"27433d43011d5a6a91611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV5::class, $uuid);
        $this->assertSame(self::UUID_V5_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x5a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV5::class, $uuid);
        $this->assertSame(self::UUID_V5_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV5":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

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
            'with same string UUID' => [self::UUID_V5_STRING, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V5_STRING), 0],
            'with same hex UUID' => [self::UUID_V5_HEX, 0],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V5_HEX), 0],
            'with same bytes UUID' => [self::UUID_V5_BYTES, 0],
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
                new class (self::UUID_V5_BYTES) {
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
            'with UuidV5 from string' => [new Uuid\UuidV5(self::UUID_V5_STRING), 0],
            'with UuidV5 from hex' => [new Uuid\UuidV5(self::UUID_V5_HEX), 0],
            'with UuidV5 from bytes' => [new Uuid\UuidV5(self::UUID_V5_BYTES), 0],
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
            'with same string UUID' => [self::UUID_V5_STRING, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V5_STRING), true],
            'with same hex UUID' => [self::UUID_V5_HEX, true],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V5_HEX), true],
            'with same bytes UUID' => [self::UUID_V5_BYTES, true],
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
                new class (self::UUID_V5_BYTES) {
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
            'with UuidV5 from string' => [new Uuid\UuidV5(self::UUID_V5_STRING), true],
            'with UuidV5 from hex' => [new Uuid\UuidV5(self::UUID_V5_HEX), true],
            'with UuidV5 from bytes' => [new Uuid\UuidV5(self::UUID_V5_BYTES), true],
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
        $this->assertSame(Version::HashSha1, $this->uuidWithString->getVersion());
        $this->assertSame(Version::HashSha1, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::HashSha1, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V5_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V5_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V5_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V5_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V5_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V5_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V5_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V5_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V5_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V5_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V5_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V5_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260751234539370675194671108809';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V5_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V5_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V5_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV5($value);

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
                'value' => '27433D43-011D-5A6A-9161-1550863792C9',
                'expected' => '27433d43-011d-5a6a-9161-1550863792c9',
            ],
            [
                'value' => '27433D43011D5A6A91611550863792C9',
                'expected' => '27433d43-011d-5a6a-9161-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x5A\x6A\x91\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-5a6a-9161-1550863792c9',
            ],
        ];
    }
}
