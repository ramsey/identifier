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

class NonstandardUuidTest extends TestCase
{
    private const UUID_NONSTANDARD = '27433d43-011d-0a6a-0161-1550863792c9';

    private Uuid\NonstandardUuid $uuid;

    protected function setUp(): void
    {
        $this->uuid = new Uuid\NonstandardUuid(self::UUID_NONSTANDARD);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: ""');

        new Uuid\NonstandardUuid('');
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

        new Uuid\NonstandardUuid('27433d43-011d-4a6a-a161-1550863792c9');
    }

    public function testSerialize(): void
    {
        $expected = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-0a6a-0161-1550863792c9";}';
        $serialized = serialize($this->uuid);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_NONSTANDARD, (string) $this->uuid);
    }

    public function testUnserialize(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-0a6a-0161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NonstandardUuid::class, $uuid);
        $this->assertSame(self::UUID_NONSTANDARD, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidNonstandardUuid(): void
    {
        $serialized = 'O:38:"Ramsey\\Identifier\\Uuid\\NonstandardUuid":1:'
            . '{s:4:"uuid";s:36:"27433d43-011d-4a6a-a161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nonstandard UUID: "27433d43-011d-4a6a-a161-1550863792c9"');

        unserialize($serialized);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->uuid->compareTo($other));
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
            'with same string UUID' => [self::UUID_NONSTANDARD, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_NONSTANDARD), 0],
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
            'with NilUuid' => [new Uuid\NilUuid(), 1],
            'with same NonstandardUuid' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD), 0],
            'with MaxUuid' => [new Uuid\MaxUuid(), -1],
        ];
    }

    public function testCompareToThrowsExceptionWhenNotComparable(): void
    {
        $this->expectException(NotComparableException::class);
        $this->expectExceptionMessage('Comparison with values of type "array" is not supported');

        $this->uuid->compareTo([]);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(mixed $other, bool $expected): void
    {
        $this->assertSame($expected, $this->uuid->equals($other));
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
            'with same string UUID' => [self::UUID_NONSTANDARD, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_NONSTANDARD), true],
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
            'with NilUuid' => [new Uuid\NilUuid(), false],
            'with same NonstandardUuid' => [new Uuid\NonstandardUuid(self::UUID_NONSTANDARD), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::ReservedNcs, $this->uuid->getVariant());
    }

    public function testGetVersionThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Nonstandard UUIDs do not have a version field');

        $this->uuid->getVersion();
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_NONSTANDARD . '"', json_encode($this->uuid));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_NONSTANDARD, $this->uuid->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(
            "\x27\x43\x3d\x43\x01\x1d\x0a\x6a\x01\x61\x15\x50\x86\x37\x92\xc9",
            $this->uuid->toBytes(),
        );
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(
            '27433d43011d0a6a01611550863792c9',
            $this->uuid->toHexadecimal(),
        );
    }

    public function testToInteger(): void
    {
        $this->assertSame(
            '52189018260750856739675752081592390345',
            $this->uuid->toInteger(),
        );
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_NONSTANDARD, $this->uuid->toUrn());
    }

    /**
     * @dataProvider allVariantsProvider
     */
    public function testAllVariants(string $uuid, Variant $expected): void
    {
        $uuid = new Uuid\NonstandardUuid($uuid);

        $this->assertSame($expected, $uuid->getVariant());
    }

    /**
     * @return array<string, array{string, Variant}>
     */
    public function allVariantsProvider(): array
    {
        return [
            'reserved NCS: 0' => ['27433d43-011d-0a6a-0161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 1' => ['27433d43-011d-0a6a-1161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 2' => ['27433d43-011d-0a6a-2161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 3' => ['27433d43-011d-0a6a-3161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 4' => ['27433d43-011d-0a6a-4161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 5' => ['27433d43-011d-0a6a-5161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 6' => ['27433d43-011d-0a6a-6161-1550863792c9', Variant::ReservedNcs],
            'reserved NCS: 7' => ['27433d43-011d-0a6a-7161-1550863792c9', Variant::ReservedNcs],
            'RFC 4122: 8, version 0' => ['27433d43-011d-0a6a-8161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 9, version 0' => ['27433d43-011d-0a6a-9161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: a, version 0' => ['27433d43-011d-0a6a-a161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: b, version 0' => ['27433d43-011d-0a6a-b161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 8, version 9' => ['27433d43-011d-9a6a-8161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 9, version a' => ['27433d43-011d-aa6a-9161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: a, version b' => ['27433d43-011d-ba6a-a161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: b, version c' => ['27433d43-011d-ca6a-b161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 8, version d' => ['27433d43-011d-da6a-8161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 8, version e' => ['27433d43-011d-ea6a-8161-1550863792c9', Variant::Rfc4122],
            'RFC 4122: 8, version f' => ['27433d43-011d-fa6a-8161-1550863792c9', Variant::Rfc4122],
            'reserved Microsoft: c' => ['27433d43-011d-0a6a-c161-1550863792c9', Variant::ReservedMicrosoft],
            'reserved Microsoft: d' => ['27433d43-011d-0a6a-d161-1550863792c9', Variant::ReservedMicrosoft],
            'reserved future: e' => ['27433d43-011d-0a6a-e161-1550863792c9', Variant::ReservedFuture],
            'reserved future: f' => ['27433d43-011d-0a6a-f161-1550863792c9', Variant::ReservedFuture],
        ];
    }

    /**
     * @dataProvider invalidNonstandardProvider
     */
    public function testInvalidNonstandard(string $uuid): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid nonstandard UUID: \"$uuid\"");

        new Uuid\NonstandardUuid($uuid);
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidNonstandardProvider(): array
    {
        return [
            // The variant character is "8".
            'RFC 4122, version 1, variant "8"' => ['27433d43-011d-1a6a-8161-1550863792c9'],
            'RFC 4122, version 2, variant "8"' => ['27433d43-011d-2a6a-8161-1550863792c9'],
            'RFC 4122, version 3, variant "8"' => ['27433d43-011d-3a6a-8161-1550863792c9'],
            'RFC 4122, version 4, variant "8"' => ['27433d43-011d-4a6a-8161-1550863792c9'],
            'RFC 4122, version 5, variant "8"' => ['27433d43-011d-5a6a-8161-1550863792c9'],
            'RFC 4122, version 6, variant "8"' => ['27433d43-011d-6a6a-8161-1550863792c9'],
            'RFC 4122, version 7, variant "8"' => ['27433d43-011d-7a6a-8161-1550863792c9'],
            'RFC 4122, version 8, variant "8"' => ['27433d43-011d-8a6a-8161-1550863792c9'],

            // The variant character is "9".
            'RFC 4122, version 1, variant "9"' => ['27433d43-011d-1a6a-9161-1550863792c9'],
            'RFC 4122, version 2, variant "9"' => ['27433d43-011d-2a6a-9161-1550863792c9'],
            'RFC 4122, version 3, variant "9"' => ['27433d43-011d-3a6a-9161-1550863792c9'],
            'RFC 4122, version 4, variant "9"' => ['27433d43-011d-4a6a-9161-1550863792c9'],
            'RFC 4122, version 5, variant "9"' => ['27433d43-011d-5a6a-9161-1550863792c9'],
            'RFC 4122, version 6, variant "9"' => ['27433d43-011d-6a6a-9161-1550863792c9'],
            'RFC 4122, version 7, variant "9"' => ['27433d43-011d-7a6a-9161-1550863792c9'],
            'RFC 4122, version 8, variant "9"' => ['27433d43-011d-8a6a-9161-1550863792c9'],

            // The variant character is "a".
            'RFC 4122, version 1, variant "a"' => ['27433d43-011d-1a6a-a161-1550863792c9'],
            'RFC 4122, version 2, variant "a"' => ['27433d43-011d-2a6a-a161-1550863792c9'],
            'RFC 4122, version 3, variant "a"' => ['27433d43-011d-3a6a-a161-1550863792c9'],
            'RFC 4122, version 4, variant "a"' => ['27433d43-011d-4a6a-a161-1550863792c9'],
            'RFC 4122, version 5, variant "a"' => ['27433d43-011d-5a6a-a161-1550863792c9'],
            'RFC 4122, version 6, variant "a"' => ['27433d43-011d-6a6a-a161-1550863792c9'],
            'RFC 4122, version 7, variant "a"' => ['27433d43-011d-7a6a-a161-1550863792c9'],
            'RFC 4122, version 8, variant "a"' => ['27433d43-011d-8a6a-a161-1550863792c9'],

            // The variant character is "b".
            'RFC 4122, version 1, variant "b"' => ['27433d43-011d-1a6a-b161-1550863792c9'],
            'RFC 4122, version 2, variant "b"' => ['27433d43-011d-2a6a-b161-1550863792c9'],
            'RFC 4122, version 3, variant "b"' => ['27433d43-011d-3a6a-b161-1550863792c9'],
            'RFC 4122, version 4, variant "b"' => ['27433d43-011d-4a6a-b161-1550863792c9'],
            'RFC 4122, version 5, variant "b"' => ['27433d43-011d-5a6a-b161-1550863792c9'],
            'RFC 4122, version 6, variant "b"' => ['27433d43-011d-6a6a-b161-1550863792c9'],
            'RFC 4122, version 7, variant "b"' => ['27433d43-011d-7a6a-b161-1550863792c9'],
            'RFC 4122, version 8, variant "b"' => ['27433d43-011d-8a6a-b161-1550863792c9'],
        ];
    }
}
