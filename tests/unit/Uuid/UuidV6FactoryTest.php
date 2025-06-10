<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\FrozenClockSequence;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\Uuid\UuidV6Factory;
use Ramsey\Test\Identifier\TestCase;

use function substr;

class UuidV6FactoryTest extends TestCase
{
    private UuidV6Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV6Factory();
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new UuidV6Factory(
            new FrozenClock(new DateTimeImmutable('1582-10-15 00:00:00')),
            new StaticNic(0),
            new FrozenClockSequence(0),
        );

        $uuid = $factory->create();

        $this->assertSame('00000000-0000-6000-8000-010000000000', $uuid->toString());
    }

    public function testCreateWithClockSequence(): void
    {
        $uuid = $this->factory->create(clockSequence: 0x3321);

        $this->assertSame('321', substr($uuid->toString(), 20, 3));
    }

    public function testCreateWithNode(): void
    {
        $uuid = $this->factory->create(node: '3c1239b4f540');

        $this->assertSame('3d1239b4f540', substr($uuid->toString(), -12));
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create(dateTime: $dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1ed3cf7f-d24f-6600', substr($uuid->toString(), 0, 18));
    }

    public function testCreateWithMethodDeterministicValues(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create('3c1239b4f540', 0x3321, $dateTime);

        $this->assertSame('1ed3cf7f-d24f-6600-b321-3d1239b4f540', $uuid->toString());
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x6f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-6fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x6f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion6Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 6 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.294208');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.294208', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('1ed3cf7f-d51d-6a80', substr($uuid->toString(), 0, 18));
    }

    public function testCreateFromMaximumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('5236-03-31 21:21:00.684697');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        $this->assertSame('5236-03-31 21:21:00.684697', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('ffffffff-ffff-6ffa', substr($uuid->toString(), 0, 18));
    }

    public function testCreateFromMinimumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('1582-10-15 00:00:00.000000');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        $this->assertSame('1582-10-15 00:00:00.000000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('00000000-0000-6000', substr($uuid->toString(), 0, 18));
    }

    public function testCreateFromDateTimeThrowsExceptionForTooEarlyDate(): void
    {
        $dateTime = new DateTimeImmutable('1582-10-14 23:59:59.999999');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to get bytes for a timestamp earlier than the Gregorian epoch');

        $this->factory->createFromDateTime($dateTime);
    }

    public function testCreateFromDateTimeThrowsExceptionForTooLateDate(): void
    {
        $dateTime = new DateTimeImmutable('5236-03-31 21:21:00.684698');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The date exceeds the maximum value allowed for Gregorian time UUIDs: 5236-03-31 21:21:00.684698',
        );

        $this->factory->createFromDateTime($dateTime);
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937783437989388184430051327');

        $this->assertSame('ffffffff-ffff-6fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('453356405727522795290624');

        $this->assertSame('00000000-0000-6000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion6Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 6 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-6fff-8fff-ffffffffffff');

        $this->assertSame('ffffffff-ffff-6fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-6fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-6fff-8fff-ffffffffffff');
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('a6a011d274336d4391611550863792c9');

        $this->assertSame('a6a011d2-7433-6d43-9161-1550863792c9', $uuid->toString());
    }

    #[DataProvider('createFromHexadecimalThrowsExceptionProvider')]
    public function testCreateFromHexadecimalThrowsExceptionForInvalidHexadecimal(string $hexadecimal): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal($hexadecimal);
    }

    /**
     * @return array<string, array{hexadecimal: string}>
     */
    public static function createFromHexadecimalThrowsExceptionProvider(): array
    {
        return [
            'too short' => ['hexadecimal' => 'a6a011d274336d4391611550863792c'],
            'too long' => ['hexadecimal' => 'a6a011d274336d4391611550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => 'a6a011d274336d4391611550863792cg'],
        ];
    }
}
