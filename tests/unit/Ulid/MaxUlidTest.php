<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

class MaxUlidTest extends TestCase
{
    use UlidComparisonTests;

    private const MAX_ULID = '7ZZZZZZZZZZZZZZZZZZZZZZZZZ';

    private Ulid\MaxUlid $maxUlid;
    private Ulid\MaxUlid $maxUlidWithString;
    private Ulid\MaxUlid $maxUlidWithHex;
    private Ulid\MaxUlid $maxUlidWithBytes;

    protected function setUp(): void
    {
        $this->maxUlid = new Ulid\MaxUlid();
        $this->maxUlidWithString = new Ulid\MaxUlid(self::MAX_ULID);
        $this->maxUlidWithHex = new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff');
        $this->maxUlidWithBytes = new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUlidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Max ULID: "%s"', $value));

        new Ulid\MaxUlid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUlidsProvider(): array
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
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithString);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithHex);
        $this->assertSame(self::MAX_ULID, (string) $this->maxUlidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:26:"7ZZZZZZZZZZZZZZZZZZZZZZZZZ";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:32:"ffffffffffffffffffffffffffffffff";}';
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:16:'
            . "\"\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\";}";
        $maxUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\MaxUlid::class, $maxUlid);
        $this->assertSame(self::MAX_ULID, (string) $maxUlid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max ULID: ""');

        unserialize($serialized);
    }

    #[TestWith(['a:0:{}'], 'an array')]
    #[TestWith(['i:1;'], 'an integer')]
    #[TestWith(['N;'], 'null')]
    public function testUnserializeFailsWhenUlidIsNotAString(string $value): void
    {
        $serialized = sprintf(
            'O:%d:"%s":1:{s:4:"ulid";%s}',
            strlen(MaxUlid::class),
            MaxUlid::class,
            $value,
        );

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "ulid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsWhenUlidIsMissing(): void
    {
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(MaxUlid::class), MaxUlid::class);

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The serialized data must have a string value for "ulid"');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\MaxUlid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Max ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Ulid\MaxUlid>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'without a value' => new Ulid\MaxUlid(),
            'from a string' => new Ulid\MaxUlid(self::MAX_ULID),
            'from a hexadecimal string' => new Ulid\MaxUlid('ffffffffffffffffffffffffffffffff'),
            'from bytes' => new Ulid\MaxUlid("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"),
        ];
    }

    /**
     * @return array<string, Ulid\Ulid>
     */
    protected static function equalValues(): array
    {
        return [
            'a Ulid with the same value' => new Ulid\Ulid(self::MAX_ULID),
        ];
    }

    /**
     * @return array<string, Ulid\Ulid | Ulid\NilUlid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser Ulid' => new Ulid\Ulid('01FWHE4YDGFK1SHH6W1G60EECF'),
            'the NilUlid' => new Ulid\NilUlid(),
            'a lesser ULID string' => '7ZZZZZZZZZZZZZZZZZZZZZZZZY',
            'a lesser ULID string with substitute letters' => '7ZZZZZZZZZZZZZZZZZZZZZZZOI',
        ];
    }

    /**
     * @return array{}
     */
    protected static function greaterValues(): array
    {
        return [];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlid));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithString));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithHex));
        $this->assertSame('"' . self::MAX_ULID . '"', json_encode($this->maxUlidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::MAX_ULID, $this->maxUlid->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithString->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithHex->toString());
        $this->assertSame(self::MAX_ULID, $this->maxUlidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUlid->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithString->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithHex->toBytes());
        $this->assertSame($bytes, $this->maxUlidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

        $this->assertSame($bytes, $this->maxUlid->getSortKey());
        $this->assertSame($bytes, $this->maxUlidWithString->getSortKey());
        $this->assertSame($bytes, $this->maxUlidWithHex->getSortKey());
        $this->assertSame($bytes, $this->maxUlidWithBytes->getSortKey());
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
     * @param non-empty-string $value
     * @param non-empty-string $expected
     */
    #[DataProvider('valuesForUppercaseConversionTestProvider')]
    public function testUppercaseConversion(string $value, string $expected): void
    {
        $ulid = new Ulid\MaxUlid($value);

        $this->assertTrue($ulid->equals($expected));
        $this->assertSame($expected, $ulid->toString());
    }

    /**
     * @return list<array{value: non-empty-string, expected: non-empty-string}>
     */
    public static function valuesForUppercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'expected' => self::MAX_ULID,
            ],
            [
                'value' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
                'expected' => self::MAX_ULID,
            ],
            [
                'value' => 'ffffffffffffffffffffffffffffffff',
                'expected' => self::MAX_ULID,
            ],
        ];
    }

    public function testToUuid(): void
    {
        $uuid = $this->maxUlid->toUuid()->toTypedUuid();

        $this->assertInstanceOf(MaxUuid::class, $uuid);
        $this->assertSame(0, $this->maxUlid->compareTo($uuid));
        $this->assertFalse($this->maxUlid->equals($uuid));
    }

    public function testToUuidV7(): void
    {
        $expectedBytes = "\xff\xff\xff\xff\xff\xff\x7f\xff\xbf\xff\xff\xff\xff\xff\xff\xff";
        $uuid = $this->maxUlid->toUuidV7();

        $this->assertSame($expectedBytes, $uuid->toBytes());
        $this->assertFalse($this->maxUlid->equals($uuid));
    }

    public function testMaxEpochDateTime(): void
    {
        $max = new Ulid\MaxUlid();

        $this->assertSame('10889-08-02T05:31:50.655Z', $max->getDateTime()->format('Y-m-d\TH:i:s.vp'));
    }
}
