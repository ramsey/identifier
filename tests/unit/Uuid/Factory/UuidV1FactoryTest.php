<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Factory;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Identifier\Service\Time\StaticDateTimeService;
use Ramsey\Identifier\Uuid\Factory\UuidV1Factory;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Test\Identifier\TestCase;

use function substr;

class UuidV1FactoryTest extends TestCase
{
    private UuidV1Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV1Factory();
    }

    public function testCreate(): void
    {
        $uuid = $this->factory->create();

        $this->assertInstanceOf(UuidV1::class, $uuid);
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new UuidV1Factory(
            new StaticClockSequenceService(0),
            new StaticNodeService(0),
            new StaticDateTimeService(new DateTimeImmutable('1582-10-15 00:00:00')),
        );

        $uuid = $factory->create();

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('00000000-0000-1000-8000-010000000000', $uuid->toString());
    }

    public function testCreateWithClockSequence(): void
    {
        $uuid = $this->factory->create(clockSequence: 0x3321);

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('321', substr($uuid->toString(), 20, 3));
    }

    public function testCreateWithNode(): void
    {
        $uuid = $this->factory->create(node: '3c1239b4f540');

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('3d1239b4f540', substr($uuid->toString(), -12));
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create(dateTime: $dateTime);

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('fd24f600-3cf7-11ed', substr($uuid->toString(), 0, 18));
    }

    public function testCreateWithMethodDeterministicValues(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create('3c1239b4f540', 0x3321, $dateTime);

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('fd24f600-3cf7-11ed-b321-3d1239b4f540', $uuid->toString());
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('ffffffff-ffff-1fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion1Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid version 1 UUID: \"\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('ffffffffffff1fff8fffffffffffffff');

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('ffffffff-ffff-1fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff1fff8ffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff1fff8ffffffffffffffg');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonVersion1Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: "ffffffffffff2fff8fffffffffffffff"');

        $this->factory->createFromHexadecimal('ffffffffffff2fff8fffffffffffffff');
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937405648670758612812955647');

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('ffffffff-ffff-1fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('75567087097951178194944');

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('00000000-0000-1000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion1Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 1 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-1fff-8fff-ffffffffffff');

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('ffffffff-ffff-1fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-1fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-1fff-8fff-ffffffffffff');
    }
}
