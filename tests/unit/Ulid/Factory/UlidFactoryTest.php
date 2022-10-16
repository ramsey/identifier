<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Ulid\Factory;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\DateTime\StaticDateTimeService;
use Ramsey\Identifier\Service\Random\StaticBytesService;
use Ramsey\Identifier\Ulid\Factory\UlidFactory;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;
use function substr;

use const PHP_INT_MAX;

class UlidFactoryTest extends TestCase
{
    private UlidFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UlidFactory();
    }

    public function testCreate(): void
    {
        $ulid = $this->factory->create();

        $this->assertInstanceOf(Ulid::class, $ulid);
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new UlidFactory(
            new StaticBytesService("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            new StaticDateTimeService(new DateTimeImmutable('1970-01-01 00:00:00')),
        );

        $ulid = $factory->create();

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $ulid = $this->factory->createFromDateTime($dateTime);

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertNotSame($dateTime, $ulid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $ulid->getDateTime()->format('c'));
        $this->assertSame('01GDTV9RB0', substr($ulid->toString(), 0, 10));
    }

    public function testCreateFromBytes(): void
    {
        $ulid = $this->factory->createFromBytes("\x01\x83\x95\xab\x83\x9a\xdf\x27\x40\x8c\x40\x86\xcc\x5b\x66\xf5");

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame('01GEATQ0WTVWKM1320GV65PSQN', $ulid->toString());
    }

    public function testCreateFromBytesReturnsMax(): void
    {
        $ulid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromBytesReturnsNil(): void
    {
        $ulid = $this->factory->createFromBytes("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $ulid = $this->factory->createFromHexadecimal('018395ab839adf27408c4086cc5b66f5');

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame('01GEATQ0WTVWKM1320GV65PSQN', $ulid->toString());
    }

    public function testCreateFromHexidecimalReturnsMax(): void
    {
        $ulid = $this->factory->createFromHexadecimal('ffffffffffffffffffffffffffffffff');

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromHexadecimalReturnsNil(): void
    {
        $ulid = $this->factory->createFromHexadecimal('00000000000000000000000000000000');

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffffffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffffffffffffffffffg');
    }

    public function testCreateFromIntegerThrowsExceptionForOutOfBoundsInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: 340282366920938463463374607431768211456');

        $this->factory->createFromInteger('340282366920938463463374607431768211456');
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeNativeInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a ULID from a negative integer');

        $this->factory->createFromInteger(-1);
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeStringInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a ULID from a negative integer');

        $this->factory->createFromInteger('-9223372036854775809');
    }

    /**
     * @dataProvider createFromIntegerInvalidIntegerProvider
     */
    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid integer: "%s"', $input));

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger($input);
    }

    /**
     * @return array<array{input: int | string}>
     */
    public function createFromIntegerInvalidIntegerProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0"],
            ['input' => '00000000-0000-1000-a000-000000000000'],
            ['input' => '0000000000001000a000000000000000'],
            ['input' => '123.456'],
            ['input' => 'foobar'],
        ];
    }

    /**
     * @param int | numeric-string $value
     * @param class-string $expectedType
     *
     * @dataProvider createFromIntegerProvider
     */
    public function testCreateFromInteger(int | string $value, string $expectedType): void
    {
        $ulid = $this->factory->createFromInteger($value);

        $this->assertInstanceOf($expectedType, $ulid);
    }

    /**
     * @return array<array{value: int | numeric-string, expectedType: class-string}>
     */
    public function createFromIntegerProvider(): array
    {
        return [
            [
                'value' => '340282366920937934553716840013076889599',
                'expectedType' => Ulid::class,
            ],
            [
                'value' => '0',
                'expectedType' => NilUlid::class,
            ],
            [
                'value' => 0,
                'expectedType' => NilUlid::class,
            ],
            [
                'value' => '340282366920938463463374607431768211455',
                'expectedType' => MaxUlid::class,
            ],
            [
                'value' => PHP_INT_MAX,
                'expectedType' => Ulid::class,
            ],
            [
                'value' => (string) PHP_INT_MAX,
                'expectedType' => Ulid::class,
            ],
        ];
    }

    public function testCreateFromString(): void
    {
        $ulid = $this->factory->createFromString('01bx5zzkbkactav9wevgemmvrz');

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame('01BX5ZZKBKACTAV9WEVGEMMVRZ', $ulid->toString());
    }

    public function testCreateFromStringReturnsMaxUlid(): void
    {
        $ulid = $this->factory->createFromString('7ZZZZZZZZZZZZZZZZZZZZZZZZZ');

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromStringReturnsNilUlid(): void
    {
        $ulid = $this->factory->createFromString('00000000000000000000000000');

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a valid ULID string representation');

        $this->factory->createFromString('01BX5ZZKBKACTAV9WEVGEMMVR');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a valid ULID string representation');

        $this->factory->createFromString('ffffffff-ffff-7fff-8fff-fffffffffffff');
    }
}
