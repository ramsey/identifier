<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class NilUlidTest extends TestCase
{
    use UlidComparisonTests;

    private const NIL_ULID = '00000000000000000000000000';

    private Ulid\NilUlid $nilUlid;
    private Ulid\NilUlid $nilUlidWithString;
    private Ulid\NilUlid $nilUlidWithHex;
    private Ulid\NilUlid $nilUlidWithBytes;

    protected function setUp(): void
    {
        $this->nilUlid = new Ulid\NilUlid();
        $this->nilUlidWithString = new Ulid\NilUlid(self::NIL_ULID);
        $this->nilUlidWithHex = new Ulid\NilUlid('00000000000000000000000000000000');
        $this->nilUlidWithBytes = new Ulid\NilUlid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUlidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Nil ULID: "%s"', $value));

        new Ulid\NilUlid($value);
    }

    /**
     * @return list<array{value: string, messageValue?: string}>
     */
    public static function invalidUlidsProvider(): array
    {
        return [
            ['value' => ''],

            // This is 25 characters:
            ['value' => '0000000000000000000000000'],

            // This is 31 characters:
            ['value' => '0000000000000000000000000000000'],

            // This is 15 bytes:
            ['value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // These 16 bytes don't form a standard ULID:
            ['value' => 'foobarbazquux123'],

            // This is out of bounds for a ULID:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],

            // These contain invalid characters:
            ['value' => '7ZZZZZILOUZZZZZZZZZZZZZZZZ'],
            ['value' => 'fffffffffffffffffffffffffffffffg'],
            ['value' => 'ffffffff-ffff-7fff-9fff-ffffffffffff'],

            // Valid Max ULID:
            ['value' => '7ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            ['value' => 'ffffffffffffffffffffffffffffffff'],
            ['value' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"],

            // Valid dULID:
            ['value' => '01BX5ZZKBKACTAV9WEVGEMMVS0'],
            ['value' => '015f4bffcd735334ada78edc1d4a6f20'],
            ['value' => "\x01\x5f\x4b\xff\xcd\x73\x53\x34\xad\xa7\x8e\xdc\x1d\x4a\x6f\x20"],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:26:"00000000000000000000000000";}';
        $serialized = serialize($this->nilUlidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:32:"00000000000000000000000000000000";}';
        $serialized = serialize($this->nilUlidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $serialized = serialize($this->nilUlidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithString);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithHex);
        $this->assertSame(self::NIL_ULID, (string) $this->nilUlidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:26:"00000000000000000000000000";}';
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:32:"00000000000000000000000000000000";}';
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $nilUlid = unserialize($serialized);

        $this->assertInstanceOf(Ulid\NilUlid::class, $nilUlid);
        $this->assertSame(self::NIL_ULID, (string) $nilUlid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Ulid\\NilUlid":1:{s:4:"ulid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Nil ULID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Ulid\NilUlid>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'without a value' => new Ulid\NilUlid(),
            'from a string' => new Ulid\NilUlid(self::NIL_ULID),
            'from a hexadecimal string' => new Ulid\NilUlid('00000000000000000000000000000000'),
            'from bytes' => new Ulid\NilUlid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
        ];
    }

    /**
     * @return array<string, Ulid\Ulid | string>
     */
    protected static function equalValues(): array
    {
        return [
            'a Ulid with the same value' => new Ulid\Ulid(self::NIL_ULID),
            'a string with the substitute letters' => 'OOOOOOOOOOOOOOOOOOOOOOOOOO',
            'a string with the substitute letters in lowercase' => 'oooooooooooooooooooooooooo',
        ];
    }

    /**
     * @return array{}
     */
    protected static function lesserValues(): array
    {
        return [];
    }

    /**
     * @return array<string, Ulid\Ulid | Ulid\MaxUlid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater Ulid' => new Ulid\Ulid('01FWHE4YDGFK1SHH6W1G60EECF'),
            'the MaxUlid' => new Ulid\MaxUlid(),
            'a greater ULID string' => '00000000000000000000000001',
            'a greater ULID string with a substitute letter' => '0000000000000000000000000i',
        ];
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlid));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithString));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithHex));
        $this->assertSame('"' . self::NIL_ULID . '"', json_encode($this->nilUlidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::NIL_ULID, $this->nilUlid->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithString->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithHex->toString());
        $this->assertSame(self::NIL_ULID, $this->nilUlidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

        $this->assertSame($bytes, $this->nilUlid->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithString->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithHex->toBytes());
        $this->assertSame($bytes, $this->nilUlidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $bytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

        $this->assertSame($bytes, $this->nilUlid->getSortKey());
        $this->assertSame($bytes, $this->nilUlidWithString->getSortKey());
        $this->assertSame($bytes, $this->nilUlidWithHex->getSortKey());
        $this->assertSame($bytes, $this->nilUlidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $hex = '00000000000000000000000000000000';

        $this->assertSame($hex, $this->nilUlid->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithString->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->nilUlidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = 0;

        $this->assertSame($int, $this->nilUlid->toInteger());
        $this->assertSame($int, $this->nilUlidWithString->toInteger());
        $this->assertSame($int, $this->nilUlidWithHex->toInteger());
        $this->assertSame($int, $this->nilUlidWithBytes->toInteger());
    }

    public function testToUuid(): void
    {
        $uuid = $this->nilUlid->toUuid()->toTypedUuid();

        $this->assertInstanceOf(NilUuid::class, $uuid);
        $this->assertSame(0, $this->nilUlid->compareTo($uuid));
        $this->assertFalse($this->nilUlid->equals($uuid));
    }

    public function testToUuidV7(): void
    {
        $expectedBytes = "\x00\x00\x00\x00\x00\x00\x70\x00\x80\x00\x00\x00\x00\x00\x00\x00";
        $uuid = $this->nilUlid->toUuidV7();

        $this->assertSame($expectedBytes, $uuid->toBytes());
        $this->assertFalse($this->nilUlid->equals($uuid));
    }

    public function testNilEpochDateTime(): void
    {
        $nil = new Ulid\NilUlid();

        $this->assertSame('1970-01-01T00:00:00.000Z', $nil->getDateTime()->format('Y-m-d\TH:i:s.vp'));
    }
}
