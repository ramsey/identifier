<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class UuidV6Test extends TestCase
{
    use UuidComparisonTests;

    private const UUID_V6_STRING = 'a6a011d2-7433-6d43-9161-1550863792c9';
    private const UUID_V6_HEX = 'a6a011d274336d4391611550863792c9';
    private const UUID_V6_BYTES = "\xa6\xa0\x11\xd2\x74\x33\x6d\x43\x91\x61\x15\x50\x86\x37\x92\xc9";

    private Uuid\UuidV6 $uuidWithString;
    private Uuid\UuidV6 $uuidWithHex;
    private Uuid\UuidV6 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV6(self::UUID_V6_STRING);
        $this->uuidWithHex = new Uuid\UuidV6(self::UUID_V6_HEX);
        $this->uuidWithBytes = new Uuid\UuidV6(self::UUID_V6_BYTES);
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 6 UUID: "%s"', $value));

        new Uuid\UuidV6($value);
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

            // Valid version 7 UUID:
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff7fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x7f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

            // Valid version 8 UUID:
            ['value' => 'ffffffff-ffff-8fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff8fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],

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
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:36:"a6a011d2-7433-6d43-9161-1550863792c9";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:32:"a6a011d274336d4391611550863792c9";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:16:'
            . "\"\xa6\xa0\x11\xd2\x74\x33\x6d\x43\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V6_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V6_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V6_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:36:"a6a011d2-7433-6d43-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV6::class, $uuid);
        $this->assertSame(self::UUID_V6_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:32:"a6a011d274336d4391611550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV6::class, $uuid);
        $this->assertSame(self::UUID_V6_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:16:'
            . "\"\xa6\xa0\x11\xd2\x74\x33\x6d\x43\x91\x61\x15\x50\x86\x37\x92\xc9\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV6::class, $uuid);
        $this->assertSame(self::UUID_V6_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 6 UUID: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['i:1;'], 'an integer')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenUuidIsNotAString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:4:"uuid";%s}',
            strlen(UuidV6::class),
            UuidV6::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenUuidIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(UuidV6::class), UuidV6::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV6":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 6 UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Uuid\UuidV6>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from a string' => new Uuid\UuidV6(self::UUID_V6_STRING),
            'from a hexadecimal string' => new Uuid\UuidV6(self::UUID_V6_HEX),
            'from bytes' => new Uuid\UuidV6(self::UUID_V6_BYTES),
        ];
    }

    /**
     * @return array<string, Uuid\UntypedUuid | Uuid\MicrosoftGuid>
     */
    protected static function equalValues(): array
    {
        return [
            'an UntypedUuid with the same value' => new Uuid\UntypedUuid(self::UUID_V6_STRING),
            'a MicrosoftGuid with the same string form' => new Uuid\MicrosoftGuid(self::UUID_V6_STRING),
        ];
    }

    /**
     * @return array<string, Uuid\UuidV6 | Uuid\NilUuid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser UuidV6' => new Uuid\UuidV6('00000000-0000-6000-8000-000000000000'),
            'the NilUuid' => new Uuid\NilUuid(),
            'a lesser UUID string' => '00000000-0000-0000-0000-000000000000',
        ];
    }

    /**
     * @return array<string, Uuid\UuidV6 | Uuid\MaxUuid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater UuidV6' => new Uuid\UuidV6('ffffffff-ffff-6fff-bfff-ffffffffffff'),
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
        $this->assertSame(Version::ReorderedGregorianTime, $this->uuidWithString->getVersion());
        $this->assertSame(Version::ReorderedGregorianTime, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::ReorderedGregorianTime, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V6_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V6_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V6_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V6_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V6_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V6_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithString->getSortKey());
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithHex->getSortKey());
        $this->assertSame(self::UUID_V6_BYTES, $this->uuidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V6_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V6_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V6_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '221482976272501429736935490600400556745';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V6_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V6_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V6_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV6($value);

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
                'value' => 'A6A011D2-7433-6D43-9161-1550863792C9',
                'expected' => 'a6a011d2-7433-6d43-9161-1550863792c9',
            ],
            [
                'value' => 'A6A011D274336D4391611550863792C9',
                'expected' => 'a6a011d2-7433-6d43-9161-1550863792c9',
            ],
            [
                'value' => "\xA6\xA0\x11\xD2\x74\x33\x6D\x43\x91\x61\x15\x50\x86\x37\x92\xC9",
                'expected' => 'a6a011d2-7433-6d43-9161-1550863792c9',
            ],
        ];
    }

    public function testGetDateTimeFromStringUuid(): void
    {
        $dateTime = $this->uuidWithString->getDateTime();

        $this->assertSame('3960-10-02 03:47:43.500628', $dateTime->format('Y-m-d H:i:s.u'));
    }

    public function testGetDateTimeFromHexUuid(): void
    {
        $dateTime = $this->uuidWithHex->getDateTime();

        $this->assertSame('3960-10-02 03:47:43.500628', $dateTime->format('Y-m-d H:i:s.u'));
    }

    public function testGetDateTimeFromBytesUuid(): void
    {
        $dateTime = $this->uuidWithBytes->getDateTime();

        $this->assertSame('3960-10-02 03:47:43.500628', $dateTime->format('Y-m-d H:i:s.u'));
    }

    public function testGetNode(): void
    {
        $this->assertSame('1550863792c9', $this->uuidWithString->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithHex->getNode());
        $this->assertSame('1550863792c9', $this->uuidWithBytes->getNode());
    }

    public function testMaximumDate(): void
    {
        $uuid = new Uuid\UuidV6('ffffffff-ffff-6fff-bfff-ffffffffffff');

        $this->assertSame('5236-03-31 21:21:00.684698', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
    }

    public function testMinimumDate(): void
    {
        $uuid = new Uuid\UuidV6('00000000-0000-6000-bfff-ffffffffffff');

        $this->assertSame('1582-10-15 00:00:00.000000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
    }
}
