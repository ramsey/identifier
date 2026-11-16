<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class UuidV7Test extends TestCase
{
    use UuidComparisonTests;

    private const UUID_V7_STRING = '017f22e2-79b0-7cc3-98c4-dc0c0c07398f';
    private const UUID_V7_HEX = '017f22e279b07cc398c4dc0c0c07398f';
    private const UUID_V7_BYTES = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f";

    private Uuid\UuidV7 $uuidWithString;
    private Uuid\UuidV7 $uuidWithHex;
    private Uuid\UuidV7 $uuidWithBytes;

    protected function setUp(): void
    {
        $this->uuidWithString = new Uuid\UuidV7(self::UUID_V7_STRING);
        $this->uuidWithHex = new Uuid\UuidV7(self::UUID_V7_HEX);
        $this->uuidWithBytes = new Uuid\UuidV7(self::UUID_V7_BYTES);
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid version 7 UUID: "%s"', $value));

        new Uuid\UuidV7($value);
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
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"017f22e2-79b0-7cc3-98c4-dc0c0c07398f";}';
        $serialized = serialize($this->uuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $serialized = serialize($this->uuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $serialized = serialize($this->uuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V7_STRING, (string) $this->uuidWithString);
        $this->assertSame(self::UUID_V7_STRING, (string) $this->uuidWithHex);
        $this->assertSame(self::UUID_V7_STRING, (string) $this->uuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"017f22e2-79b0-7cc3-98c4-dc0c0c07398f";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV7::class, $uuid);
        $this->assertSame(self::UUID_V7_STRING, (string) $uuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV7::class, $uuid);
        $this->assertSame(self::UUID_V7_STRING, (string) $uuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV7::class, $uuid);
        $this->assertSame(self::UUID_V7_STRING, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Uuid\UuidV7>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from a string' => new Uuid\UuidV7(self::UUID_V7_STRING),
            'from a hexadecimal string' => new Uuid\UuidV7(self::UUID_V7_HEX),
            'from bytes' => new Uuid\UuidV7(self::UUID_V7_BYTES),
        ];
    }

    /**
     * @return array<string, Uuid\UntypedUuid | Uuid\MicrosoftGuid>
     */
    protected static function equalValues(): array
    {
        return [
            'an UntypedUuid with the same value' => new Uuid\UntypedUuid(self::UUID_V7_STRING),
            'a MicrosoftGuid with the same string form' => new Uuid\MicrosoftGuid(self::UUID_V7_STRING),
        ];
    }

    /**
     * @return array<string, Uuid\UuidV7 | Uuid\NilUuid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser UuidV7' => new Uuid\UuidV7('00000000-0000-7000-8000-000000000000'),
            'the NilUuid' => new Uuid\NilUuid(),
            'a lesser UUID string' => '00000000-0000-0000-0000-000000000000',
        ];
    }

    /**
     * @return array<string, Uuid\UuidV7 | Uuid\MaxUuid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater UuidV7' => new Uuid\UuidV7('ffffffff-ffff-7fff-bfff-ffffffffffff'),
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
        $this->assertSame(Version::UnixTime, $this->uuidWithString->getVersion());
        $this->assertSame(Version::UnixTime, $this->uuidWithHex->getVersion());
        $this->assertSame(Version::UnixTime, $this->uuidWithBytes->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V7_STRING . '"', json_encode($this->uuidWithString));
        $this->assertSame('"' . self::UUID_V7_STRING . '"', json_encode($this->uuidWithHex));
        $this->assertSame('"' . self::UUID_V7_STRING . '"', json_encode($this->uuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V7_STRING, $this->uuidWithString->toString());
        $this->assertSame(self::UUID_V7_STRING, $this->uuidWithHex->toString());
        $this->assertSame(self::UUID_V7_STRING, $this->uuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithString->toBytes());
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithHex->toBytes());
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithString->getSortKey());
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithHex->getSortKey());
        $this->assertSame(self::UUID_V7_BYTES, $this->uuidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::UUID_V7_HEX, $this->uuidWithString->toHexadecimal());
        $this->assertSame(self::UUID_V7_HEX, $this->uuidWithHex->toHexadecimal());
        $this->assertSame(self::UUID_V7_HEX, $this->uuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '1989357241971137676463954034883508623';

        $this->assertSame($int, $this->uuidWithString->toInteger());
        $this->assertSame($int, $this->uuidWithHex->toInteger());
        $this->assertSame($int, $this->uuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V7_STRING, $this->uuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V7_STRING, $this->uuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::UUID_V7_STRING, $this->uuidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\UuidV7($value);

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
                'value' => '017F22E2-79B0-7CC3-98C4-DC0C0C07398F',
                'expected' => '017f22e2-79b0-7cc3-98c4-dc0c0c07398f',
            ],
            [
                'value' => '017F22E279B07CC398C4DC0C0C07398F',
                'expected' => '017f22e2-79b0-7cc3-98c4-dc0c0c07398f',
            ],
            [
                'value' => "\x01\x7F\x22\xE2\x79\xB0\x7C\xC3\x98\xC4\xDC\x0C\x0C\x07\x39\x8F",
                'expected' => '017f22e2-79b0-7cc3-98c4-dc0c0c07398f',
            ],
        ];
    }

    public function testGetDateTimeFromStringUuid(): void
    {
        $dateTime = $this->uuidWithString->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromHexUuid(): void
    {
        $dateTime = $this->uuidWithHex->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromBytesUuid(): void
    {
        $dateTime = $this->uuidWithBytes->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testMaximumDate(): void
    {
        $uuid = new Uuid\UuidV7('ffffffff-ffff-7fff-bfff-ffffffffffff');

        $this->assertSame('10889-08-02 05:31:50.655000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
    }

    public function testMinimumDate(): void
    {
        $uuid = new Uuid\UuidV7('00000000-0000-7fff-bfff-ffffffffffff');

        $this->assertSame('1970-01-01 00:00:00.000000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
    }
}
