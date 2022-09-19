<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use BadMethodCallException;
use Identifier\Uuid\Variant;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Test\Identifier\TestCase;

use function bin2hex;
use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function unserialize;

class NonstandardUuidTest extends TestCase
{
    private const UUID_NONSTANDARD_STRING = '27433d43-011d-0a6a-0161-1550863792c9';
    private const UUID_NONSTANDARD_HEX = '27433d43011d0a6a01611550863792c9';
    private const UUID_NONSTANDARD_BYTES = "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\NonstandardUuid $uuidWithString;
    private Uuid\NonstandardUuid $uuidWithHex;
    private Uuid\NonstandardUuid $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_STRING);
        $this->uuidWithHex = new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_HEX);
        $this->uuidWithBytes = new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_BYTES);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: ""');

        new Uuid\NonstandardUuid('');
    }

    public function testConstructorThrowsExceptionForInvalidUuidFromString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

        new Uuid\NonstandardUuid('27433d43-011d-4a6a-a161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidUuidFromHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

        new Uuid\NonstandardUuid('27433d43011d4a6aa1611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidUuidFromBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

        new Uuid\NonstandardUuid("\x27\x43\x3d\x43\x01\x1d\x4a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testSerializeForString(): void
    {
        $expected = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-0a6a-0161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:32:"27433d43011d0a6a01611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . "{s:4:\"uuid\";s:16:\"\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-0a6a-0161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NonstandardUuid::class, $uuid);
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:32:"27433d43011d0a6a01611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NonstandardUuid::class, $uuid);
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . "{s:4:\"uuid\";s:16:\"\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NonstandardUuid::class, $uuid);
        $this->assertSame(self::UUID_NONSTANDARD_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidNonstandardUuid(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-4a6a-a161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

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
            'with same string UUID' => [self::UUID_NONSTANDARD_STRING, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_NONSTANDARD_STRING), 0],
            'with same hex UUID' => [self::UUID_NONSTANDARD_HEX, 0],
            'with same hex UUID all caps' => [strtoupper(self::UUID_NONSTANDARD_HEX), 0],
            'with same bytes UUID' => [self::UUID_NONSTANDARD_BYTES, 0],
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
                new class (self::UUID_NONSTANDARD_BYTES) {
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
            'with NonstandardUuid from string' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_STRING), 0],
            'with NonstandardUuid from hex' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_HEX), 0],
            'with NonstandardUuid from bytes' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_BYTES), 0],
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
            'with same string UUID' => [self::UUID_NONSTANDARD_STRING, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_NONSTANDARD_STRING), true],
            'with same hex UUID' => [self::UUID_NONSTANDARD_HEX, true],
            'with same hex UUID all caps' => [strtoupper(self::UUID_NONSTANDARD_HEX), true],
            'with same bytes UUID' => [self::UUID_NONSTANDARD_BYTES, true],
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
                new class (self::UUID_NONSTANDARD_BYTES) {
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
            'with NonstandardUuid from string' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_STRING), true],
            'with NonstandardUuid from hex' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_HEX), true],
            'with NonstandardUuid from bytes' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD_BYTES), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::ReservedNcs, $this->uuidWithString->getVariant());
        $this->assertSame(Variant::ReservedNcs, $this->uuidWithHex->getVariant());
        $this->assertSame(Variant::ReservedNcs, $this->uuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Nonstandard UUIDs do not have a version field');

        $this->uuidWithString->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_NONSTANDARD_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_NONSTANDARD_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_NONSTANDARD_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_NONSTANDARD_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_NONSTANDARD_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_NONSTANDARD_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9";

        $this->assertSame($bytes, $this->uuidWithString->toBytes());
        $this->assertSame($bytes, $this->uuidWithHex->toBytes());
        $this->assertSame($bytes, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = '27433d43011d0a6a01611550863792c9';

        $this->assertSame($hex, $this->uuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->uuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260750856739675752081592390345';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_NONSTANDARD_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_NONSTANDARD_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_NONSTANDARD_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @dataProvider allVariantsProvider
     */
    public function testAllVariants(string $uuid, Variant $expected): void
    {
        $uuid = new Uuid\NonstandardUuid($uuid);

        $this->assertSame($expected, $uuid->getVariant());
    }

    /**
     * @return array<string, array{string, Variant}>
     */
    public function allVariantsProvider(): array
    {
        return [
            'string: reserved NCS: 0' => ['27433d43-011d-0a6a-0161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 1' => ['27433d43-011d-0a6a-1161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 2' => ['27433d43-011d-0a6a-2161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 3' => ['27433d43-011d-0a6a-3161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 4' => ['27433d43-011d-0a6a-4161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 5' => ['27433d43-011d-0a6a-5161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 6' => ['27433d43-011d-0a6a-6161-1550863792c9', Variant::ReservedNcs],
            'string: reserved NCS: 7' => ['27433d43-011d-0a6a-7161-1550863792c9', Variant::ReservedNcs],
            'string: RFC 4122: 8, version 0' => ['27433d43-011d-0a6a-8161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 9, version 0' => ['27433d43-011d-0a6a-9161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: a, version 0' => ['27433d43-011d-0a6a-a161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: b, version 0' => ['27433d43-011d-0a6a-b161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 8, version 9' => ['27433d43-011d-9a6a-8161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 9, version a' => ['27433d43-011d-aa6a-9161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: a, version b' => ['27433d43-011d-ba6a-a161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: b, version c' => ['27433d43-011d-ca6a-b161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 8, version d' => ['27433d43-011d-da6a-8161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 8, version e' => ['27433d43-011d-ea6a-8161-1550863792c9', Variant::Rfc4122],
            'string: RFC 4122: 8, version f' => ['27433d43-011d-fa6a-8161-1550863792c9', Variant::Rfc4122],
            'string: reserved Microsoft: c' => ['27433d43-011d-0a6a-c161-1550863792c9', Variant::ReservedMicrosoft],
            'string: reserved Microsoft: d' => ['27433d43-011d-0a6a-d161-1550863792c9', Variant::ReservedMicrosoft],
            'string: reserved future: e' => ['27433d43-011d-0a6a-e161-1550863792c9', Variant::ReservedFuture],
            'string: reserved future: f' => ['27433d43-011d-0a6a-f161-1550863792c9', Variant::ReservedFuture],

            'hex: reserved NCS: 0' => ['27433d43011d0a6a01611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 1' => ['27433d43011d0a6a11611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 2' => ['27433d43011d0a6a21611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 3' => ['27433d43011d0a6a31611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 4' => ['27433d43011d0a6a41611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 5' => ['27433d43011d0a6a51611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 6' => ['27433d43011d0a6a61611550863792c9', Variant::ReservedNcs],
            'hex: reserved NCS: 7' => ['27433d43011d0a6a71611550863792c9', Variant::ReservedNcs],
            'hex: RFC 4122: 8, version 0' => ['27433d43011d0a6a81611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 9, version 0' => ['27433d43011d0a6a91611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: a, version 0' => ['27433d43011d0a6aa1611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: b, version 0' => ['27433d43011d0a6ab1611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 8, version 9' => ['27433d43011d9a6a81611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 9, version a' => ['27433d43011daa6a91611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: a, version b' => ['27433d43011dba6aa1611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: b, version c' => ['27433d43011dca6ab1611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 8, version d' => ['27433d43011dda6a81611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 8, version e' => ['27433d43011dea6a81611550863792c9', Variant::Rfc4122],
            'hex: RFC 4122: 8, version f' => ['27433d43011dfa6a81611550863792c9', Variant::Rfc4122],
            'hex: reserved Microsoft: c' => ['27433d43011d0a6ac1611550863792c9', Variant::ReservedMicrosoft],
            'hex: reserved Microsoft: d' => ['27433d43011d0a6ad1611550863792c9', Variant::ReservedMicrosoft],
            'hex: reserved future: e' => ['27433d43011d0a6ae1611550863792c9', Variant::ReservedFuture],
            'hex: reserved future: f' => ['27433d43011d0a6af1611550863792c9', Variant::ReservedFuture],

            'bytes: reserved NCS: 0' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 1' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x11\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 2' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x21\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 3' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x31\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 4' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x41\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 5' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x51\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 6' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x61\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: reserved NCS: 7' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x71\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedNcs,
            ],
            'bytes: RFC 4122: 8, version 0' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 9, version 0' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: a, version 0' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: b, version 0' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 8, version 9' => [
                "\x27\x43\x3d\x43\x01\x1d\x9a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 9, version a' => [
                "\x27\x43\x3d\x43\x01\x1d\xaa\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: a, version b' => [
                "\x27\x43\x3d\x43\x01\x1d\xba\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: b, version c' => [
                "\x27\x43\x3d\x43\x01\x1d\xca\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 8, version d' => [
                "\x27\x43\x3d\x43\x01\x1d\xda\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 8, version e' => [
                "\x27\x43\x3d\x43\x01\x1d\xea\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: RFC 4122: 8, version f' => [
                "\x27\x43\x3d\x43\x01\x1d\xfa\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
                Variant::Rfc4122,
            ],
            'bytes: reserved Microsoft: c' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedMicrosoft,
            ],
            'bytes: reserved Microsoft: d' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xd1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedMicrosoft,
            ],
            'bytes: reserved future: e' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xe1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedFuture,
            ],
            'bytes: reserved future: f' => [
                "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xf1\x61\x15\x50\x86\x37\x92\xc9",
                Variant::ReservedFuture,
            ],
        ];
    }

    /**
     * @dataProvider invalidNonstandardProvider
     */
    public function testInvalidNonstandard(string $uuid): void
    {
        $formatted = match (strlen($uuid)) {
            36 => strtolower($uuid),
            32 => strtolower(sprintf(
                '%08s-%04s-%04s-%04s-%012s',
                substr($uuid, 0, 8),
                substr($uuid, 8, 4),
                substr($uuid, 12, 4),
                substr($uuid, 16, 4),
                substr($uuid, 20),
            )),
            default => (static function (string $uuid): string {
                $hex = bin2hex($uuid);

                return sprintf(
                    '%08s-%04s-%04s-%04s-%012s',
                    substr($hex, 0, 8),
                    substr($hex, 8, 4),
                    substr($hex, 12, 4),
                    substr($hex, 16, 4),
                    substr($hex, 20),
                );
            })(
                $uuid,
            ),
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid nonstandard UUID: \"$formatted\"");

        new Uuid\NonstandardUuid($uuid);
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidNonstandardProvider(): array
    {
        return [
            // The variant character is "8".
            'string: RFC 4122, version 1, variant "8"' => ['27433d43-011d-1a6a-8161-1550863792c9'],
            'string: RFC 4122, version 2, variant "8"' => ['27433d43-011d-2a6a-8161-1550863792c9'],
            'string: RFC 4122, version 3, variant "8"' => ['27433d43-011d-3a6a-8161-1550863792c9'],
            'string: RFC 4122, version 4, variant "8"' => ['27433d43-011d-4a6a-8161-1550863792c9'],
            'string: RFC 4122, version 5, variant "8"' => ['27433d43-011d-5a6a-8161-1550863792c9'],
            'string: RFC 4122, version 6, variant "8"' => ['27433d43-011d-6a6a-8161-1550863792c9'],
            'string: RFC 4122, version 7, variant "8"' => ['27433d43-011d-7a6a-8161-1550863792c9'],
            'string: RFC 4122, version 8, variant "8"' => ['27433d43-011d-8a6a-8161-1550863792c9'],

            'hex: RFC 4122, version 1, variant "8"' => ['27433d43011d1a6a81611550863792c9'],
            'hex: RFC 4122, version 2, variant "8"' => ['27433d43011d2a6a81611550863792c9'],
            'hex: RFC 4122, version 3, variant "8"' => ['27433d43011d3a6a81611550863792c9'],
            'hex: RFC 4122, version 4, variant "8"' => ['27433d43011d4a6a81611550863792c9'],
            'hex: RFC 4122, version 5, variant "8"' => ['27433d43011d5a6a81611550863792c9'],
            'hex: RFC 4122, version 6, variant "8"' => ['27433d43011d6a6a81611550863792c9'],
            'hex: RFC 4122, version 7, variant "8"' => ['27433d43011d7a6a81611550863792c9'],
            'hex: RFC 4122, version 8, variant "8"' => ['27433d43011d8a6a81611550863792c9'],

            'bytes: RFC 4122, version 1, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 2, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x2a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 3, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x3a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 4, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 5, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x5a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 6, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x6a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 7, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x7a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 8, variant "8"' => [
                "\x27\x43\x3d\x43\x01\x1d\x8a\x6a\x81\x61\x15\x50\x86\x37\x92\xc9",
            ],

            // The variant character is "9".
            'string: RFC 4122, version 1, variant "9"' => ['27433d43-011d-1a6a-9161-1550863792c9'],
            'string: RFC 4122, version 2, variant "9"' => ['27433d43-011d-2a6a-9161-1550863792c9'],
            'string: RFC 4122, version 3, variant "9"' => ['27433d43-011d-3a6a-9161-1550863792c9'],
            'string: RFC 4122, version 4, variant "9"' => ['27433d43-011d-4a6a-9161-1550863792c9'],
            'string: RFC 4122, version 5, variant "9"' => ['27433d43-011d-5a6a-9161-1550863792c9'],
            'string: RFC 4122, version 6, variant "9"' => ['27433d43-011d-6a6a-9161-1550863792c9'],
            'string: RFC 4122, version 7, variant "9"' => ['27433d43-011d-7a6a-9161-1550863792c9'],
            'string: RFC 4122, version 8, variant "9"' => ['27433d43-011d-8a6a-9161-1550863792c9'],

            'hex: RFC 4122, version 1, variant "9"' => ['27433d43011d1a6a91611550863792c9'],
            'hex: RFC 4122, version 2, variant "9"' => ['27433d43011d2a6a91611550863792c9'],
            'hex: RFC 4122, version 3, variant "9"' => ['27433d43011d3a6a91611550863792c9'],
            'hex: RFC 4122, version 4, variant "9"' => ['27433d43011d4a6a91611550863792c9'],
            'hex: RFC 4122, version 5, variant "9"' => ['27433d43011d5a6a91611550863792c9'],
            'hex: RFC 4122, version 6, variant "9"' => ['27433d43011d6a6a91611550863792c9'],
            'hex: RFC 4122, version 7, variant "9"' => ['27433d43011d7a6a91611550863792c9'],
            'hex: RFC 4122, version 8, variant "9"' => ['27433d43011d8a6a91611550863792c9'],

            'bytes: RFC 4122, version 1, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 2, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x2a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 3, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x3a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 4, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 5, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x5a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 6, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x6a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 7, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x7a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 8, variant "9"' => [
                "\x27\x43\x3d\x43\x01\x1d\x8a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            ],

            // The variant character is "a".
            'string: RFC 4122, version 1, variant "a"' => ['27433d43-011d-1a6a-a161-1550863792c9'],
            'string: RFC 4122, version 2, variant "a"' => ['27433d43-011d-2a6a-a161-1550863792c9'],
            'string: RFC 4122, version 3, variant "a"' => ['27433d43-011d-3a6a-a161-1550863792c9'],
            'string: RFC 4122, version 4, variant "a"' => ['27433d43-011d-4a6a-a161-1550863792c9'],
            'string: RFC 4122, version 5, variant "a"' => ['27433d43-011d-5a6a-a161-1550863792c9'],
            'string: RFC 4122, version 6, variant "a"' => ['27433d43-011d-6a6a-a161-1550863792c9'],
            'string: RFC 4122, version 7, variant "a"' => ['27433d43-011d-7a6a-a161-1550863792c9'],
            'string: RFC 4122, version 8, variant "a"' => ['27433d43-011d-8a6a-a161-1550863792c9'],

            'hex: RFC 4122, version 1, variant "a"' => ['27433d43011d1a6aa1611550863792c9'],
            'hex: RFC 4122, version 2, variant "a"' => ['27433d43011d2a6aa1611550863792c9'],
            'hex: RFC 4122, version 3, variant "a"' => ['27433d43011d3a6aa1611550863792c9'],
            'hex: RFC 4122, version 4, variant "a"' => ['27433d43011d4a6aa1611550863792c9'],
            'hex: RFC 4122, version 5, variant "a"' => ['27433d43011d5a6aa1611550863792c9'],
            'hex: RFC 4122, version 6, variant "a"' => ['27433d43011d6a6aa1611550863792c9'],
            'hex: RFC 4122, version 7, variant "a"' => ['27433d43011d7a6aa1611550863792c9'],
            'hex: RFC 4122, version 8, variant "a"' => ['27433d43011d8a6aa1611550863792c9'],

            'bytes: RFC 4122, version 1, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 2, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x2a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 3, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x3a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 4, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 5, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x5a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 6, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x6a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 7, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x7a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 8, variant "a"' => [
                "\x27\x43\x3d\x43\x01\x1d\x8a\x6a\xa1\x61\x15\x50\x86\x37\x92\xc9",
            ],

            // The variant character is "b".
            'string: RFC 4122, version 1, variant "b"' => ['27433d43-011d-1a6a-b161-1550863792c9'],
            'string: RFC 4122, version 2, variant "b"' => ['27433d43-011d-2a6a-b161-1550863792c9'],
            'string: RFC 4122, version 3, variant "b"' => ['27433d43-011d-3a6a-b161-1550863792c9'],
            'string: RFC 4122, version 4, variant "b"' => ['27433d43-011d-4a6a-b161-1550863792c9'],
            'string: RFC 4122, version 5, variant "b"' => ['27433d43-011d-5a6a-b161-1550863792c9'],
            'string: RFC 4122, version 6, variant "b"' => ['27433d43-011d-6a6a-b161-1550863792c9'],
            'string: RFC 4122, version 7, variant "b"' => ['27433d43-011d-7a6a-b161-1550863792c9'],
            'string: RFC 4122, version 8, variant "b"' => ['27433d43-011d-8a6a-b161-1550863792c9'],

            'hex: RFC 4122, version 1, variant "b"' => ['27433d43011d1a6ab1611550863792c9'],
            'hex: RFC 4122, version 2, variant "b"' => ['27433d43011d2a6ab1611550863792c9'],
            'hex: RFC 4122, version 3, variant "b"' => ['27433d43011d3a6ab1611550863792c9'],
            'hex: RFC 4122, version 4, variant "b"' => ['27433d43011d4a6ab1611550863792c9'],
            'hex: RFC 4122, version 5, variant "b"' => ['27433d43011d5a6ab1611550863792c9'],
            'hex: RFC 4122, version 6, variant "b"' => ['27433d43011d6a6ab1611550863792c9'],
            'hex: RFC 4122, version 7, variant "b"' => ['27433d43011d7a6ab1611550863792c9'],
            'hex: RFC 4122, version 8, variant "b"' => ['27433d43011d8a6ab1611550863792c9'],

            'bytes: RFC 4122, version 1, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 2, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x2a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 3, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x3a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 4, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 5, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x5a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 6, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x6a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 7, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x7a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
            'bytes: RFC 4122, version 8, variant "b"' => [
                "\x27\x43\x3d\x43\x01\x1d\x8a\x6a\xb1\x61\x15\x50\x86\x37\x92\xc9",
            ],
        ];
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\NonstandardUuid($value);

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
                'value' => '27433D43-011D-0A6A-3161-1550863792C9',
                'expected' => '27433d43-011d-0a6a-3161-1550863792c9',
            ],
            [
                'value' => '27433D43011D0A6A31611550863792C9',
                'expected' => '27433d43-011d-0a6a-3161-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x0A\x6A\x31\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-0a6a-3161-1550863792c9',
            ],
        ];
    }
}
