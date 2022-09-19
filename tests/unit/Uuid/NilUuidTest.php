<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use BadMethodCallException;
use Identifier\Uuid\Variant;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function strtoupper;
use function unserialize;

class NilUuidTest extends TestCase
{
    private Uuid\NilUuid $nilUuid;
    private Uuid\NilUuid $nilUuidWithString;
    private Uuid\NilUuid $nilUuidWithHex;
    private Uuid\NilUuid $nilUuidWithBytes;

    protected function setUp(): void
    {
        $this->nilUuid = new Uuid\NilUuid();
        $this->nilUuidWithString = new Uuid\NilUuid('00000000-0000-0000-0000-000000000000');
        $this->nilUuidWithHex = new Uuid\NilUuid('00000000000000000000000000000000');
        $this->nilUuidWithBytes = new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: ""');

        new Uuid\NilUuid('');
    }

    public function testConstructorThrowsExceptionForInvalidStringUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        new Uuid\NilUuid('a6a011d2-7433-9d43-9161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidHexUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        new Uuid\NilUuid('a6a011d274339d4391611550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidBytesUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        new Uuid\NilUuid("\xa6\xa0\x11\xd2\x74\x33\x9d\x43\x91\x61\x15\x50\x86\x37\x92\xc9");
    }

    public function testSerializeForString(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $serialized = serialize($this->nilUuid);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForHex(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:32:"00000000000000000000000000000000";}';
        $serialized = serialize($this->nilUuidWithHex);

        $this->assertSame($expected, $serialized);
    }

    public function testSerializeForBytes(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $serialized = serialize($this->nilUuidWithBytes);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(Uuid::NIL, (string) $this->nilUuid);
        $this->assertSame(Uuid::NIL, (string) $this->nilUuidWithString);
        $this->assertSame(Uuid::NIL, (string) $this->nilUuidWithHex);
        $this->assertSame(Uuid::NIL, (string) $this->nilUuidWithBytes);
    }

    public function testUnserializeForString(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(Uuid::NIL, (string) $nilUuid);
    }

    public function testUnserializeForHex(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:32:"00000000000000000000000000000000";}';
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(Uuid::NIL, (string) $nilUuid);
    }

    public function testUnserializeForBytes(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:16:'
            . "\"\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\";}";
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(Uuid::NIL, (string) $nilUuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Nil UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->nilUuid->compareTo($other));
        $this->assertSame($expected, $this->nilUuidWithString->compareTo($other));
        $this->assertSame($expected, $this->nilUuidWithHex->compareTo($other));
        $this->assertSame($expected, $this->nilUuidWithBytes->compareTo($other));
    }

    /**
     * @return array<string, array{mixed, int}>
     */
    public function compareToProvider(): array
    {
        return [
            'with null' => [null, 1],
            'with int' => [123, -1],
            'with float' => [123.456, -1],
            'with string' => ['foobar', -1],
            'with string Nil UUID' => [Uuid::NIL, 0],
            'with hex Nil UUID' => ['00000000000000000000000000000000', 0],
            'with bytes Nil UUID' => ["\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 0],
            'with string Max UUID' => [Uuid::MAX, -1],
            'with string Max UUID all caps' => [strtoupper(Uuid::MAX), -1],
            'with bool true' => [true, -1],
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
                new class {
                    public function __toString(): string
                    {
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                0,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), 0],
            'with NilUuid from string' => [new Uuid\NilUuid('00000000-0000-0000-0000-000000000000'), 0],
            'with NilUuid from hex' => [new Uuid\NilUuid('00000000000000000000000000000000'), 0],
            'with NilUuid from bytes' => [
                new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                0,
            ],
            'with MaxUuid' => [new Uuid\MaxUuid(), -1],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->nilUuid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->nilUuid->equals($other));
        $this->assertSame($expected, $this->nilUuidWithString->equals($other));
        $this->assertSame($expected, $this->nilUuidWithHex->equals($other));
        $this->assertSame($expected, $this->nilUuidWithBytes->equals($other));
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
            'with string Nil UUID' => [Uuid::NIL, true],
            'with hex Nil UUID' => ['00000000000000000000000000000000', true],
            'with bytes Nil UUID' => ["\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", true],
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
                new class {
                    public function __toString(): string
                    {
                        return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
                    }
                },
                true,
            ],
            'with NilUuid' => [new Uuid\NilUuid(), true],
            'with NilUuid from string' => [new Uuid\NilUuid('00000000-0000-0000-0000-000000000000'), true],
            'with NilUuid from hex' => [new Uuid\NilUuid('00000000000000000000000000000000'), true],
            'with NilUuid from bytes' => [
                new Uuid\NilUuid("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                true,
            ],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->nilUuid->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithString->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithHex->getVariant());
        $this->assertSame(Variant::Rfc4122, $this->nilUuidWithBytes->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Nil UUIDs do not have a version field');

        $this->nilUuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . Uuid::NIL . '"', json_encode($this->nilUuid));
        $this->assertSame('"' . Uuid::NIL . '"', json_encode($this->nilUuidWithString));
        $this->assertSame('"' . Uuid::NIL . '"', json_encode($this->nilUuidWithHex));
        $this->assertSame('"' . Uuid::NIL . '"', json_encode($this->nilUuidWithBytes));
    }

    public function testToString(): void
    {
        $this->assertSame(Uuid::NIL, $this->nilUuid->toString());
        $this->assertSame(Uuid::NIL, $this->nilUuidWithString->toString());
        $this->assertSame(Uuid::NIL, $this->nilUuidWithHex->toString());
        $this->assertSame(Uuid::NIL, $this->nilUuidWithBytes->toString());
    }

    public function testToBytes(): void
    {
        $bytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

        $this->assertSame($bytes, $this->nilUuid->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithString->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithHex->toBytes());
        $this->assertSame($bytes, $this->nilUuidWithBytes->toBytes());
    }

    public function testToHexadecimal(): void
    {
        $hex = '00000000000000000000000000000000';

        $this->assertSame($hex, $this->nilUuid->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithString->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithHex->toHexadecimal());
        $this->assertSame($hex, $this->nilUuidWithBytes->toHexadecimal());
    }

    public function testToInteger(): void
    {
        $this->assertSame('0', $this->nilUuid->toInteger());
        $this->assertSame('0', $this->nilUuidWithString->toInteger());
        $this->assertSame('0', $this->nilUuidWithHex->toInteger());
        $this->assertSame('0', $this->nilUuidWithBytes->toInteger());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . Uuid::NIL, $this->nilUuid->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::NIL, $this->nilUuidWithString->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::NIL, $this->nilUuidWithHex->toUrn());
        $this->assertSame('urn:uuid:' . Uuid::NIL, $this->nilUuidWithBytes->toUrn());
    }

    /**
     * Yes, yes. There's no real "lowercase" conversion with a Nil UUID, but
     * we're also testing the equality/comparison check with hex/bytes.
     *
     * @dataProvider valuesForLowercaseConversionTestProvider
     */
    public function testLowercaseConversion(string $value, string $expected): void
    {
        $uuid = new Uuid\NilUuid($value);

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
                'value' => '00000000-0000-0000-0000-000000000000',
                'expected' => '00000000-0000-0000-0000-000000000000',
            ],
            [
                'value' => '00000000000000000000000000000000',
                'expected' => '00000000-0000-0000-0000-000000000000',
            ],
            [
                'value' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'expected' => '00000000-0000-0000-0000-000000000000',
            ],
        ];
    }
}
