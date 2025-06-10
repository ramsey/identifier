<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\FrozenClockSequence;
use Ramsey\Identifier\Service\Dce\StaticDce;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Identifier\Uuid\UuidV2Factory;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function substr;

class UuidV2FactoryTest extends TestCase
{
    private UuidV2Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV2Factory();
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new UuidV2Factory(
            new FrozenClock(new DateTimeImmutable('1582-10-15 00:00:00')),
            new StaticDce(501),
            new StaticNic(0),
            new FrozenClockSequence(0),
        );

        $uuid = $factory->create();

        $this->assertSame('000001f5-0000-2000-8000-010000000000', $uuid->toString());
    }

    public function testCreateWithLocalDomain(): void
    {
        $uuid = $this->factory->create(localDomain: DceDomain::Group);

        $this->assertSame(1, hexdec(substr($uuid->toString(), 21, 2)));
        $this->assertSame(DceDomain::Group, $uuid->getLocalDomain());
    }

    public function testCreateWithLocalIdentifier(): void
    {
        $uuid = $this->factory->create(localIdentifier: 1001);

        $this->assertSame('000003e9', substr($uuid->toString(), 0, 8));
        $this->assertSame(1001, $uuid->getLocalIdentifier());
    }

    public function testCreateWithClockSequence(): void
    {
        $uuid = $this->factory->create(clockSequence: 42);

        $this->assertSame(42, hexdec(substr($uuid->toString(), 19, 2)) & 0x3f);
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

        // We lose up to 7+ minutes of time with UUID version 2.
        $this->assertSame('2022-09-25T17:25:07+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('3cf7-21ed', substr($uuid->toString(), 9, 9));
    }

    public function testCreateWithMethodDeterministicValues(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create(
            DceDomain::Org,
            2147483647,
            '3c1239b4f540',
            0x3f,
            $dateTime,
        );

        $this->assertSame('7fffffff-3cf7-21ed-bf02-3d1239b4f540', $uuid->toString());
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\x00\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-2fff-8f00-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\x00\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion2Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 2 UUID: \"\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.294208');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        // We lose up to 7+ minutes of time with UUID version 2.
        $this->assertSame('2022-09-25 17:25:07.294208', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('3cf7-21ed', substr($uuid->toString(), 9, 9));
    }

    public function testCreateFromMaximumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('5236-03-31 21:21:00.684697');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        // We lose up to 7+ minutes of time with UUID version 2.
        $this->assertSame('5236-03-31 21:13:51.187968', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('ffff-2fff', substr($uuid->toString(), 9, 9));
    }

    public function testCreateFromMinimumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('1582-10-15 00:00:00.000000');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        // We lose up to 7+ minutes of time with UUID version 2.
        $this->assertSame('1582-10-15 00:00:00.000000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('0000-2000', substr($uuid->toString(), 9, 9));
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
        $uuid = $this->factory->createFromInteger('340282366920937481206463271358028578815');

        $this->assertSame('ffffffff-ffff-2fff-bf02-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('151124950823865501614080');

        $this->assertSame('00000000-0000-2000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion2Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 2 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-2fff-8f00-ffffffffffff');

        $this->assertSame('ffffffff-ffff-2fff-8f00-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-2fff-8f00-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-2fff-8f00-ffffffffffff');
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('27433d43011d2a6a91001550863792c9');

        $this->assertSame('27433d43-011d-2a6a-9100-1550863792c9', $uuid->toString());
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
            'too short' => ['hexadecimal' => '27433d43011d2a6a91001550863792c'],
            'too long' => ['hexadecimal' => '27433d43011d2a6a91001550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => '27433d43011d2a6a91001550863792cg'],
        ];
    }
}
