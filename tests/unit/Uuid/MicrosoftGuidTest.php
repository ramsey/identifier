<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Identifier\Exception\NotConvertible as IdentifierNotConvertible;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function hex2bin;
use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class MicrosoftGuidTest extends TestCase
{
    use UuidComparisonTests;

    private const GUID_STRING = '27433d43-011d-4a6a-c161-1550863792c9';
    private const GUID_HEX = '27433d43011d4a6ac1611550863792c9';
    private const GUID_BYTES = "\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\MicrosoftGuid $guidWithString;
    private Uuid\MicrosoftGuid $guidWithHex;
    private Uuid\MicrosoftGuid $guidWithBytes;

    protected function setUp(): void
    {
        $this->guidWithString = new Uuid\MicrosoftGuid(self::GUID_STRING);
        $this->guidWithHex = new Uuid\MicrosoftGuid(self::GUID_HEX);
        $this->guidWithBytes = new Uuid\MicrosoftGuid(self::GUID_BYTES);
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidGuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidGuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Microsoft GUID: "%s"', $value));

        new Uuid\MicrosoftGuid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidGuidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 35 characters:
            ['value' => '00000000-0000-0000-0000-00000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These contain invalid characters:
            ['value' => '00000000-0000-0000-0000-00000000000g'],
            ['value' => '0000000000000000000000000000000g'],
            ['value' => '00000000-0000-0000-0000-00000000'],

            // Valid Nil UUID:
            ['value' => '0'],
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // Valid Max UUID:
            ['value' => '340282366920938463463374607431768211455'],
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 1 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x1f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 2 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x2f\xff\x9f\x00\xff\xff\xff\xff\xff\xff"],

            // Valid version 3 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x3f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 4 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x4f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 5 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x5f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 6 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x6f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 7 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 8 UUID (non GUID bytes):
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // These look like GUIDs, but they have the wrong versions/variants:
            ['value' => '00000000-0000-0000-c000-000000000000'],
            ['value' => '00000000-0000-0000-d000-000000000000'],
            ['value' => '00000000-0000-9000-c000-000000000000'],
            ['value' => '00000000-0000-a000-d000-000000000000'],
            ['value' => '00000000-0000-1000-e000-000000000000'],
        ];
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('microsoftGuidProvider')]
    public function testSucceedsForMicrosoftGuids(string $value): void
    {
        $uuid = new Uuid\MicrosoftGuid($value);

        /** @phpstan-ignore-next-line */
        $this->assertInstanceOf(Uuid\MicrosoftGuid::class, $uuid);
    }

    /**
     * @return array<array{value: string, messageValue?: string}>
     */
    public static function microsoftGuidProvider(): array
    {
        return [
            // Valid version 1 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-1fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff1fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x1f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 2 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-2fff-9f00-ffffffffffff'],
            ['value' => 'ffffffffffff2fff9f00ffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x2f\x9f\x00\xff\xff\xff\xff\xff\xff"],

            // Valid version 3 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-3fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff3fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x3f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 4 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-4fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff4fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x4f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 5 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-5fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff5fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x5f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 6 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-6fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff6fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x6f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 7 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff7fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x7f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 8 UUID (with GUID bytes):
            ['value' => 'ffffffff-ffff-8fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff8fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x8f\x9f\xff\xff\xff\xff\xff\xff\xff"],

            ['value' => 'ffffffff-ffff-1fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff1fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x1f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-2fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff2fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x2f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-3fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff3fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x3f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-4fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff4fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x4f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-5fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff5fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x5f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-6fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff6fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x6f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-7fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff7fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x7f\xcf\xff\xff\xff\xff\xff\xff\xff"],
            ['value' => 'ffffffff-ffff-8fff-cfff-ffffffffffff'],
            ['value' => 'ffffffffffff8fffcfffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\x8f\xcf\xff\xff\xff\xff\xff\xff\xff"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . '{s:4:"guid";s:36:"27433d43-011d-4a6a-c161-1550863792c9";}';
        $serialized = serialize($this->guidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . '{s:4:"guid";s:32:"27433d43011d4a6ac1611550863792c9";}';
        $serialized = serialize($this->guidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . "{s:4:\"guid\";s:16:\"\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->guidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::GUID_STRING, (string) $this->guidWithString);
        $this->assertSame(self::GUID_STRING, (string) $this->guidWithHex);
        $this->assertSame(self::GUID_STRING, (string) $this->guidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . '{s:4:"guid";s:36:"27433d43-011d-4a6a-c161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MicrosoftGuid::class, $uuid);
        $this->assertSame(self::GUID_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . '{s:4:"guid";s:32:"27433d43011d4a6ac1611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MicrosoftGuid::class, $uuid);
        $this->assertSame(self::GUID_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . "{s:4:\"guid\";s:16:\"\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MicrosoftGuid::class, $uuid);
        $this->assertSame(self::GUID_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenGuidIsAnEmptyString(): void
    {
        $serialized = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:{s:4:"guid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Microsoft GUID: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['i:1;'], 'an integer')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenGuidIsNotAString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:4:"guid";%s}',
            strlen(MicrosoftGuid::class),
            MicrosoftGuid::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "guid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenGuidIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(MicrosoftGuid::class), MicrosoftGuid::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "guid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidMicrosoftGuid(): void
    {
        $serialized = 'O:36:"Ramsey\\Identifier\\Uuid\\MicrosoftGuid":1:'
            . '{s:4:"guid";s:36:"27433d43-011d-4a6a-e161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Microsoft GUID: "27433d43-011d-4a6a-e161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Uuid\MicrosoftGuid>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from a string' => new Uuid\MicrosoftGuid(self::GUID_STRING),
            'from a hexadecimal string' => new Uuid\MicrosoftGuid(self::GUID_HEX),
            'from bytes' => new Uuid\MicrosoftGuid(self::GUID_BYTES),
        ];
    }

    /**
     * @return array<string, Uuid\UntypedUuid>
     */
    protected static function equalValues(): array
    {
        return [
            'an UntypedUuid with the same string form' => new Uuid\UntypedUuid(self::GUID_STRING),
        ];
    }

    /**
     * @return array<string, Uuid\MicrosoftGuid | Uuid\NilUuid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser MicrosoftGuid' => new Uuid\MicrosoftGuid('00000000-0000-4000-8000-000000000000'),
            'the NilUuid' => new Uuid\NilUuid(),
            'a lesser UUID string' => '00000000-0000-0000-0000-000000000000',
        ];
    }

    /**
     * @return array<string, Uuid\MicrosoftGuid | Uuid\MaxUuid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater MicrosoftGuid' => new Uuid\MicrosoftGuid('ffffffff-ffff-4fff-bfff-ffffffffffff'),
            'the MaxUuid' => new Uuid\MaxUuid(),
            'a greater UUID string' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ];
    }

    public function testNonstandardUuidsFromBytesThatLookLikeNumbersAreNotEqual(): void
    {
        // No GUID has these bytes, since the variant byte of a GUID is at least
        // 0x80, so this uses NonstandardUuid.
        $first = new Uuid\NonstandardUuid('0000000000000010');
        $second = new Uuid\NonstandardUuid('00000000000001e1');

        $this->assertLessThan(0, $first->compareTo($second));
        $this->assertGreaterThan(0, $second->compareTo($first));
        $this->assertFalse($first->equals($second));
        $this->assertFalse($second->equals($first));
    }

    public function testGuidsSortInTheOrderOfTheirStringForm(): void
    {
        // In the byte order of a GUID, the first of these is less than the
        // second; in the order of their string forms, it is greater.
        $first = new Uuid\MicrosoftGuid('01000000-0000-4000-8000-000000000000');
        $second = new Uuid\MicrosoftGuid('00000001-0000-4000-8000-000000000000');

        $this->assertLessThan(0, $second->compareTo($first));
        $this->assertGreaterThan(0, $first->compareTo($second));
        $this->assertLessThan(0, $second->compareTo('01000000-0000-4000-8000-000000000000'));
    }

    public function testGuidCompareToUuidWithSameValue(): void
    {
        // Their byte representations are different, but their string
        // representations are the same.
        $guid = new Uuid\MicrosoftGuid("\xff\xff\xff\xff\xff\xff\xff\x4f\x9f\xff\xff\xff\xff\xff\xff\xff");
        $uuid = new Uuid\UuidV4("\xff\xff\xff\xff\xff\xff\x4f\xff\x9f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame(0, $guid->compareTo($uuid));
        $this->assertSame(0, $uuid->compareTo($guid));
    }

    public function testGuidIsEqualToUuidWithSameValue(): void
    {
        // Their byte representations are different, but their string
        // representations are the same.
        $guid = new Uuid\MicrosoftGuid("\xff\xff\xff\xff\xff\xff\xff\x4f\x9f\xff\xff\xff\xff\xff\xff\xff");
        $uuid = new Uuid\UuidV4("\xff\xff\xff\xff\xff\xff\x4f\xff\x9f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertTrue($guid->equals($uuid));
        $this->assertTrue($uuid->equals($guid));
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Microsoft, $this->guidWithString->getVariant());
        $this->assertSame(Variant::Microsoft, $this->guidWithHex->getVariant());
        $this->assertSame(Variant::Microsoft, $this->guidWithBytes->getVariant());
    }

    public function testGetVariantForRfc9562Guid(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('ffffffff-ffff-4fff-9fff-ffffffffffff');
        $guidWithHex = new Uuid\MicrosoftGuid('ffffffffffff4fff9fffffffffffffff');
        $guidWithBytes = new Uuid\MicrosoftGuid("\xff\xff\xff\xff\xff\xff\xff\x4f\x9f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame(Variant::Rfc, $guidWithString->getVariant());
        $this->assertSame(Variant::Rfc, $guidWithHex->getVariant());
        $this->assertSame(Variant::Rfc, $guidWithBytes->getVariant());
    }

    public function testGetVersion(): void
    {
        $this->assertSame(Version::Random, $this->guidWithString->getVersion());
        $this->assertSame(Version::Random, $this->guidWithHex->getVersion());
        $this->assertSame(Version::Random, $this->guidWithBytes->getVersion());
    }

    public function testGetVersionReturnsOtherVersionValues(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('ffffffff-ffff-5fff-cfff-ffffffffffff');
        $guidWithHex = new Uuid\MicrosoftGuid('ffffffffffff5fffcfffffffffffffff');
        $guidWithBytes = new Uuid\MicrosoftGuid("\xff\xff\xff\xff\xff\xff\xff\x5f\xcf\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame(Version::NameSha1, $guidWithString->getVersion());
        $this->assertSame(Version::NameSha1, $guidWithHex->getVersion());
        $this->assertSame(Version::NameSha1, $guidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::GUID_STRING . '"', json_encode($this->guidWithString));
        $this->assertSame('"' . self::GUID_STRING . '"', json_encode($this->guidWithHex));
        $this->assertSame('"' . self::GUID_STRING . '"', json_encode($this->guidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::GUID_STRING, $this->guidWithString->toString());
        $this->assertSame(self::GUID_STRING, $this->guidWithHex->toString());
        $this->assertSame(self::GUID_STRING, $this->guidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9";

        $this->assertSame($bytes, $this->guidWithString->toBytes());
        $this->assertSame($bytes, $this->guidWithHex->toBytes());
        $this->assertSame($bytes, $this->guidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $sortKey = hex2bin('27433d43011d4a6ac1611550863792c9');

        $this->assertSame($sortKey, $this->guidWithString->getSortKey());
        $this->assertSame($sortKey, $this->guidWithBytes->getSortKey());
        $this->assertNotSame($this->guidWithString->toBytes(), $this->guidWithString->getSortKey());
        $this->assertNotSame($this->guidWithBytes->toBytes(), $this->guidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $hex = '27433d43011d4a6ac1611550863792c9';

        $this->assertSame($hex, $this->guidWithString->toHexadecimal());
        $this->assertSame($hex, $this->guidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->guidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '52189018260751158984965713794168230601';

        $this->assertSame($int, $this->guidWithString->toInteger());
        $this->assertSame($int, $this->guidWithHex->toInteger());
        $this->assertSame($int, $this->guidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::GUID_STRING, $this->guidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::GUID_STRING, $this->guidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::GUID_STRING, $this->guidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\MicrosoftGuid($value);

        $this->assertTrue($uuid->equals($expected));
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return array<array{value: string, expected: string}>
     */
    public static function valuesForLowercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => '27433D43-011D-1A6A-D161-1550863792C9',
                'expected' => '27433d43-011d-1a6a-d161-1550863792c9',
            ],
            [
                'value' => '27433D43011D1A6AD1611550863792C9',
                'expected' => '27433d43-011d-1a6a-d161-1550863792c9',
            ],
            [
                'value' => "\x43\x3D\x43\x27\x1D\x01\x6A\x1A\xD1\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => '27433d43-011d-1a6a-d161-1550863792c9',
            ],
        ];
    }

    public function testVersion1Guid(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('27433d43-011d-1a6a-d161-1550863792c9');
        $guidWithHex = new Uuid\MicrosoftGuid('27433d43011d1a6ad1611550863792c9');
        $guidWithBytes = new Uuid\MicrosoftGuid("\x43\x3d\x43\x27\x1d\x01\x6a\x1a\xd1\x61\x15\x50\x86\x37\x92\xc9");

        $dateTimeForGuidWithString = $guidWithString->getDateTime();
        $dateTimeForGuidWithHex = $guidWithHex->getDateTime();
        $dateTimeForGuidWithBytes = $guidWithBytes->getDateTime();

        $this->assertSame(Version::GregorianTime, $guidWithString->getVersion());
        $this->assertSame(Version::GregorianTime, $guidWithHex->getVersion());
        $this->assertSame(Version::GregorianTime, $guidWithBytes->getVersion());
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithString->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithHex->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithBytes->format('Y-m-d H:i:s.u'));
        $this->assertSame('1550863792c9', $guidWithString->getNode());
        $this->assertSame('1550863792c9', $guidWithHex->getNode());
        $this->assertSame('1550863792c9', $guidWithBytes->getNode());
    }

    public function testVersion2Guid(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('27433d43-011d-2a6a-d101-1550863792c9');
        $guidWithHex = new Uuid\MicrosoftGuid('27433d43011d2a6ad1011550863792c9');
        $guidWithBytes = new Uuid\MicrosoftGuid("\x43\x3d\x43\x27\x1d\x01\x6a\x2a\xd1\x01\x15\x50\x86\x37\x92\xc9");

        $dateTimeForGuidWithString = $guidWithString->getDateTime();
        $dateTimeForGuidWithHex = $guidWithHex->getDateTime();
        $dateTimeForGuidWithBytes = $guidWithBytes->getDateTime();

        $this->assertSame(Version::DceSecurity, $guidWithString->getVersion());
        $this->assertSame(Version::DceSecurity, $guidWithHex->getVersion());
        $this->assertSame(Version::DceSecurity, $guidWithBytes->getVersion());
        $this->assertSame('3960-10-02 03:46:37.628826', $dateTimeForGuidWithString->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:46:37.628826', $dateTimeForGuidWithHex->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:46:37.628826', $dateTimeForGuidWithBytes->format('Y-m-d H:i:s.u'));
        $this->assertSame(DceDomain::Group, $guidWithString->getLocalDomain());
        $this->assertSame(DceDomain::Group, $guidWithHex->getLocalDomain());
        $this->assertSame(DceDomain::Group, $guidWithBytes->getLocalDomain());
        $this->assertSame(658718019, $guidWithString->getLocalIdentifier());
        $this->assertSame(658718019, $guidWithHex->getLocalIdentifier());
        $this->assertSame(658718019, $guidWithBytes->getLocalIdentifier());
        $this->assertSame('1550863792c9', $guidWithString->getNode());
        $this->assertSame('1550863792c9', $guidWithHex->getNode());
        $this->assertSame('1550863792c9', $guidWithBytes->getNode());
    }

    public function testVersion6Guid(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('a6a011d2-7433-6d43-d161-1550863792c9');
        $guidWithHex = new Uuid\MicrosoftGuid('a6a011d274336d43d1611550863792c9');
        $guidWithBytes = new Uuid\MicrosoftGuid("\xd2\x11\xa0\xa6\x33\x74\x43\x6d\xd1\x61\x15\x50\x86\x37\x92\xc9");

        $dateTimeForGuidWithString = $guidWithString->getDateTime();
        $dateTimeForGuidWithHex = $guidWithHex->getDateTime();
        $dateTimeForGuidWithBytes = $guidWithBytes->getDateTime();

        $this->assertSame(Version::ReorderedGregorianTime, $guidWithString->getVersion());
        $this->assertSame(Version::ReorderedGregorianTime, $guidWithHex->getVersion());
        $this->assertSame(Version::ReorderedGregorianTime, $guidWithBytes->getVersion());
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithString->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithHex->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500628', $dateTimeForGuidWithBytes->format('Y-m-d H:i:s.u'));
        $this->assertSame('1550863792c9', $guidWithString->getNode());
        $this->assertSame('1550863792c9', $guidWithHex->getNode());
        $this->assertSame('1550863792c9', $guidWithBytes->getNode());
    }

    public function testVersion7Guid(): void
    {
        $guidWithString = new Uuid\MicrosoftGuid('3922e67a-910c-704c-bbd3-a5765a69f0d9');
        $guidWithHex = new Uuid\MicrosoftGuid('3922e67a910c704cbbd3a5765a69f0d9');
        $guidWithBytes = new Uuid\MicrosoftGuid("\x7a\xe6\x22\x39\x0c\x91\x4c\x70\xbb\xd3\xa5\x76\x5a\x69\xf0\xd9");

        $dateTimeForGuidWithString = $guidWithString->getDateTime();
        $dateTimeForGuidWithHex = $guidWithHex->getDateTime();
        $dateTimeForGuidWithBytes = $guidWithBytes->getDateTime();

        $this->assertSame(Version::UnixTime, $guidWithString->getVersion());
        $this->assertSame(Version::UnixTime, $guidWithHex->getVersion());
        $this->assertSame(Version::UnixTime, $guidWithBytes->getVersion());
        $this->assertSame('3960-10-02 03:47:43.500000', $dateTimeForGuidWithString->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500000', $dateTimeForGuidWithHex->format('Y-m-d H:i:s.u'));
        $this->assertSame('3960-10-02 03:47:43.500000', $dateTimeForGuidWithBytes->format('Y-m-d H:i:s.u'));
    }

    public function testGetDateTimeThrowsException(): void
    {
        $this->expectException(NotConvertible::class);
        $this->expectExceptionMessage('Version 4 GUIDs do not contain date-time values');

        $this->guidWithString->getDateTime();
    }

    public function testGetDateTimeExceptionImplementsSpecificationInterface(): void
    {
        $this->expectException(IdentifierNotConvertible::class);

        $this->guidWithString->getDateTime();
    }

    public function testGetDateTimeExceptionIsBadMethodCall(): void
    {
        $this->expectException(BadMethodCall::class);

        $this->guidWithString->getDateTime();
    }

    public function testGetLocalDomainThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Version 4 GUIDs do not contain local domain values');

        $this->guidWithString->getLocalDomain();
    }

    public function testGetLocalIdentifierThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Version 4 GUIDs do not contain local identifier values');

        $this->guidWithString->getLocalIdentifier();
    }

    public function testGetNodeThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Version 4 GUIDs do not contain node values');

        $this->guidWithString->getNode();
    }

    /**
     * @param non-empty-string $guidValue
     * @param class-string $expectedInstanceOf
     */
    #[DataProvider('toRfcProvider')]
    public function testToRfc(
        string $guidValue,
        string $expectedInstanceOf,
        string $expectedUuidValue,
        bool $expectedEquality,
    ): void {
        $guid = new Uuid\MicrosoftGuid($guidValue);
        $uuid = $guid->toRfc();

        $this->assertInstanceOf($expectedInstanceOf, $uuid);
        $this->assertSame($expectedUuidValue, $uuid->toString());
        $this->assertSame($expectedEquality, $uuid->equals($guid));
    }

    /**
     * @return array<string, array{guidValue: string, expectedInstanceOf: class-string, expectedUuidValue: string, expectedEquality: bool}>
     */
    public static function toRfcProvider(): array
    {
        return [
            'v1 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-1fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV1::class,
                'expectedUuidValue' => 'ffffffff-ffff-1fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v2 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-2fff-8f00-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV2::class,
                'expectedUuidValue' => 'ffffffff-ffff-2fff-8f00-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v3 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-3fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV3::class,
                'expectedUuidValue' => 'ffffffff-ffff-3fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v4 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV4::class,
                'expectedUuidValue' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v5 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-5fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV5::class,
                'expectedUuidValue' => 'ffffffff-ffff-5fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v6 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-6fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV6::class,
                'expectedUuidValue' => 'ffffffff-ffff-6fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v7 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-7fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV7::class,
                'expectedUuidValue' => 'ffffffff-ffff-7fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v8 UUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-8fff-8fff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV8::class,
                'expectedUuidValue' => 'ffffffff-ffff-8fff-8fff-ffffffffffff',
                'expectedEquality' => true,
            ],
            'v1 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-1fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV1::class,
                'expectedUuidValue' => 'ffffffff-ffff-1fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v2 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-2fff-cf00-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV2::class,
                'expectedUuidValue' => 'ffffffff-ffff-2fff-8f00-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v3 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-3fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV3::class,
                'expectedUuidValue' => 'ffffffff-ffff-3fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v4 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-4fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV4::class,
                'expectedUuidValue' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v5 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-5fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV5::class,
                'expectedUuidValue' => 'ffffffff-ffff-5fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v6 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-6fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV6::class,
                'expectedUuidValue' => 'ffffffff-ffff-6fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v7 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-7fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV7::class,
                'expectedUuidValue' => 'ffffffff-ffff-7fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
            'v8 GUID to UUID' => [
                'guidValue' => 'ffffffff-ffff-8fff-cfff-ffffffffffff',
                'expectedInstanceOf' => Uuid\UuidV8::class,
                'expectedUuidValue' => 'ffffffff-ffff-8fff-8fff-ffffffffffff',
                'expectedEquality' => false,
            ],
        ];
    }
}
