<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use DateTimeImmutable;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomSourceFailure;
use Ramsey\Identifier\Service\BytesGenerator\TimeSourceFailure;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Test\Identifier\TestCase;
use ReflectionClass;

use function bin2hex;
use function ord;
use function str_repeat;
use function strlen;
use function substr;

use const INF;
use const NAN;

class MonotonicBytesGeneratorTest extends TestCase
{
    /**
     * 2023-11-14 22:13:20 UTC, as 48 bits of milliseconds in hexadecimal
     */
    private const string TIMESTAMP = '018bcfe56800';

    protected function setUp(): void
    {
        // Generators with a SystemClock share one state, so each test starts
        // without it.
        (new ReflectionClass(MonotonicBytesGenerator::class))->setStaticPropertyValue('sharedState', null);
    }

    public function testBytesForTheSystemTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator(), os: $this->os(1700000000.123456));

        $this->assertSame('018bcfe5687b' . '074b' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
    }

    public function testBytesForAnotherClock(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new RecordingBytesGenerator(),
            new FrozenClock(new DateTimeImmutable('@1700000000.123456')),
        );

        $this->assertSame('018bcfe5687b' . '074b' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
    }

    public function testBytesForADateTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator());
        $bytes = $bytesGenerator->bytes(dateTime: new DateTimeImmutable('@1700000000.123456'));

        $this->assertSame('018bcfe5687b' . '074b' . '0000000000000000', bin2hex($bytes));
    }

    public function testBytesForADateTimeInAnotherTimeZone(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator());
        $bytes = $bytesGenerator->bytes(dateTime: new DateTimeImmutable('2023-11-15 03:43:20.123456 +05:30'));

        $this->assertSame('018bcfe5687b' . '074b' . '0000000000000000', bin2hex($bytes));
    }

    #[TestWith(['000000', 0.0, '0000'])]
    #[TestWith(['000001', 0.000001, '0004'])]
    #[TestWith(['000456', 0.000456, '074b'])]
    #[TestWith(['000500', 0.0005, '0800'])]
    #[TestWith(['000999', 0.000999, '0ffb'])]
    #[TestWith(['999999', 0.999999, '0ffb'])]
    public function testFractionOfTheMillisecond(string $microseconds, float $fractionOfSecond, string $expected): void
    {
        $dateTime = new DateTimeImmutable('@1700000000.' . $microseconds);

        $forSystemTime = new MonotonicBytesGenerator(os: $this->os(1700000000 + $fractionOfSecond));
        $forClock = new MonotonicBytesGenerator(clock: new FrozenClock($dateTime));
        $forDateTime = new MonotonicBytesGenerator();

        $this->assertSame($expected, bin2hex(substr($forSystemTime->bytes(), 6, 2)));
        $this->assertSame($expected, bin2hex(substr($forClock->bytes(), 6, 2)));
        $this->assertSame($expected, bin2hex(substr($forDateTime->bytes(dateTime: $dateTime), 6, 2)));
    }

    public function testCounterStartsWithItsFirstTwoBitsCleared(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\xff"),
            new FrozenClock(new DateTimeImmutable('@1700000000')),
        );

        $this->assertSame(self::TIMESTAMP . '0000' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
    }

    public function testVersionAndVariantBitsAreZero(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();
        $dateTime = new DateTimeImmutable('@1700000000.999999');

        for ($i = 0; $i < 1000; $i++) {
            foreach ([$bytesGenerator->bytes(), $bytesGenerator->bytes(dateTime: $dateTime)] as $bytes) {
                $this->assertSame(0, ord($bytes[6]) & 0xf0);
                $this->assertSame(0, ord($bytes[8]) & 0xc0);
            }
        }
    }

    public function testBytesWithoutDateTimeIncreaseWhenClockStandsStill(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(clock: new FrozenClock(new DateTimeImmutable('@1700000000')));

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 10000; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $this->assertSame(self::TIMESTAMP, bin2hex(substr($bytes, 0, 6)));
            $previous = $bytes;
        }
    }

    public function testBytesWithoutDateTimeIncreaseWhenClockMovesBackward(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            os: $this->os(1700000000.0, 1699999999.5, 1600000000.0, 0.0, -5.0, 1700000000.0),
        );

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 5; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $this->assertSame(self::TIMESTAMP, bin2hex(substr($bytes, 0, 6)));
            $previous = $bytes;
        }
    }

    public function testBytesWithoutDateTimeUseALargerFractionInTheSameMillisecond(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new RecordingBytesGenerator(),
            os: $this->os(1700000000.0001, 1700000000.0009, 1700000000.0005),
        );

        // The first increase with a seed of zero bytes is by 0xabc6bc, and the
        // second is by 0xa1c9cd.
        $this->assertSame(self::TIMESTAMP . '0199' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0e66' . '0000000000abc6bc', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0e66' . '00000000014d9089', bin2hex($bytesGenerator->bytes()));
    }

    public function testBytesWithoutDateTimeStartANewCounterInALaterMillisecond(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new RecordingBytesGenerator(),
            os: $this->os(1700000000.0, 1700000000.0, 1700000000.001),
        );

        $bytesGenerator->bytes();

        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($bytesGenerator->bytes()));
        $this->assertSame('018bcfe56801' . '0000' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
    }

    public function testCounterOverflowStepsTheFractionAndThenTheMillisecond(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\xff"),
            new FrozenClock(new DateTimeImmutable('@1700000000.000999')),
        );

        $this->assertSame(self::TIMESTAMP . '0ffb' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0ffc' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0ffd' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0ffe' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0fff' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame('018bcfe56801' . '0000' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
        $this->assertSame('018bcfe56801' . '0001' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));
    }

    public function testBytesWithoutDateTimeIncreaseWhenCounterOverflowsEveryTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\xff"),
            new FrozenClock(new DateTimeImmutable('@1700000000')),
        );

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 10000; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }

        // 10000 steps of the fraction are two whole milliseconds and 1808 more.
        $this->assertSame('018bcfe56802' . '0710', bin2hex(substr($previous, 0, 8)));
    }

    public function testBytesForARepeatedDateTimeIncrease(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();
        $dateTime = new DateTimeImmutable('@1700000000');

        $previous = $bytesGenerator->bytes(dateTime: $dateTime);

        for ($i = 0; $i < 10000; $i++) {
            $bytes = $bytesGenerator->bytes(dateTime: $dateTime);
            $this->assertTrue($previous < $bytes);
            $this->assertSame(self::TIMESTAMP, bin2hex(substr($bytes, 0, 6)));
            $previous = $bytes;
        }
    }

    public function testBytesForARepeatedDateTimeIncreaseWhenCounterOverflowsEveryTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new FixedBytesGenerator("\xff"));
        $dateTime = new DateTimeImmutable('@1700000000');

        $previous = $bytesGenerator->bytes(dateTime: $dateTime);

        for ($i = 0; $i < 10000; $i++) {
            $bytes = $bytesGenerator->bytes(dateTime: $dateTime);
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }

        $this->assertSame('018bcfe56802' . '0710', bin2hex(substr($previous, 0, 8)));
    }

    public function testBytesForADifferentDateTimeStartANewCounter(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator());
        $dateTime = new DateTimeImmutable('@1700000000');
        $earlier = new DateTimeImmutable('@1600000000');

        $first = bin2hex($bytesGenerator->bytes(dateTime: $dateTime));
        $second = bin2hex($bytesGenerator->bytes(dateTime: $dateTime));
        $third = bin2hex($bytesGenerator->bytes(dateTime: $earlier));
        $fourth = bin2hex($bytesGenerator->bytes(dateTime: $dateTime));

        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', $first);
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', $second);
        $this->assertSame('0174876e8000' . '0000' . '0000000000000000', $third);
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', $fourth);
    }

    public function testDateTimeDoesNotChangeBytesWithoutDateTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator(), os: $this->os(1700000000.0));

        $first = $bytesGenerator->bytes();
        $future = $bytesGenerator->bytes(dateTime: new DateTimeImmutable('2100-01-01 00:00:00 +00:00'));
        $past = $bytesGenerator->bytes(dateTime: new DateTimeImmutable('2000-01-01 00:00:00 +00:00'));
        $second = $bytesGenerator->bytes();

        $this->assertSame('03bb2cc3d800', bin2hex(substr($future, 0, 6)));
        $this->assertSame('00dc6acfac00', bin2hex(substr($past, 0, 6)));
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', bin2hex($first));
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($second));
    }

    public function testStepPastTheLastMillisecondThrowsWithoutDateTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\xff"),
            new FrozenClock(new DateTimeImmutable('@281474976710.655999')),
        );

        for ($i = 0; $i < 4; $i++) {
            $bytesGenerator->bytes();
        }

        $this->assertSame('ffffffffffff' . '0fff' . '3fffffffffffffff', bin2hex($bytesGenerator->bytes()));

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage('The timestamp cannot move past 10889-08-02 05:31:50.655 UTC');

        $bytesGenerator->bytes();
    }

    public function testStepPastTheLastMillisecondThrowsForADateTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new FixedBytesGenerator("\xff"));
        $dateTime = new DateTimeImmutable('@281474976710.655999');

        for ($i = 0; $i < 4; $i++) {
            $bytesGenerator->bytes(dateTime: $dateTime);
        }

        $this->assertSame(
            'ffffffffffff' . '0fff' . '3fffffffffffffff',
            bin2hex($bytesGenerator->bytes(dateTime: $dateTime)),
        );

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage('The timestamp cannot move past 10889-08-02 05:31:50.655 UTC');

        $bytesGenerator->bytes(dateTime: $dateTime);
    }

    #[TestWith(['1969-12-31 23:59:59.999999 +00:00'])]
    #[TestWith(['1969-12-31 23:59:59.500 +00:00'])]
    #[TestWith(['1960-06-01 12:00:00.000 +00:00'])]
    #[TestWith(['@-9999999999999'])]
    public function testBytesThrowsWhenClockIsBeforeTheRange(string $dateTime): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(clock: new FrozenClock(new DateTimeImmutable($dateTime)));

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage(
            'The current time is before 1970-01-01 00:00:00.000 UTC, the first time the generator can use',
        );

        $bytesGenerator->bytes();
    }

    #[TestWith(['@281474976710.656'])]
    #[TestWith(['@281474976711'])]
    #[TestWith(['@316516464000'])]
    #[TestWith(['@9999999999999'])]
    public function testBytesThrowsWhenClockIsAfterTheRange(string $dateTime): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(clock: new FrozenClock(new DateTimeImmutable($dateTime)));

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage(
            'The current time is after 10889-08-02 05:31:50.655 UTC, the last time the generator can use',
        );

        $bytesGenerator->bytes();
    }

    #[TestWith([-0.000001])]
    #[TestWith([-1.0])]
    #[TestWith([-8999999999999.0])]
    public function testBytesThrowsWhenSystemTimeIsBeforeTheRange(float $microtime): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(os: $this->os($microtime));

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage(
            'The current time is before 1970-01-01 00:00:00.000 UTC, the first time the generator can use',
        );

        $bytesGenerator->bytes();
    }

    #[TestWith([281474976710.656])]
    #[TestWith([8999999999999.0])]
    public function testBytesThrowsWhenSystemTimeIsAfterTheRange(float $microtime): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(os: $this->os($microtime));

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage(
            'The current time is after 10889-08-02 05:31:50.655 UTC, the last time the generator can use',
        );

        $bytesGenerator->bytes();
    }

    #[TestWith([NAN, 'NAN'])]
    #[TestWith([INF, 'INF'])]
    #[TestWith([-INF, '-INF'])]
    #[TestWith([9000000000000.0, '9000000000000.0'])]
    #[TestWith([-9000000000000.0, '-9000000000000.0'])]
    #[TestWith([1.0e300, '1.0E+300'])]
    public function testBytesThrowsWhenSystemTimeIsNotATime(float $microtime, string $received): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(os: $this->os(1700000000.0, $microtime));

        // The check is made on every call, not only when a sequence starts.
        $bytesGenerator->bytes();

        $this->expectException(TimeSourceFailure::class);
        $this->expectExceptionMessage(
            'The operating system service must return the current time as a count of seconds; received ' . $received,
        );

        $bytesGenerator->bytes();
    }

    public function testBytesAtTheLimitsOfTheRangeForTheSystemTime(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(os: $this->os(0.0, 281474976710.655));

        $this->assertSame('000000000000', bin2hex(substr($bytesGenerator->bytes(), 0, 6)));
        $this->assertSame('ffffffffffff', bin2hex(substr($bytesGenerator->bytes(), 0, 6)));
    }

    #[TestWith(['1969-12-31 23:59:59.999 +00:00', '1969-12-31 23:59:59.999 +00:00'])]
    #[TestWith(['1969-12-31 23:59:59.500 +00:00', '1969-12-31 23:59:59.500 +00:00'])]
    #[TestWith(['1960-06-01 12:00:00.000 +00:00', '1960-06-01 12:00:00.000 +00:00'])]
    #[TestWith(['@281474976710.656', '10889-08-02 05:31:50.656 +00:00'])]
    #[TestWith(['@316516464000', '12000-01-04 00:00:00.000 +00:00'])]
    #[TestWith(['@9999999999999999', '316889355-01-25 17:46:39.000 +00:00'])]
    public function testBytesThrowsExceptionForDateTimeOutOfRange(string $dateTime, string $received): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The date-time must be from 1970-01-01 00:00:00.000 UTC through 10889-08-02 05:31:50.655 UTC; '
            . 'received ' . $received,
        );

        $bytesGenerator->bytes(dateTime: new DateTimeImmutable($dateTime));
    }

    #[TestWith(['1970-01-01 00:00:00.000 +00:00', '000000000000'])]
    #[TestWith(['1970-01-01 00:00:00.001 +00:00', '000000000001'])]
    #[TestWith(['@281474976710.655', 'ffffffffffff'])]
    public function testBytesAtTheLimitsOfTheRange(string $dateTime, string $expectedTimestamp): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();
        $bytes = $bytesGenerator->bytes(dateTime: new DateTimeImmutable($dateTime));

        $this->assertSame($expectedTimestamp, bin2hex(substr($bytes, 0, 6)));
    }

    public function testSeedIsDrawnOnItsOwnWhenACounterIsFirstIncreased(): void
    {
        $source = new RecordingBytesGenerator([16 => str_repeat("\xa5", 16)]);
        $bytesGenerator = new MonotonicBytesGenerator(
            $source,
            new FrozenClock(new DateTimeImmutable('@1700000000')),
        );

        $first = $bytesGenerator->bytes();

        $this->assertSame([8], $source->requests);
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', bin2hex($first));

        $bytesGenerator->bytes();

        $this->assertSame([8, 16], $source->requests);

        for ($i = 0; $i < 100; $i++) {
            $bytesGenerator->bytes();
        }

        $this->assertSame([8, 16], $source->requests);
    }

    public function testChangedProcessIdentifierStepsEachSequenceAndDrawsANewSeed(): void
    {
        $source = new RecordingBytesGenerator();
        $dateTime = new DateTimeImmutable('@1600000000');

        $os = $this->mockery(Os::class);
        $os->allows('microtime')->andReturn(1700000000.0);
        $os->allows('getMyPid')->andReturn(100, 100, 100, 100, 200);

        $bytesGenerator = new MonotonicBytesGenerator($source, os: $os);

        // In the first process, each sequence starts and then increases.
        $bytesGenerator->bytes();
        $bytesGenerator->bytes(dateTime: $dateTime);
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(
            '0174876e8000' . '0000' . '0000000000a1c9cd',
            bin2hex($bytesGenerator->bytes(dateTime: $dateTime)),
        );
        $this->assertSame([8, 8, 16], $source->requests);

        // In the second process, each sequence steps the first time it would
        // have increased, and gets a new counter.
        $this->assertSame(
            '0174876e8000' . '0001' . '0000000000000000',
            bin2hex($bytesGenerator->bytes(dateTime: $dateTime)),
        );
        $this->assertSame([8, 8, 16, 8], $source->requests);
        $this->assertSame(self::TIMESTAMP . '0001' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
        $this->assertSame([8, 8, 16, 8, 8], $source->requests);

        // The next increase draws a new seed, and the chain starts again.
        $this->assertSame(self::TIMESTAMP . '0001' . '0000000000abc6bc', bin2hex($bytesGenerator->bytes()));
        $this->assertSame([8, 8, 16, 8, 8, 16], $source->requests);
        $this->assertSame(
            '0174876e8000' . '0001' . '0000000000a1c9cd',
            bin2hex($bytesGenerator->bytes(dateTime: $dateTime)),
        );
        $this->assertSame([8, 8, 16, 8, 8, 16], $source->requests);
    }

    public function testProcessIdentifierThatCannotBeReadChangesNothing(): void
    {
        $source = new RecordingBytesGenerator();

        $os = $this->mockery(Os::class);
        $os->allows('microtime')->andReturn(1700000000.0);
        $os->allows('getMyPid')->andReturn(false);

        $bytesGenerator = new MonotonicBytesGenerator($source, os: $os);

        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($bytesGenerator->bytes()));
        $this->assertSame(self::TIMESTAMP . '0000' . '00000000014d9089', bin2hex($bytesGenerator->bytes()));
        $this->assertSame([8, 16], $source->requests);
    }

    public function testBytesThrowsWhenRandomSourceReturnsWrongLengthForACounter(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator([8 => 'abc']));

        $this->expectException(RandomSourceFailure::class);
        $this->expectExceptionMessage('The bytes generator must return 8 bytes; received 3 bytes');

        $bytesGenerator->bytes();
    }

    public function testBytesThrowsWhenRandomSourceReturnsWrongLengthForASeed(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new RecordingBytesGenerator([16 => str_repeat('a', 20)]),
            new FrozenClock(new DateTimeImmutable('@1700000000')),
        );

        $bytesGenerator->bytes();

        $this->expectException(RandomSourceFailure::class);
        $this->expectExceptionMessage('The bytes generator must return 16 bytes; received 20 bytes');

        $bytesGenerator->bytes();
    }

    public function testBytesThrowsWhenRandomSourceReturnsWrongLengthForExtraBytes(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(new RecordingBytesGenerator([4 => 'abcde']));

        $this->expectException(RandomSourceFailure::class);
        $this->expectExceptionMessage('The bytes generator must return 4 bytes; received 5 bytes');

        $bytesGenerator->bytes(20);
    }

    public function testGeneratorsWithASystemClockShareOneSequence(): void
    {
        $first = new MonotonicBytesGenerator(new RecordingBytesGenerator(), os: $this->os(1700000000.0));
        $second = new MonotonicBytesGenerator(new RecordingBytesGenerator(), os: $this->os(1600000000.0));

        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', bin2hex($first->bytes()));
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($second->bytes()));
        $this->assertSame(self::TIMESTAMP . '0000' . '00000000014d9089', bin2hex($first->bytes()));
    }

    public function testGeneratorWithAnotherClockKeepsItsOwnSequence(): void
    {
        $system = new MonotonicBytesGenerator(new RecordingBytesGenerator(), os: $this->os(1700000000.0));
        $future = new MonotonicBytesGenerator(
            new RecordingBytesGenerator(),
            new FrozenClock(new DateTimeImmutable('2100-01-01 00:00:00 +00:00')),
        );
        $otherFuture = new MonotonicBytesGenerator(
            new RecordingBytesGenerator(),
            new FrozenClock(new DateTimeImmutable('2100-01-01 00:00:00 +00:00')),
        );

        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000000000', bin2hex($system->bytes()));
        $this->assertSame('03bb2cc3d800' . '0000' . '0000000000000000', bin2hex($future->bytes()));
        $this->assertSame('03bb2cc3d800' . '0000' . '0000000000000000', bin2hex($otherFuture->bytes()));
        $this->assertSame(self::TIMESTAMP . '0000' . '0000000000abc6bc', bin2hex($system->bytes()));
    }

    public function testBytesWithTheSystemClockIncrease(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 10000; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertSame(16, strlen($bytes));
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }

    public function testShorterLengthReturnsTheFirstBytes(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\xff"),
            new FrozenClock(new DateTimeImmutable('@1700000000.000999')),
        );

        $this->assertSame(self::TIMESTAMP . '0ffb' . '3f', bin2hex($bytesGenerator->bytes(9)));
    }

    public function testLongerLengthAddsBytesFromTheRandomSource(): void
    {
        $source = new RecordingBytesGenerator([13 => str_repeat("\xee", 13)]);
        $bytesGenerator = new MonotonicBytesGenerator(
            $source,
            new FrozenClock(new DateTimeImmutable('@1700000000')),
        );

        $bytes = $bytesGenerator->bytes(29);

        $this->assertSame(
            self::TIMESTAMP . '0000' . '0000000000000000' . str_repeat('ee', 13),
            bin2hex($bytes),
        );
        $this->assertSame([8, 13], $source->requests);
    }

    public function testLongerLengthBytesIncrease(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $previous = $bytesGenerator->bytes(29);

        for ($i = 0; $i < 25; $i++) {
            $bytes = $bytesGenerator->bytes(29);
            $this->assertSame(29, strlen($bytes));
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }

    /**
     * Returns an operating system service that returns the given times, one
     * for each call and then the last one again, in a process whose identifier
     * does not change
     */
    private function os(float ...$microtimes): Os & MockInterface
    {
        $os = $this->mockery(Os::class);
        $os->allows('microtime')->andReturn(...$microtimes);
        $os->allows('getMyPid')->andReturn(100);

        return $os;
    }
}
