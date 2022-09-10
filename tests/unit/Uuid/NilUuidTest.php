<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use BadMethodCallException;
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

    protected function setUp(): void
    {
        $this->nilUuid = new Uuid\NilUuid();
    }

    public function testSerialize(): void
    {
        $expected =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $serialized = serialize($this->nilUuid);

        $this->assertSame($expected, $serialized);
    }

    public function testCastsToString(): void
    {
        $this->assertSame(Uuid::NIL, (string) $this->nilUuid);
    }

    public function testUnserialize(): void
    {
        $serialized =
            'O:30:"Ramsey\\Identifier\\Uuid\\NilUuid":1:{s:4:"uuid";s:36:"00000000-0000-0000-0000-000000000000";}';
        $nilUuid = unserialize($serialized);

        $this->assertInstanceOf(Uuid\NilUuid::class, $nilUuid);
        $this->assertSame(Uuid::NIL, (string) $nilUuid);
    }

    /**
     * @dataProvider compareToProvider
     */
    public function testCompareTo(mixed $other, int $expected): void
    {
        $this->assertSame($expected, $this->nilUuid->compareTo($other));
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
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), 0],
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
            'with NilUuid' => [new Uuid\NilUuid(), 0],
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
            'with string Nil UUID all caps' => [strtoupper(Uuid::NIL), true],
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
            'with NilUuid' => [new Uuid\NilUuid(), true],
            'with MaxUuid' => [new Uuid\MaxUuid(), false],
            'with array' => [[], false],
        ];
    }

    public function testGetVariantThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Nil UUIDs do not have a variant field');

        $this->nilUuid->getVariant();
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
    }

    public function testToString(): void
    {
        $this->assertSame(Uuid::NIL, $this->nilUuid->toString());
    }

    public function testToBytes(): void
    {
        $this->assertSame(
            "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            $this->nilUuid->toBytes(),
        );
    }

    public function testToHexadecimal(): void
    {
        $this->assertSame(
            '00000000000000000000000000000000',
            $this->nilUuid->toHexadecimal(),
        );
    }

    public function testToInteger(): void
    {
        $this->assertSame(
            '0',
            $this->nilUuid->toInteger(),
        );
    }

    public function testToRfc4122(): void
    {
        $this->assertSame(Uuid::NIL, $this->nilUuid->toRfc4122());
    }

    public function testToUrn(): void
    {
        $this->assertSame('urn:uuid:' . Uuid::NIL, $this->nilUuid->toUrn());
    }
}
