<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Uuid\Utility\Time;
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
        // Force the 32-bit code path.
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
}
