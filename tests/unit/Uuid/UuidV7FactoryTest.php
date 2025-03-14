<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Uuid\UuidV7Factory;
use Ramsey\Test\Identifier\TestCase;

use function gmdate;
use function substr;

class UuidV7FactoryTest extends TestCase
{
    private UuidV7Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV7Factory();
    }

    public function testCreateThrowsExceptionForTooEarlyTimestamp(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Timestamp may not be earlier than the Unix Epoch');

        $this->factory->create(new DateTimeImmutable('1969-12-31 23:59:59.999999'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCreateWithFactoryInitializedValues(): void
    {
        $factory = new UuidV7Factory(
            new MonotonicBytesGenerator(
                new FixedBytesGenerator("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
                new FrozenClock(new DateTimeImmutable('1970-01-01 00:00:00.000000')),
            ),
        );

        $uuid = $factory->create();

        $this->assertSame('00000000-0000-7000-8000-000000000000', $uuid->toString());

        // Another v7 UUID generated will not be identical because of the
        // non-deterministic randomizing we perform inside the class.
        $uuidNext = $factory->create();

        $this->assertNotTrue($uuid->equals($uuidNext));
        $this->assertLessThan(0, $uuid->compareTo($uuidNext));
    }

    public function testCreateWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12');
        $uuid = $this->factory->create(dateTime: $dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25T17:32:12+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('018375b4-e160', substr($uuid->toString(), 0, 13));
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x7f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

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

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.294208');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.294000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('018375b4-e286', substr($uuid->toString(), 0, 13));
    }

    public function testCreateFromMaximumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('@281474976710.655000');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        $this->assertSame('10889-08-02 05:31:50.655000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('ffffffff-ffff', substr($uuid->toString(), 0, 13));
    }

    public function testCreateFromMinimumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('1970-01-01 00:00:00.000000');
        $uuid = $this->factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $uuid->getDateTime());

        // We lose up to 7+ minutes of time with UUID version 2.
        $this->assertSame('1970-01-01 00:00:00.000000', $uuid->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('00000000-0000', substr($uuid->toString(), 0, 13));
    }

    public function testCreateFromDateTimeThrowsExceptionForTooEarlyDate(): void
    {
        $dateTime = new DateTimeImmutable('1969-12-31 23:59:59.999999');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Timestamp may not be earlier than the Unix Epoch');

        $this->factory->createFromDateTime($dateTime);
    }

    public function testCreateFromDateTimeThrowsExceptionForTooLateDate(): void
    {
        $dateTime = new DateTimeImmutable('@281474976710.656000');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The date exceeds the maximum value allowed for Unix Epoch time UUIDs: 10889-08-02 05:31:50.656000 +00:00',
        );

        $this->factory->createFromDateTime($dateTime);
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937858995853114098753470463');

        $this->assertSame('ffffffff-ffff-7fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('528914269453437118709760');

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

    public function testCreateEachUuidIsMonotonicallyIncreasing(): void
    {
        $previous = $this->factory->create();

        for ($i = 0; $i < 25; $i++) {
            $uuid = $this->factory->create();
            $now = gmdate('Y-m-d H:i');
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($now, $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testCreateEachUuidFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        $dateTime = new DateTimeImmutable();

        $previous = $this->factory->create($dateTime);

        for ($i = 0; $i < 25; $i++) {
            $uuid = $this->factory->create($dateTime);
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('017f22e279b07cc398c4dc0c0c07398f');

        $this->assertSame('017f22e2-79b0-7cc3-98c4-dc0c0c07398f', $uuid->toString());
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
            'too short' => ['hexadecimal' => '017f22e279b07cc398c4dc0c0c07398'],
            'too long' => ['hexadecimal' => '017f22e279b07cc398c4dc0c0c07398fa'],
            'not hexadecimal' => ['hexadecimal' => '017f22e279b07cc398c4dc0c0c07398g'],
        ];
    }
}
