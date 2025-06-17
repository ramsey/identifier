<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Snowflake;

use Brick\Math\BigInteger;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomBytesGenerator;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Hash\Hash;
use Ramsey\Identifier\Service\Hash\Md5Hash;
use Ramsey\Identifier\Service\Sequence\MonotonicSequence;
use Ramsey\Identifier\Service\Sequence\Sequence;
use Ramsey\Identifier\Snowflake\Internal\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function bin2hex;
use function is_int;
use function sprintf;
use function substr;
use function unpack;

/**
 * A factory that generates Snowflake identifiers for use with the Mastodon open source platform for decentralized
 * social networking.
 *
 * @link https://joinmastodon.org Mastodon.
 * @see MastodonSnowflake
 */
final class MastodonSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    private const TIMESTAMP_BIT_SHIFTS = 16;

    /**
     * @param non-empty-string | null $tableName An optional database table name to ensure different tables derive
     *     separate sequence bases.
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param Sequence $sequence A sequence to prevent collisions within the same millisecond. This defaults to
     *     {@see MonotonicSequence}; to properly implement Mastodon Snowflake identifiers, you might wish to create a
     *     PostgreSQL sequence class that returns the next sequence for the related table.
     * @param BytesGenerator $bytesGenerator A generator of bytes used to prevent collisions within the same millisecond;
     *     defaults to {@see RandomBytesGenerator}.
     * @param Hash $hash A message digest generator used to create a hash of table name, random bytes, and sequence;
     *     defaults to {@see Md5Hash}.
     */
    public function __construct(
        private readonly ?string $tableName = null,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new MonotonicSequence(start: 0, step: 5),
        private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator(),
        private readonly Hash $hash = new Md5Hash(),
    ) {
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): MastodonSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
     * @param non-empty-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): MastodonSnowflake
    {
        return new MastodonSnowflake($this->convertFromBytes($identifier));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): MastodonSnowflake
    {
        /** @var int $milliseconds */
        $milliseconds = (int) $dateTime->format(Precision::Millisecond->value);

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the Mastodon epoch, %s',
                Epoch::Mastodon->toIso8601(),
            ));
        }

        if ($milliseconds > 0xffffffffffff) {
            throw new InvalidArgument(
                'Mastodon Snowflakes cannot have a date-time greater than 10889-08-02T05:31:50.655Z',
            );
        }

        $millisecondsShifted = $milliseconds << self::TIMESTAMP_BIT_SHIFTS;

        // Did we go beyond the bounds of a signed, 64-bit integer?
        if ($millisecondsShifted < $milliseconds) {
            $millisecondsShifted = (string) BigInteger::of($milliseconds)->shiftedLeft(self::TIMESTAMP_BIT_SHIFTS);
        }

        // Create a hash of the table name, random string (salt), and timestamp.
        $hash = $this->hash->hash(
            data: $this->tableName . bin2hex($this->bytesGenerator->bytes(16)) . $millisecondsShifted,
            binary: true,
        );

        /**
         * We form the sequence base from the first two bytes of the hash.
         *
         * @var array{1: int<0, 65535>} $sequenceBase
         */
        $sequenceBase = unpack('n', substr($hash, 0, 2));

        // Add the next sequence to the base and chop the value to the last two bytes.
        $tail = $sequenceBase[1] + (int) $this->sequence->next($this->tableName) & 0xffff;

        if (is_int($millisecondsShifted)) {
            /** @var int<0, max> $identifier */
            $identifier = $millisecondsShifted | $tail;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($millisecondsShifted)->or($tail);
        }

        return new MastodonSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): MastodonSnowflake
    {
        return new MastodonSnowflake($this->convertFromHexadecimal($identifier));
    }

    /**
     * @param int<0, max> | numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): MastodonSnowflake
    {
        return new MastodonSnowflake($identifier);
    }

    /**
     * @param numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): MastodonSnowflake
    {
        return new MastodonSnowflake($identifier);
    }
}
