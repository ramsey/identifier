<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use Identifier\Uuid\Variant;
use Identifier\Uuid\Version;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Dce\Domain;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function strtoupper;
use function unserialize;

class UuidV2Test extends TestCase
{
    private const UUID_V2_STRING = '27433d43-011d-2a6a-9100-1550863792c9';
    private const UUID_V2_HEX = '27433d43011d2a6a91001550863792c9';
    private const UUID_V2_BYTES = "\x27\x43\x3d\x43\x01\x1d\x2a\x6a\x91\x00\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV2 $uuidWithString;
    private Uuid\UuidV2 $uuidWithHex;
    private Uuid\UuidV2 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV2(self::UUID_V2_STRING);
        $this->uuidWithHex = new Uuid\UuidV2(self::UUID_V2_HEX);
        $this->uuidWithBytes = new Uuid\UuidV2(self::UUID_V2_BYTES);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: ""');

        new Uuid\UuidV2('');
    }

    public function testConstructorThrowsExceptionForInvalidStringUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-9a6a-9100-1550863792c9"');

        new Uuid\UuidV2('27433d43-011d-9a6a-9100-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidHexUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-9a6a-9100-1550863792c9"');

        new Uuid\UuidV2('27433d43011d9a6a91001550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidBytesUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-9a6a-9100-1550863792c9"');

        new Uuid\UuidV2("\x27\x43\x3d\x43\x01\x1d\x9a\x6a\x91\x00\x15\x50\x86\x37\x92\xc9");
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-2a6a-c161-1550863792c9"');

        new Uuid\UuidV2('27433d43-011d-2a6a-c161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-2a6a-c161-1550863792c9"');

        new Uuid\UuidV2('27433d43011d2a6ac1611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuidBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-2a6a-c161-1550863792c9"');

        new Uuid\UuidV2("\x27\x43\x3d\x43\x01\x1d\x2a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:36:"27433d43-011d-2a6a-9100-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:32:"27433d43011d2a6a91001550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x2a\x6a\x91\x00\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V2_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V2_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V2_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:36:"27433d43-011d-2a6a-9100-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV2::class, $uuid);
        $this->assertSame(self::UUID_V2_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:32:"27433d43011d2a6a91001550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV2::class, $uuid);
        $this->assertSame(self::UUID_V2_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:16:'
            . "\"\x27\x43\x3d\x43\x01\x1d\x2a\x6a\x91\x00\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV2::class, $uuid);
        $this->assertSame(self::UUID_V2_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV2":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9100-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: "27433d43-011d-9a6a-9100-1550863792c9"');

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
            'with same string UUID' => [self::UUID_V2_STRING, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V2_STRING), 0],
            'with same hex UUID' => [self::UUID_V2_HEX, 0],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V2_HEX), 0],
            'with same bytes UUID' => [self::UUID_V2_BYTES, 0],
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
                new class (self::UUID_V2_BYTES) {
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
            'with UuidV2 from string' => [new Uuid\UuidV2(self::UUID_V2_STRING), 0],
            'with UuidV2 from hex' => [new Uuid\UuidV2(self::UUID_V2_HEX), 0],
            'with UuidV2 from bytes' => [new Uuid\UuidV2(self::UUID_V2_BYTES), 0],
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
            'with same string UUID' => [self::UUID_V2_STRING, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V2_STRING), true],
            'with same hex UUID' => [self::UUID_V2_HEX, true],
            'with same hex UUID all caps' => [strtoupper(self::UUID_V2_HEX), true],
            'with same bytes UUID' => [self::UUID_V2_BYTES, true],
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
                new class (self::UUID_V2_BYTES) {
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
            'with UuidV2 from string' => [new Uuid\UuidV2(self::UUID_V2_STRING), true],
            'with UuidV2 from hex' => [new Uuid\UuidV2(self::UUID_V2_HEX), true],
            'with UuidV2 from bytes' => [new Uuid\UuidV2(self::UUID_V2_BYTES), true],
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
        $this->assertSame(Version::DceSecurity, $this->uuidWithString->getVersion());
        $this->assertSame(Version::DceSecurity, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::DceSecurity, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V2_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V2_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V2_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V2_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V2_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V2_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V2_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V2_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V2_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V2_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V2_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V2_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260751007865752194378959917769';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V2_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V2_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V2_STRING, $this->uuidWithBytes->toUrn());
    }

    public function testGetDateTimeFromStringUuid(): void
    {
        $dateTime = $this->uuidWithString->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);

        // v2 UUIDs have a loss of fidelity in the timestamp.
        $this->assertSame('3960-10-02T03:46:38+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromHexUuid(): void
    {
        $dateTime = $this->uuidWithHex->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);

        // v2 UUIDs have a loss of fidelity in the timestamp.
        $this->assertSame('3960-10-02T03:46:38+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromBytesUuid(): void
    {
        $dateTime = $this->uuidWithBytes->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);

        // v2 UUIDs have a loss of fidelity in the timestamp.
        $this->assertSame('3960-10-02T03:46:38+00:00', $dateTime->format('c'));
    }

    public function testGetNode(): void
    {
        $this->assertSame('1550863792c9', $this->uuidWithString->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithHex->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithBytes->getNode());
    }

    public function testGetLocalDomain(): void
    {
        $this->assertSame(Domain::Person, $this->uuidWithString->getLocalDomain());
        $this->assertSame(Domain::Person, $this->uuidWithHex->getLocalDomain());
        $this->assertSame(Domain::Person, $this->uuidWithBytes->getLocalDomain());
    }

    public function testGetLocalIdentifier(): void
    {
        $this->assertSame(658718019, $this->uuidWithString->getLocalIdentifier());
        $this->assertSame(658718019, $this->uuidWithHex->getLocalIdentifier());
        $this->assertSame(658718019, $this->uuidWithBytes->getLocalIdentifier());
    }

    /**
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV2($value);

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
                'value' => '27433D43-011D-2A6A-9100-1550863792C9',
                'expected' => '27433d43-011d-2a6a-9100-1550863792c9',
            ],
            [
                'value' => '27433D43011D2A6A91001550863792C9',
                'expected' => '27433d43-011d-2a6a-9100-1550863792c9',
            ],
            [
                'value' => "\x27\x43\x3D\x43\x01\x1D\x2A\x6A\x91\x00\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-2a6a-9100-1550863792c9',
            ],
        ];
    }
}
