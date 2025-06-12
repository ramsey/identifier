<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Utility\Time;
use Ramsey\Identifier\Uuid\UuidFactory;
use Ramsey\Test\Identifier\TestCase;

use function bin2hex;
use function sprintf;

class TimeTest extends TestCase
{
    #[DataProvider('timeBytesForGregorianEpochProvider')]
    public function testGetTimeBytesForGregorianEpoch(DateTimeInterface $dateTime, string $expectedBytes): void
    {
        $bytes = (new Time())->getTimeBytesForGregorianEpoch($dateTime);

        $this->assertSame(
            $expectedBytes,
            $bytes,
            sprintf('Expected "%s", received "%s"', bin2hex($expectedBytes), bin2hex($bytes)),
        );
    }

    /**
     * @return list<array{dateTime: DateTimeInterface, expectedBytes: non-empty-string}>
     */
    public static function timeBytesForGregorianEpochProvider(): array
    {
        return [
            [
                'dateTime' => new DateTimeImmutable('1582-10-15 00:00:00'),
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'dateTime' => new DateTimeImmutable('1970-01-01 00:00:00'),
                'expectedBytes' => "\x01\xb2\x1d\xd2\x13\x81\x40\x00",
            ],
            [
                'dateTime' => new DateTimeImmutable('2022-09-26 17:53:42.123456'),
                'expectedBytes' => "\x01\xed\x3d\xc4\x28\x87\xed\x80",
            ],
            [
                'dateTime' => new DateTimeImmutable('5236-03-31 21:21:00.684697'),
                'expectedBytes' => "\x0f\xff\xff\xff\xff\xff\xff\xfa",
            ],
        ];
    }

    public function testGetTimeBytesForGregorianEpochThrowsExceptionForEarlyDate(): void
    {
        $dateTime = new DateTimeImmutable('1582-10-14 23:59:59.999999');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to get bytes for a timestamp earlier than the Gregorian epoch');

        (new Time())->getTimeBytesForGregorianEpoch($dateTime);
    }

    public function testGetTimeBytesForGregorianEpochThrowsExceptionForOutOfBoundsDate(): void
    {
        $dateTime = new DateTimeImmutable('5236-03-31 21:21:00.684698');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The date exceeds the maximum value allowed for Gregorian time UUIDs: 5236-03-31 21:21:00.684698',
        );

        (new Time())->getTimeBytesForGregorianEpoch($dateTime);
    }

    /**
     * @param non-empty-string $uuid
     */
    #[DataProvider('getDateTimeForUuidProvider')]
    public function testGetDateTimeForUuid(string $uuid, string $expectedTime): void
    {
        $uuid = (new UuidFactory())->createFromString($uuid);
        $time = new Time();

        $this->assertSame($expectedTime, $time->getDateTimeForUuid($uuid)->format('Y-m-d H:i:s.u'));
    }

    /**
     * @return array<string, array{uuid: non-empty-string, expectedTime: string}>
     */
    public static function getDateTimeForUuidProvider(): array
    {
        return [
            'v1 UUID' => [
                'uuid' => '27433d43-011d-1a6a-8161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500627',
            ],
            'v2 UUID' => [
                'uuid' => '27433d43-011d-2a6a-8101-1550863792c9',
                'expectedTime' => '3960-10-02 03:46:37.628825',
            ],
            'v6 UUID' => [
                'uuid' => 'a6a011d2-7433-6d43-8161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500627',
            ],
            'v7 UUID' => [
                'uuid' => '3922e67a-910c-704c-8bd3-a5765a69f0d9',
                'expectedTime' => '3960-10-02 03:47:43.500000',
            ],
            'v1 GUID' => [
                'uuid' => '27433d43-011d-1a6a-d161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500627',
            ],
            'v2 GUID' => [
                'uuid' => '27433d43-011d-2a6a-d101-1550863792c9',
                'expectedTime' => '3960-10-02 03:46:37.628825',
            ],
            'v6 GUID' => [
                'uuid' => 'a6a011d2-7433-6d43-d161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500627',
            ],
            'v7 GUID' => [
                'uuid' => '3922e67a-910c-704c-bbd3-a5765a69f0d9',
                'expectedTime' => '3960-10-02 03:47:43.500000',
            ],
            'minimum UUID' => [
                'uuid' => '00000000-0000-1000-8000-000000000000',
                'expectedTime' => '1582-10-15 00:00:00.000000',
            ],
            'maximum UUID' => [
                'uuid' => 'ffffffff-ffff-1fff-8000-000000000000',
                'expectedTime' => '5236-03-31 21:21:00.684697',
            ],

            // PHP uses microsecond resolution, while UUIDs use 100-nanosecond
            // interval resolution. As a result, multiple UUIDs can have the
            // same timestamp.
            'maximum UUID timestamp resolution A' => [
                'uuid' => 'fffffffa-ffff-1fff-8000-000000000000',
                'expectedTime' => '5236-03-31 21:21:00.684697',
            ],
            'maximum UUID timestamp resolution B' => [
                'uuid' => 'fffffffb-ffff-1fff-8000-000000000000',
                'expectedTime' => '5236-03-31 21:21:00.684697',
            ],
            'maximum UUID timestamp resolution C' => [
                'uuid' => 'fffffffc-ffff-1fff-8000-000000000000',
                'expectedTime' => '5236-03-31 21:21:00.684697',
            ],
        ];
    }
}
