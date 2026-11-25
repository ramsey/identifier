<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class MaxUuidTest extends TestCase
{
    use UuidComparisonTests;

    private const string MAX_UUID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    private Uuid\MaxUuid $maxUuid;
    private Uuid\MaxUuid $maxUuidWithString;
    private Uuid\MaxUuid $maxUuidWithHex;
    private Uuid\MaxUuid $maxUuidWithBytes;

    protected function setUp(): void
    {
        $this->maxUuid = new Uuid\MaxUuid();
        $this->maxUuidWithString = new Uuid\MaxUuid(self::MAX_UUID);
        $this->maxUuidWithHex = new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff');
        $this->maxUuidWithBytes = new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUuidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Max UUID: "%s"', $value));

        new Uuid\MaxUuid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUuidsProvider(): array
    {
        return [
            ['value' => ''],
            ['value' => '0'],

            // This is 35 characters:
            ['value' => 'ffffffff-ffff-ffff-ffff-fffffffffff'],

            // This is 31 characters:
            ['value' => 'ffffffffffffffffffffffffffffff'],

            // This is 15 bytes:
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // These 16 bytes don't form a standard UUID:
            ['value' => 'foobarbazquux123'],

            // These contain invalid characters:
            ['value' => 'ffffffff-ffff-ffff-ffff-fffffffffffg'],
            ['value' => 'fffffffffffffffffffffffffffffffg'],
            ['value' => 'ffffffff-ffff-ffff-ffff-ffffffff'],

            // Valid Nil UUID:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '00000000000000000000000000000000'],
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

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

            // Valid version 8 UUID:
            ['value' => 'ffffffff-ffff-8fff-9fff-ffffffffffff'],
            ['value' => 'ffffffffffff8fff9fffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\x8f\xff\x9f\xff\xff\xff\xff\xff\xff\xff"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $serialized = serialize($this->maxUuidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $serialized = serialize($this->maxUuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $serialized = serialize($this->maxUuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithString);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithHex);
        $this->assertSame(self::MAX_UUID, (string) $this->maxUuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"ffffffff-ffff-ffff-ffff-ffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\MaxUuid::class, $maxUuid);
        $this->assertSame(self::MAX_UUID, (string) $maxUuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max UUID: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['i:1;'], 'an integer')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenUuidIsNotAString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:4:"uuid";%s}',
            strlen(MaxUuid::class),
            MaxUuid::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenUuidIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(MaxUuid::class), MaxUuid::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "uuid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\MaxUuid":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Uuid\MaxUuid>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'without a value' => new Uuid\MaxUuid(),
            'from a string' => new Uuid\MaxUuid(self::MAX_UUID),
            'from a hexadecimal string' => new Uuid\MaxUuid('ffffffffffffffffffffffffffffffff'),
            'from bytes' => new Uuid\MaxUuid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
        ];
    }

    /**
     * @return array<string, Uuid\UntypedUuid>
     */
    protected static function equalValues(): array
    {
        return [
            'an UntypedUuid with the same value' => new Uuid\UntypedUuid(self::MAX_UUID),
        ];
    }

    /**
     * @return array<string, Uuid\UuidV4 | Uuid\NilUuid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser UuidV4' => new Uuid\UuidV4('27433d43-011d-4a6a-9161-1550863792c9'),
            'the NilUuid' => new Uuid\NilUuid(),
            'a lesser UUID string' => 'ffffffff-ffff-ffff-ffff-fffffffffffe',
        ];
    }

    /**
     * @return array{}
     */
    protected static function greaterValues(): array
    {
        return [];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Future, $this->maxUuid->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithString->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithHex->getVariant());
        $this->assertSame(Variant::Future, $this->maxUuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('Max UUIDs do not have a version field');

        $this->maxUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuid));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithString));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithHex));
        $this->assertSame('"' . self::MAX_UUID . '"', json_encode($this->maxUuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::MAX_UUID, $this->maxUuid->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithString->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithHex->toString());
        $this->assertSame(self::MAX_UUID, $this->maxUuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUuid->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithString->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithHex->toBytes());
        $this->assertSame($bytes, $this->maxUuidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUuid->getSortKey());
        $this->assertSame($bytes, $this->maxUuidWithString->getSortKey());
        $this->assertSame($bytes, $this->maxUuidWithHex->getSortKey());
        $this->assertSame($bytes, $this->maxUuidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $hex = 'ffffffffffffffffffffffffffffffff';

        $this->assertSame($hex, $this->maxUuid->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->maxUuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '340282366920938463463374607431768211455';

        $this->assertSame($int, $this->maxUuid->toInteger());
        $this->assertSame($int, $this->maxUuidWithString->toInteger());
        $this->assertSame($int, $this->maxUuidWithHex->toInteger());
        $this->assertSame($int, $this->maxUuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuid->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . self::MAX_UUID, $this->maxUuidWithBytes->toUrn());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForLowercaseConversionTestProvider')]
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\MaxUuid($value);

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
                'value' => 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
                'expected' => self::MAX_UUID,
            ],
            [
                'value' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF',
                'expected' => self::MAX_UUID,
            ],
            [
                'value' => "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF",
                'expected' => self::MAX_UUID,
            ],
        ];
    }
}
