<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Factory;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\DateTime\StaticDateTimeService;
use Ramsey\Identifier\Service\Random\StaticBytesService;
use Ramsey\Identifier\Uuid\Factory\UuidV7Factory;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Test\Identifier\TestCase;

use function substr;

class UuidV7FactoryTest extends TestCase
{
    private UuidV7Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV7Factory();
    }

    public function testCreate(): void
    {
        $uuid = $this->factory->create();

        $this->assertInstanceOf(UuidV7::class, $uuid);
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new UuidV7Factory(
            new StaticBytesService("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            new StaticDateTimeService(new DateTimeImmutable('1970-01-01 00:00:00')),
        );

        $uuid = $factory->create();

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('00000000-0000-7000-8000-000000000000', $uuid->toString());
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create(dateTime: $dateTime);

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('018375b4-e160', substr($uuid->toString(), 0, 13));
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x7f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('ffffffff-ffff-7fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x7f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion7Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 7 UUID: \"\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('ffffffffffff7fff8fffffffffffffff');

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('ffffffff-ffff-7fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff7fff8ffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff7fff8ffffffffffffffg');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonVersion7Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: "ffffffffffff2fff8fffffffffffffff"');

        $this->factory->createFromHexadecimal('ffffffffffff2fff8fffffffffffffff');
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937858995853114098753470463');

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('ffffffff-ffff-7fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('528914269453437118709760');

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('00000000-0000-7000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion7Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 7 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-7fff-8fff-ffffffffffff');

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('ffffffff-ffff-7fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-7fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-7fff-8fff-ffffffffffff');
    }
}
