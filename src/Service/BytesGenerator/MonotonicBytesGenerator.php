<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\BytesGenerator;

use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;

use function intdiv;
use function pack;
use function round;
use function sprintf;
use function strlen;
use function substr;
use function unpack;
use function var_export;

use const PHP_INT_MAX;

/**
 * A generator that returns a 48-bit timestamp in milliseconds since the Unix
 * Epoch, a 12-bit fraction of the millisecond, and a 62-bit counter that
 * starts at a random value
 *
 * The fraction follows RFC 9562 section 6.2, method 3: the part of the time
 * below one millisecond, multiplied by 4096 and rounded down. The counter
 * follows method 2 of the same section.
 *
 * Bytes generated in one process without a date-time always increase, whatever
 * the clock does. Bytes generated for the same date-time, in one call after
 * another, always increase too. A date-time passed to one call has no effect
 * on the calls made without one. A length of less than 16 returns only the
 * first bytes, which can repeat.
 *
 * The four bits before the fraction and the two bits before the counter are
 * zero. They are where a version 7 UUID has its version and its variant.
 *
 * Generators created with a {@see SystemClock} share one timestamp and one
 * counter, so the bytes of all of them increase together. A generator created
 * with any other clock keeps its own.
 *
 * Code and concepts within this class are borrowed from the symfony/uid package
 * and are used under the terms of the MIT license distributed with symfony/uid.
 *
 * symfony/uid is copyright (c) Fabien Potencier.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.2 RFC 9562, section 6.2. Monotonicity and Counters
 * @link https://symfony.com/components/Uid Symfony Uid component
 * @link https://github.com/symfony/uid/blob/v7.2.0/UuidV7.php Symfony UuidV7 class
 * @link https://github.com/symfony/uid/blob/7.2/LICENSE MIT License
 */
final class MonotonicBytesGenerator implements BytesGenerator
{
    /**
     * The number of random bytes that make one batch of 21 numbers
     */
    private const int BATCH_LENGTH = 64;

    /**
     * The largest value of the 62-bit counter
     */
    private const int MAX_COUNTER = 0x3fffffffffffffff;

    /**
     * The largest value of the 12-bit fraction of a millisecond
     */
    private const int MAX_FRACTION = 0x0fff;

    /**
     * The largest count of milliseconds since the Unix Epoch that fits in the
     * 48-bit timestamp
     */
    private const int MAX_MILLISECOND = 0x0000ffffffffffff;

    /**
     * The whole seconds of the largest count of milliseconds
     */
    private const int MAX_SECOND = 281474976710;

    /**
     * A count of seconds, far outside the range of the timestamp, below which
     * the microseconds of a float are sure to fit in an integer
     */
    private const float SECONDS_LIMIT = 9_000_000_000_000.0;

    /**
     * The state that every generator created with a SystemClock uses; null
     * until the first one is created
     */
    private static ?MonotonicState $sharedState = null;

    private readonly bool $readsSystemTime;
    private readonly MonotonicState $state;

    /**
     * @param BytesGenerator $bytesGenerator A source of random bytes for the
     *     counter and for the numbers it increases by
     * @param Clock $clock A clock used to get the current date-time
     * @param Os $os An operating system service used to read the identifier of
     *     the current process and, with a SystemClock, the current time
     */
    public function __construct(
        private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator(),
        private readonly Clock $clock = new SystemClock(),
        private readonly Os $os = new PhpOs(),
    ) {
        $this->readsSystemTime = $this->clock instanceof SystemClock;

        if ($this->readsSystemTime) {
            $this->state = self::$sharedState ??= new MonotonicState();
        } else {
            $this->state = new MonotonicState();
        }
    }

    /**
     * @throws InvalidArgument if the length is less than 1, or if the date-time
     *     is before 1970-01-01 00:00:00.000 UTC or after 10889-08-02
     *     05:31:50.655 UTC
     * @throws RandomSourceFailure if the source of random bytes returns a
     *     different number of bytes than it was asked for
     * @throws TimeSourceFailure if the current time is after that same range,
     *     is before it when no earlier call set a time to continue from, or is
     *     not a time at all; or if the timestamp would have to move past the
     *     end of the range
     */
    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        if ($length < 1) {
            throw new InvalidArgument(sprintf('The length must be a positive integer; received %d', $length));
        }

        if ($dateTime === null) {
            $sequence = $this->nextForClock();
        } else {
            $sequence = $this->nextForDateTime($dateTime);
        }

        // The first number is the timestamp, four zero bits, and the fraction.
        // The second is two zero bits and the counter.
        $bytes = pack('JJ', $sequence->millisecond << 16 | $sequence->fraction, $sequence->counter);

        /** @var non-empty-string */
        return match (true) {
            $length > 16 => $bytes . $this->randomBytes($length - 16),
            $length < 16 => substr($bytes, 0, $length),
            default => $bytes,
        };
    }

    /**
     * Returns the timestamp and counter for the current time
     *
     * When the clock has not passed the last millisecond used, whether it
     * stands still or has moved backward, the last millisecond is used again
     * and the counter increases.
     *
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    private function nextForClock(): MonotonicSequence
    {
        if ($this->readsSystemTime) {
            // The clock is not called here; building a date-time object for
            // each call costs more than reading the time directly.
            $seconds = $this->os->microtime();

            // A float that is not a number fails both comparisons.
            if (!($seconds > -self::SECONDS_LIMIT && $seconds < self::SECONDS_LIMIT)) {
                throw new TimeSourceFailure(sprintf(
                    'The operating system service must return the current time as a count of seconds; received %s',
                    var_export($seconds, true),
                ));
            }

            $microseconds = (int) round($seconds * 1_000_000);
        } else {
            $microseconds = $this->microsecondsFromClock();
        }

        $millisecond = intdiv($microseconds, 1000);
        $fraction = intdiv($microseconds % 1000 * (self::MAX_FRACTION + 1), 1000);

        $sequence = $this->state->clockSequence;

        if ($sequence === null || $millisecond > $sequence->millisecond) {
            if ($microseconds < 0) {
                throw new TimeSourceFailure(
                    'The current time is before 1970-01-01 00:00:00.000 UTC, the first time the generator can use',
                );
            }

            if ($millisecond > self::MAX_MILLISECOND) {
                throw new TimeSourceFailure(
                    'The current time is after 10889-08-02 05:31:50.655 UTC, the last time the generator can use',
                );
            }

            return $this->state->clockSequence = $this->start($millisecond, $fraction);
        }

        if ($millisecond === $sequence->millisecond && $fraction > $sequence->fraction) {
            $sequence->fraction = $fraction;
        }

        $this->increase($sequence);

        return $sequence;
    }

    /**
     * Returns the timestamp and counter for a date-time
     *
     * A date-time equal, to the microsecond, to the one in the call before it
     * continues that call's counter. Any other date-time starts a new counter.
     * The timestamp and counter for the current time are left alone.
     *
     * @throws InvalidArgument
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    private function nextForDateTime(DateTimeInterface $dateTime): MonotonicSequence
    {
        $seconds = $dateTime->getTimestamp();

        // The seconds are checked before they are multiplied, since a
        // date-time can be too far from the Unix Epoch to count in
        // milliseconds.
        if ($seconds < 0 || $seconds > self::MAX_SECOND) {
            throw $this->dateTimeOutOfRange($dateTime);
        }

        $microseconds = (int) $dateTime->format('u');
        $millisecond = $seconds * 1000 + intdiv($microseconds, 1000);

        if ($millisecond > self::MAX_MILLISECOND) {
            throw $this->dateTimeOutOfRange($dateTime);
        }

        $fraction = intdiv($microseconds % 1000 * (self::MAX_FRACTION + 1), 1000);

        $sequence = $this->state->datedSequence;

        if (
            $sequence !== null
            && $sequence->givenMillisecond === $millisecond
            && $sequence->givenFraction === $fraction
        ) {
            $this->increase($sequence);

            return $sequence;
        }

        $sequence = $this->start($millisecond, $fraction);
        $sequence->givenMillisecond = $millisecond;
        $sequence->givenFraction = $fraction;

        return $this->state->datedSequence = $sequence;
    }

    /**
     * Returns the clock's time as a count of microseconds since the Unix Epoch
     *
     * A date-time can be further from the Unix Epoch than an integer can count
     * in microseconds. Every time before the Unix Epoch is handled the same
     * way, and so is every time after the last millisecond, so one count is
     * returned for each side.
     */
    private function microsecondsFromClock(): int
    {
        $dateTime = $this->clock->now();
        $seconds = $dateTime->getTimestamp();

        if ($seconds < 0) {
            return -1;
        }

        if ($seconds > self::MAX_SECOND) {
            return PHP_INT_MAX;
        }

        return $seconds * 1_000_000 + (int) $dateTime->format('u');
    }

    private function dateTimeOutOfRange(DateTimeInterface $dateTime): InvalidArgument
    {
        return new InvalidArgument(sprintf(
            'The date-time must be from 1970-01-01 00:00:00.000 UTC through 10889-08-02 05:31:50.655 UTC; '
            . 'received %s',
            $dateTime->format('Y-m-d H:i:s.v P'),
        ));
    }

    /**
     * Returns a sequence at the given timestamp with a new random counter
     *
     * @throws RandomSourceFailure
     */
    private function start(int $millisecond, int $fraction): MonotonicSequence
    {
        $sequence = new MonotonicSequence($millisecond, $fraction, 0, false);
        $this->restart($sequence, $this->os->getMyPid());

        return $sequence;
    }

    /**
     * Gives the sequence a new random counter and the identifier of the
     * current process
     *
     * @throws RandomSourceFailure
     */
    private function restart(MonotonicSequence $sequence, int | false $processId): void
    {
        /** @var array{1: int} $random */
        $random = unpack('J', $this->randomBytes(8));

        $sequence->counter = $random[1] & self::MAX_COUNTER;
        $sequence->processId = $processId;
    }

    /**
     * Increases the counter of the sequence by 1 plus a random 24-bit number
     *
     * When the counter has no room for the number, or when the sequence was
     * last used by another process, which happens after a fork, the timestamp
     * moves forward and the counter starts again.
     *
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    private function increase(MonotonicSequence $sequence): void
    {
        $processId = $this->os->getMyPid();

        if ($sequence->processId !== $processId) {
            $this->step($sequence, $processId);

            return;
        }

        $counter = $sequence->counter + $this->nextIncrement($processId);

        if ($counter > self::MAX_COUNTER) {
            $this->step($sequence, $processId);

            return;
        }

        $sequence->counter = $counter;
    }

    /**
     * Moves the sequence one fraction forward, or to the start of the next
     * millisecond when the fraction is at its largest value, and gives it a
     * new random counter
     *
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    private function step(MonotonicSequence $sequence, int | false $processId): void
    {
        if ($sequence->fraction < self::MAX_FRACTION) {
            $sequence->fraction++;
            $this->restart($sequence, $processId);

            return;
        }

        if ($sequence->millisecond === self::MAX_MILLISECOND) {
            throw new TimeSourceFailure('The timestamp cannot move past 10889-08-02 05:31:50.655 UTC');
        }

        $sequence->millisecond++;
        $sequence->fraction = 0;
        $this->restart($sequence, $processId);
    }

    /**
     * Returns 1 plus the next random 24-bit number
     *
     * The numbers come from the source of random bytes in batches, since
     * asking it for each one is slow. A batch is 64 bytes. unpack() splits
     * them into sixteen 32-bit numbers, and the highest byte of each of the
     * first fifteen makes five more 24-bit numbers. Each of the 21 numbers is
     * used once, and then a new batch is drawn.
     *
     * A process that did not draw the batch, which happens after a fork, draws
     * its own, so a parent and a child never use the same numbers.
     *
     * @throws RandomSourceFailure
     */
    private function nextIncrement(int | false $processId): int
    {
        $state = $this->state;

        if ($state->incrementIndex === 0 || $state->incrementProcessId !== $processId) {
            // The byte order is fixed, so the same bytes give the same numbers
            // on every machine.
            /** @var int[] $s */
            $s = unpack('V*', $this->randomBytes(self::BATCH_LENGTH));
            $s[] = ($s[1] >> 8 & 0xff0000) | ($s[2] >> 16 & 0xff00) | ($s[3] >> 24 & 0xff);
            $s[] = ($s[4] >> 8 & 0xff0000) | ($s[5] >> 16 & 0xff00) | ($s[6] >> 24 & 0xff);
            $s[] = ($s[7] >> 8 & 0xff0000) | ($s[8] >> 16 & 0xff00) | ($s[9] >> 24 & 0xff);
            $s[] = ($s[10] >> 8 & 0xff0000) | ($s[11] >> 16 & 0xff00) | ($s[12] >> 24 & 0xff);
            $s[] = ($s[13] >> 8 & 0xff0000) | ($s[14] >> 16 & 0xff00) | ($s[15] >> 24 & 0xff);

            $state->increments = $s;
            $state->incrementProcessId = $processId;
            $state->incrementIndex = 21;
        }

        return 1 + ($state->increments[$state->incrementIndex--] & 0xffffff);
    }

    /**
     * Returns bytes from the source of random bytes, after checking that it
     * returned as many as it was asked for
     *
     * @param positive-int $length
     *
     * @throws RandomSourceFailure
     */
    private function randomBytes(int $length): string
    {
        $bytes = $this->bytesGenerator->bytes($length);

        if (strlen($bytes) !== $length) {
            throw new RandomSourceFailure(sprintf(
                'The bytes generator must return %d bytes; received %d bytes',
                $length,
                strlen($bytes),
            ));
        }

        return $bytes;
    }
}
