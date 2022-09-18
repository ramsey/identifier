<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

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

class UuidV7Test extends TestCase
{
    private const UUID_V7 = '017F22E2-79B0-7CC3-98C4-DC0C0C07398F';

    private Uuid\UuidV7 $uuid;

    protected function setUp(): void
    {
        $this->uuid = new Uuid\UuidV7(self::UUID_V7);
    }

    public function testConstructorThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: ""');

        new Uuid\UuidV7('');
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

        new Uuid\UuidV7('a6a011d2-7433-9d43-9161-1550863792c9');
    }

    public function testConstructorThrowsExceptionForInvalidVariantUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: "a6a011d2-7433-7d43-c161-1550863792c9"');

        new Uuid\UuidV7('a6a011d2-7433-7d43-c161-1550863792c9');
    }

    public function testSerialize(): void
    {
        $expected =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"017F22E2-79B0-7CC3-98C4-DC0C0C07398F";}';
        $serialized = serialize($this->uuid);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(self::UUID_V7, (string) $this->uuid);
    }

    public function testUnserialize(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"017F22E2-79B0-7CC3-98C4-DC0C0C07398F";}';
        $uuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\UuidV7::class, $uuid);
        $this->assertSame(self::UUID_V7, (string) $uuid);
    }

    public function testUnserializeFailsWhenUuidIsAnEmptyString(): void
    {
        $serialized = 'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:0:"";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: ""');

        unserialize($serialized);
    }

    public function testUnserializeFailsForInvalidVersionUuid(): void
    {
        $serialized =
            'O:29:"Ramsey\\Identifier\\Uuid\\UuidV7":1:{s:4:"uuid";s:36:"a6a011d2-7433-9d43-9161-1550863792c9";}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: "a6a011d2-7433-9d43-9161-1550863792c9"');

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
            'with int' => [123, -1],
            'with float' => [123.456, -1],
            'with string' => ['foobar', -1],
            'with string Nil UUID' => [Uuid::NIL, 1],
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), 1],
            'with same string UUID' => [self::UUID_V7, 0],
            'with same string UUID all caps' => [strtoupper(self::UUID_V7), 0],
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
            'with NilUuid' => [new Uuid\NilUuid(), 1],
            'with same UuidV7' => [new Uuid\UuidV7(self::UUID_V7), 0],
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
            'with same string UUID' => [self::UUID_V7, true],
            'with same string UUID all caps' => [strtoupper(self::UUID_V7), true],
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
            'with same UuidV7' => [new Uuid\UuidV7(self::UUID_V7), true],
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
        $this->assertSame(Version::UnixTime, $this->uuid->getVersion());
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame('"' . self::UUID_V7 . '"', json_encode($this->uuid));
    }

    public function testToString(): void
    {
        $this->assertSame(self::UUID_V7, $this->uuid->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(
            "\x01\x7f\x22\xe2\x79\xb0\x7c\xc3\x98\xc4\xdc\x0c\x0c\x07\x39\x8f",
            $this->uuid->toBytes(),
        );
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(
            '017F22E279B07CC398C4DC0C0C07398F',
            $this->uuid->toHexadecimal(),
        );
    }

    public function testToInteger(): void
    {
        $this->assertSame(
            '1989357241971137676463954034883508623',
            $this->uuid->toInteger(),
        );
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . self::UUID_V7, $this->uuid->toUrn());
    }

    public function testGetDateTime(): void
    {
        $dateTime = $this->uuid->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame('2022-02-22T19:22:22+00:00', $dateTime->format('c'));
    }
}
