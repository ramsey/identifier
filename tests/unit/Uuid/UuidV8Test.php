<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\UuidV8;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class UuidV8Test extends TestCase
{
    use UuidComparisonTests;

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

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 8 UUID: "%s"', $value));

        new Uuid\UuidV8($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUuidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 35 characters:
            ['value' => '00000000-0000-0000-0000-00000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These 16 bytes don't form a standard UUID:
            ['value' => 'foobarbazquux123'],

            // These contain invalid characters:
            ['value' => '00000000-0000-0000-0000-00000000000g'],
            ['value' => '0000000000000000000000000000000g'],
            ['value' => '00000000-0000-0000-0000-00000000'],

            // Valid Nil UUID:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // Valid Max UUID:
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 1 UUID:
            ['value' => 'ffffffff-ffff-1fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff1fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x1f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 2 UUID:
            ['value' => 'ffffffff-ffff-2fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff2fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x2f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 3 UUID:
            ['value' => 'ffffffff-ffff-3fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff3fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x3f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 4 UUID:
            ['value' => 'ffffffff-ffff-4fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff4fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x4f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 5 UUID:
            ['value' => 'ffffffff-ffff-5fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff5fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x5f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 6 UUID:
            ['value' => 'ffffffff-ffff-6fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff6fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x6f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 7 UUID:
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff7fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // These appear to have valid versions, but they have invalid
            // variants
            ['value' => 'ffffffff-ffff-1fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff1fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x1f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-2fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff2fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x2f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-3fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff3fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x3f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-4fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff4fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x4f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-5fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff5fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x5f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-6fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff6fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x6f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-7fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff7fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-8fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff8fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\xcf\xff\xff\xff\xff\xff\xff\xff"],
        ];
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

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['i:1;'], 'an integer')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenUuidIsNotAString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:4:"uuid";%s}',
            strlen(UuidV8::class),
            UuidV8::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenUuidIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(UuidV8::class), UuidV8::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV8":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Uuid\UuidV8>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from a string' => new Uuid\UuidV8(self::UUID_V8_STRING),
            'from a hexadecimal string' => new Uuid\UuidV8(self::UUID_V8_HEX),
            'from bytes' => new Uuid\UuidV8(self::UUID_V8_BYTES),
        ];
    }

    /**
     * @return array<string, Uuid\UntypedUuid | Uuid\MicrosoftGuid>
     */
    protected static function equalValues(): array
    {
        return [
            'an UntypedUuid with the same value' => new Uuid\UntypedUuid(self::UUID_V8_STRING),
            'a MicrosoftGuid with the same string form' => new Uuid\MicrosoftGuid(self::UUID_V8_STRING),
        ];
    }

    /**
     * @return array<string, Uuid\UuidV8 | Uuid\NilUuid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser UuidV8' => new Uuid\UuidV8('00000000-0000-8000-8000-000000000000'),
            'the NilUuid' => new Uuid\NilUuid(),
            'a lesser UUID string' => '00000000-0000-0000-0000-000000000000',
        ];
    }

    /**
     * @return array<string, Uuid\UuidV8 | Uuid\MaxUuid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater UuidV8' => new Uuid\UuidV8('ffffffff-ffff-8fff-bfff-ffffffffffff'),
            'the MaxUuid' => new Uuid\MaxUuid(),
            'a greater UUID string' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'a greater UUID string in uppercase' => 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc, $this->uuidWithString->getVariant());
        $this->assertSame(Variant::Rfc, $this->uuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc, $this->uuidWithBytes->getVariant());
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

    public function testGetSortKey(): void
    {
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithString->getSortKey());
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithHex->getSortKey());
        $this->assertSame(self::UUID_V8_BYTES, $this->uuidWithBytes->getSortKey());
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
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV8($value);

        $this->assertTrue($uuid->equals($expected));
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return list<array{value: string, expected: string}>
     */
    public static function valuesForLowercaseConversionTestProvider(): array
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
}
