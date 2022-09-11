<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use AssertionError;
use DateTimeImmutable;
use Identifier\Uuid\Variant;
use Identifier\Uuid\Version;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid;
use Ramsey\Test\Identifier\TestCase;

use function json_encode;
use function serialize;
use function strtoupper;
use function unserialize;

class UuidV1Test extends TestCase
{
    private const UUID_V1 = '27433d43-011d-1a6a-9161-1550863792c9';

    private Uuid\UuidV1 $uuid;

    protected function setUp(): void
    {
        $this->uuid = new Uuid\UuidV1(self::UUID_V1);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: ""');

        new Uuid\UuidV1('');
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: "27433d43-011d-9a6a-9161-1550863792c9"');

        new Uuid\UuidV1('27433d43-011d-9a6a-9161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: "27433d43-011d-1a6a-c161-1550863792c9"');

        new Uuid\UuidV1('27433d43-011d-1a6a-c161-1550863792c9');
    }

    public function testSerialize(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-1a6a-9161-1550863792c9";}';
        $serialized = serialize($this->uuid);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V1, (string) $this->uuid);
    }

    public function testUnserialize(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-1a6a-9161-1550863792c9";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV1::class, $uuid);
        $this->assertSame(self::UUID_V1, (string) $uuid);
    }

    public function testUnserializeFailsAssertionWhenUuidIsNotSet(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:3:"foo";s:36:"27433d43-011d-1a6a-9161-1550863792c9";}';

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('\'uuid\' is not set in serialized data');

        unserialize($serialized);
    }

    public function testUnserializeFailsAssertionWhenUuidIsNotAString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";i:1234;}';

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('\'uuid\' in serialized data is not a string');

        unserialize($serialized);
    }

    public function testUnserializeFailsAssertionWhenUuidIsAnEmptyString(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('\'uuid\' in serialized data is an empty string');

        unserialize($serialized);
    }

    public function testUnserializeFailsAssertionForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV1":1:{s:4:"uuid";s:36:"27433d43-011d-9a6a-9161-1550863792c9";}';

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage(
            '\'uuid\' in serialized data is not a valid version 1 UUID: "27433d43-011d-9a6a-9161-1550863792c9"',
        );

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
            'with same string UUID' => [self::UUID_V1, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V1), 0],
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
            'with same UuidV1' => [new Uuid\UuidV1(self::UUID_V1), 0],
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
            'with same string UUID' => [self::UUID_V1, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V1), true],
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
            'with same UuidV1' => [new Uuid\UuidV1(self::UUID_V1), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariant(): void
    {
        $this->assertSame(Variant::Rfc4122, $this->uuid->getVariant());
    }

    public function testGetVersion(): void
    {
        $this->assertSame(Version::GregorianTime, $this->uuid->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V1 . '"', json_encode($this->uuid));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V1, $this->uuid->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(
            "\x27\x43\x3d\x43\x01\x1d\x1a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9",
            $this->uuid->toBytes(),
        );
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(
            '27433d43011d1a6a91611550863792c9',
            $this->uuid->toHexadecimal(),
        );
    }

    public function testToInteger(): void
    {
        $this->assertSame(
            '52189018260750932307915771537377432265',
            $this->uuid->toInteger(),
        );
    }

    public function testToRfc4122(): void
    {
        $this->assertSame(self::UUID_V1, $this->uuid->toRfc4122());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V1, $this->uuid->toUrn());
    }

    public function testGetDateTime(): void
    {
        $dateTime = $this->uuid->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('3960-10-02T03:47:44+00:00', $dateTime->format('c'));
    }
}
