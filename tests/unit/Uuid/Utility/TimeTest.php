<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Uuid\Utility\Time;
use Ramsey\Identifier\Uuid\UuidFactory;
use Ramsey\Test\Identifier\TestCase;

use function bin2hex;
use function sprintf;

class TimeTest extends TestCase
{
    /**
     * @dataProvider timeBytesForGregorianEpochProvider
     */
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
     * @dataProvider timeBytesForGregorianEpochProvider
     */
    public function testGetTimeBytesForGregorianEpochOn32Bit(DateTimeInterface $dateTime, string $expectedBytes): void
    {
        $os = $this->mockery(Os::class, [
            'getIntSize' => 4,
        ]);

        $bytes = (new Time($os))->getTimeBytesForGregorianEpoch($dateTime);

        $this->assertSame(
            $expectedBytes,
            $bytes,
            sprintf('Expected "%s", received "%s"', bin2hex($expectedBytes), bin2hex($bytes)),
        );
    }

    /**
     * @return array<array{dateTime: DateTimeInterface, expectedBytes: non-empty-string}>
     */
    public function timeBytesForGregorianEpochProvider(): array
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
        ];
    }

    public function testGetTimeBytesForGregorianEpochThrowsExceptionForEarlyDate(): void
    {
        $dateTime = new DateTimeImmutable('1582-10-14 00:00:00');

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to get bytes for a timestamp earlier than the Gregorian epoch');

        (new Time())->getTimeBytesForGregorianEpoch($dateTime);
    }

    /**
     * @dataProvider getDateTimeForUuidProvider
     */
    public function testGetDateTimeForUuid(string $uuid, string $expectedTime): void
    {
        $uuid = (new UuidFactory())->createFromString($uuid);
        $time = new Time();

        $this->assertSame($expectedTime, $time->getDateTimeForUuid($uuid)->format('Y-m-d H:i:s.u'));
    }

    /**
     * @dataProvider getDateTimeForUuidProvider
     */
    public function testGetDateTimeForUuidOn32Bit(string $uuid, string $expectedTime): void
    {
        $os = $this->mockery(Os::class, [
            'getIntSize' => 4,
        ]);

        $uuid = (new UuidFactory())->createFromString($uuid);
        $time = new Time($os);

        $this->assertSame($expectedTime, $time->getDateTimeForUuid($uuid)->format('Y-m-d H:i:s.u'));
    }

    /**
     * @return array<string, array{uuid: string, expectedTime: string}>
     */
    public function getDateTimeForUuidProvider(): array
    {
        return [
            'v1 UUID' => [
                'uuid' => '27433d43-011d-1a6a-8161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500628',
            ],
            'v2 UUID' => [
                'uuid' => '27433d43-011d-2a6a-8101-1550863792c9',
                'expectedTime' => '3960-10-02 03:46:37.628826',
            ],
            'v6 UUID' => [
                'uuid' => 'a6a011d2-7433-6d43-8161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500628',
            ],
            'v7 UUID' => [
                'uuid' => '3922e67a-910c-704c-8bd3-a5765a69f0d9',
                'expectedTime' => '3960-10-02 03:47:43.500000',
            ],
            'v1 GUID' => [
                'uuid' => '27433d43-011d-1a6a-d161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500628',
            ],
            'v2 GUID' => [
                'uuid' => '27433d43-011d-2a6a-d101-1550863792c9',
                'expectedTime' => '3960-10-02 03:46:37.628826',
            ],
            'v6 GUID' => [
                'uuid' => 'a6a011d2-7433-6d43-d161-1550863792c9',
                'expectedTime' => '3960-10-02 03:47:43.500628',
            ],
            'v7 GUID' => [
                'uuid' => '3922e67a-910c-704c-bbd3-a5765a69f0d9',
                'expectedTime' => '3960-10-02 03:47:43.500000',
            ],
        ];
    }
}
