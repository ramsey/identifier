<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid as UlidInstance;

use function sprintf;
use function substr;

use const PHP_INT_MAX;

class UlidTest extends TestCase
{
    public function testCreate(): void
    {
        $ulid = Ulid::create();

        $this->assertInstanceOf(UlidInstance::class, $ulid);
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $ulid = Ulid::fromDateTime($dateTime);

        $this->assertInstanceOf(UlidInstance::class, $ulid);
        $this->assertNotSame($dateTime, $ulid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $ulid->getDateTime()->format('c'));
        $this->assertSame('01GDTV9RB0', substr($ulid->toString(), 0, 10));
    }

    public function testCreateFromBytes(): void
    {
        $ulid = Ulid::fromBytes("\x01\x83\x95\xab\x83\x9a\xdf\x27\x40\x8c\x40\x86\xcc\x5b\x66\xf5");

        $this->assertInstanceOf(UlidInstance::class, $ulid);
        $this->assertSame('01GEATQ0WTVWKM1320GV65PSQN', $ulid->toString());
    }

    public function testCreateFromBytesReturnsMax(): void
    {
        $ulid = Ulid::fromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromBytesReturnsNil(): void
    {
        $ulid = Ulid::fromBytes("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        Ulid::fromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $ulid = Ulid::fromHexadecimal('018395ab839adf27408c4086cc5b66f5');

        $this->assertInstanceOf(UlidInstance::class, $ulid);
        $this->assertSame('01GEATQ0WTVWKM1320GV65PSQN', $ulid->toString());
    }

    public function testCreateFromHexidecimalReturnsMax(): void
    {
        $ulid = Ulid::fromHexadecimal('ffffffffffffffffffffffffffffffff');

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromHexadecimalReturnsNil(): void
    {
        $ulid = Ulid::fromHexadecimal('00000000000000000000000000000000');

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        Ulid::fromHexadecimal('fffffffffffffffffffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        Ulid::fromHexadecimal('fffffffffffffffffffffffffffffffg');
    }

    public function testCreateFromIntegerThrowsExceptionForOutOfBoundsInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid ULID: 340282366920938463463374607431768211456');

        Ulid::fromInteger('340282366920938463463374607431768211456');
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeNativeInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a ULID from a negative integer');

        Ulid::fromInteger(-1);
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeStringInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a ULID from a negative integer');

        Ulid::fromInteger('-9223372036854775809');
    }

    /**
     * @dataProvider fromIntegerInvalidIntegerProvider
     */
    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid integer: "%s"', $input));

        /** @phpstan-ignore-next-line */
        Ulid::fromInteger($input);
    }

    /**
     * @return array<array{input: int | string}>
     */
    public function fromIntegerInvalidIntegerProvider(): array
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
     * @dataProvider fromIntegerProvider
     */
    public function testCreateFromInteger(int | string $value, string $expectedType): void
    {
        $ulid = Ulid::fromInteger($value);

        $this->assertInstanceOf($expectedType, $ulid);
    }

    /**
     * @return array<array{value: int | numeric-string, expectedType: class-string}>
     */
    public function fromIntegerProvider(): array
    {
        return [
            [
                'value' => '340282366920937934553716840013076889599',
                'expectedType' => UlidInstance::class,
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
                'expectedType' => UlidInstance::class,
            ],
            [
                'value' => (string) PHP_INT_MAX,
                'expectedType' => UlidInstance::class,
            ],
        ];
    }

    public function testCreateFromString(): void
    {
        $ulid = Ulid::fromString('01bx5zzkbkactav9wevgemmvrz');

        $this->assertInstanceOf(UlidInstance::class, $ulid);
        $this->assertSame('01BX5ZZKBKACTAV9WEVGEMMVRZ', $ulid->toString());
    }

    public function testCreateFromStringReturnsMaxUlid(): void
    {
        $ulid = Ulid::fromString('7ZZZZZZZZZZZZZZZZZZZZZZZZZ');

        $this->assertInstanceOf(MaxUlid::class, $ulid);
        $this->assertSame('7ZZZZZZZZZZZZZZZZZZZZZZZZZ', $ulid->toString());
    }

    public function testCreateFromStringReturnsNilUlid(): void
    {
        $ulid = Ulid::fromString('00000000000000000000000000');

        $this->assertInstanceOf(NilUlid::class, $ulid);
        $this->assertSame('00000000000000000000000000', $ulid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a valid ULID string representation');

        Ulid::fromString('01BX5ZZKBKACTAV9WEVGEMMVR');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a valid ULID string representation');

        Ulid::fromString('ffffffff-ffff-7fff-8fff-fffffffffffff');
    }
}
