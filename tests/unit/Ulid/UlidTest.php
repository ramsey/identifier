<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function sprintf;
use function unserialize;

class UlidTest extends TestCase
{
    use UlidComparisonTests;

    private const ULID_STRING = '01FWHE4YDGFK1SHH6W1G60EECF';
    private const ULID_HEX = '017f22e279b07cc398c4dc0c0c07398f';
    private const ULID_BYTES = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f";

    private Ulid $ulidWithString;
    private Ulid $ulidWithHex;
    private Ulid $ulidWithBytes;

    protected function setUp(): void
    {
        $this->ulidWithString = new Ulid(self::ULID_STRING);
        $this->ulidWithHex = new Ulid(self::ULID_HEX);
        $this->ulidWithBytes = new Ulid(self::ULID_BYTES);
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('invalidUlidsProvider')]
    public function testConstructorThrowsExceptionForInvalidUlid(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid ULID: "%s"', $value));

        new Ulid($value);
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

            // These contain invalid characters:
            ['value' => '00000000-0000-0000-0000-000000000000'],
            ['value' => '0000000000000000000000000000000g'],
            ['value' => '7ZZZZZZILOUZZZZZZZZZZZZZZZ'],

            // Out-of-bounds ULIDs:
            ['value' => '8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            ['value' => '80000000000000000000000000'],
        ];
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"01FWHE4YDGFK1SHH6W1G60EECF";}';
        $serialized = serialize($this->ulidWithString);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForStringWithExcludedCharacters(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"7Z1Z1Z1Z1Z1Z0Z0Z0ZZZZZZZZZ";}';

        $ulid = new Ulid('7Z1ZIZiZLZlZ0ZOZoZZZZZZZZZ');
        $serialized = serialize($ulid);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $serialized = serialize($this->ulidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $serialized = serialize($this->ulidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithString);
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithHex);
        $this->assertSame(self::ULID_STRING, (string) $this->ulidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"01FWHE4YDGFK1SHH6W1G60EECF";}';
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:32:"017f22e279b07cc398c4dc0c0c07398f";}';
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:16:'
            . "\"\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f\";}";
        $ulid = unserialize($serialized);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(self::ULID_STRING, (string) $ulid);
    }

    public function testUnserializeFailsWhenUlidIsAnEmptyString(): void
    {
        $serialized = 'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:0:"";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidUlid(): void
    {
        $serialized =
            'O:27:"Ramsey\\Identifier\\Ulid\\Ulid":1:{s:4:"ulid";s:26:"7ZZZZZZILOUZZZZZZZZZZZZZZZ";}';

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: "7ZZZZZZILOUZZZZZZZZZZZZZZZ"');

        unserialize($serialized);
    }

    /**
     * @return array<string, Ulid>
     */
    protected static function comparisonSubjects(): array
    {
        return [
            'from a string' => new Ulid(self::ULID_STRING),
            'from a hexadecimal string' => new Ulid(self::ULID_HEX),
            'from bytes' => new Ulid(self::ULID_BYTES),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function equalValues(): array
    {
        return [
            'a string with the substitute letters' => 'oiFWHE4YDGFKlSHH6W1G6OEECF',
            'a string with the substitute letters in uppercase' => 'OIFWHE4YDGFKLSHH6W1G6OEECF',
        ];
    }

    /**
     * @return array<string, Ulid | NilUlid | string>
     */
    protected static function lesserValues(): array
    {
        return [
            'a lesser Ulid' => new Ulid('00000000000000000000000001'),
            'the NilUlid' => new NilUlid(),
            'a lesser ULID string' => '01FWHE4YDGFK1SHH6W1G60EECE',
        ];
    }

    /**
     * @return array<string, Ulid | MaxUlid | string>
     */
    protected static function greaterValues(): array
    {
        return [
            'a greater Ulid' => new Ulid('7ZZZZZZZZZZZZZZZZZZZZZZZZY'),
            'the MaxUlid' => new MaxUlid(),
            'a greater ULID string' => '01FWHE4YDGFK1SHH6W1G60EECG',
            'a greater ULID string in lowercase' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
        ];
    }

    public function testUlidsFromBytesThatLookLikeNumbersAreNotEqual(): void
    {
        $first = new Ulid('0000000000000010');
        $second = new Ulid('00000000000001e1');

        $this->assertLessThan(0, $first->compareTo($second));
        $this->assertGreaterThan(0, $second->compareTo($first));
        $this->assertFalse($first->equals($second));
        $this->assertFalse($second->equals($first));
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithString));
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithHex));
        $this->assertSame('"' . self::ULID_STRING . '"', json_encode($this->ulidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(self::ULID_STRING, $this->ulidWithString->toString());
        $this->assertSame(self::ULID_STRING, $this->ulidWithHex->toString());
        $this->assertSame(self::ULID_STRING, $this->ulidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(self::ULID_BYTES, $this->ulidWithString->toBytes());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithHex->toBytes());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithBytes->toBytes());
    }

    public function testGetSortKey(): void
    {
        $this->assertSame(self::ULID_BYTES, $this->ulidWithString->getSortKey());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithHex->getSortKey());
        $this->assertSame(self::ULID_BYTES, $this->ulidWithBytes->getSortKey());
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(self::ULID_HEX, $this->ulidWithString->toHexadecimal());
        $this->assertSame(self::ULID_HEX, $this->ulidWithHex->toHexadecimal());
        $this->assertSame(self::ULID_HEX, $this->ulidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $int = '1989357241971137676463954034883508623';

        $this->assertSame($int, $this->ulidWithString->toInteger());
        $this->assertSame($int, $this->ulidWithHex->toInteger());
        $this->assertSame($int, $this->ulidWithBytes->toInteger());
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('valuesForUppercaseConversionTestProvider')]
    public function testUppercaseConversion(string $value, string $expected): void
    {
        $ulid = new Ulid($value);

        $this->assertTrue($ulid->equals($expected));
        $this->assertSame($expected, $ulid->toString());
    }

    /**
     * @return list<array{value: string, expected: string}>
     */
    public static function valuesForUppercaseConversionTestProvider(): array
    {
        return [
            [
                'value' => '01fwhe4ydgfk1shh6w1g60eecf',
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
            [
                'value' => '017f22e279b07cc398c4dc0c0c07398f',
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
            [
                'value' => "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f",
                'expected' => '01FWHE4YDGFK1SHH6W1G60EECF',
            ],
        ];
    }

    public function testGetDateTimeFromStringUlid(): void
    {
        $dateTime = $this->ulidWithString->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromHexUlid(): void
    {
        $dateTime = $this->ulidWithHex->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testGetDateTimeFromBytesUlid(): void
    {
        $dateTime = $this->ulidWithBytes->getDateTime();

        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }

    public function testConversionOfExcludedSymbols(): void
    {
        $ulid = new Ulid('7Z1ZIZiZLZlZ0ZOZoZZZZZZZZZ');

        $this->assertSame('7Z1Z1Z1Z1Z1Z0Z0Z0ZZZZZZZZZ', $ulid->toString());
    }

    public function testToHexadecimalAndBytesFromLowercaseUlid(): void
    {
        $ulid = new Ulid('01gg5qhjkp09jx275b1x4jj25f');

        $this->assertSame('01GG5QHJKP09JX275B1X4JJ25F', $ulid->toString());
        $this->assertSame('01840b78ca760265d11cab0f492908af', $ulid->toHexadecimal());
        $this->assertSame("\x01\x84\x0b\x78\xca\x76\x02\x65\xd1\x1c\xab\x0f\x49\x29\x08\xaf", $ulid->toBytes());
    }

    public function testToUuidWhenUlidBytesAreAlreadyForUuidVersion7(): void
    {
        $ulidBytes = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f";
        $ulid = new Ulid($ulidBytes);
        $uuid = $ulid->toUuid();

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame(0, $ulid->compareTo($uuid));
        $this->assertFalse($ulid->equals($uuid));
    }

    public function testToUuidWhenUlidBytesAreForNonstandardUuid(): void
    {
        $ulidBytes = "\x01\x7f\x22\xe2\x79\xb0\x4c\xc3\x68\xc4\xdc\x0c\x0c\x07\x39\x8f";
        $ulid = new Ulid($ulidBytes);
        $uuid = $ulid->toUuid();

        $this->assertInstanceOf(NonstandardUuid::class, $uuid);
        $this->assertSame(0, $ulid->compareTo($uuid));
        $this->assertFalse($ulid->equals($uuid));
    }

    public function testToUuidV7(): void
    {
        $expectedBytes = "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\xa8\xc4\xdc\x0c\x0c\x07\x39\x8f";

        $ulidBytes = "\x01\x7f\x22\xe2\x79\xb0\x4c\xc3\x68\xc4\xdc\x0c\x0c\x07\x39\x8f";
        $ulid = new Ulid($ulidBytes);
        $uuid = $ulid->toUuidV7();

        $this->assertSame($expectedBytes, $uuid->toBytes());
        $this->assertFalse($ulid->equals($uuid));
    }
}
