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

namespace Ramsey\Identifier\Uuid;

use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\ClockSequence;
use Ramsey\Identifier\Service\Clock\RandomClockSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\TimeBasedUuidFactory;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;
use Ramsey\Identifier\Uuid\Internal\Time;

use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 1, Gregorian time UUIDs.
 */
final class UuidV1Factory implements TimeBasedUuidFactory
{
    use StandardFactory;

    private readonly Binary $binary;
    private readonly Time $time;

    /**
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param Nic $nic A NIC that provides the system MAC address value; defaults to {@see RandomNic}.
     * @param ClockSequence $sequence A sequence that provides a clock sequence value to prevent collisions; defaults to {@see RandomClockSequence}.
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly Nic $nic = new RandomNic(),
        private readonly ClockSequence $sequence = new RandomClockSequence(),
    ) {
        $this->binary = new Binary();
        $this->time = new Time();
    }

    /**
     * @param Nic | int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 16,384, giving it an
     *     effective range of 0-16383 (i.e., 14 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument
     */
    public function create(
        Nic | int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        if ($node === null) {
            $node = $this->nic->address();
        } elseif ($node instanceof Nic) {
            $node = $node->address();
        } else {
            $node = (new StaticNic($node))->address();
        }

        $dateTime = $dateTime ?? $this->clock->now();

        // Use modular arithmetic to roll over the sequence value at mod 0x4000 (16384).
        $clockSequence = ($clockSequence ?? $this->sequence->next($node, $dateTime)) % 0x4000;

        $timeBytes = $this->time->getTimeBytesForGregorianEpoch($dateTime);

        /** @var non-empty-string $bytes */
        $bytes = substr($timeBytes, -4)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::GregorianTime);

        return new UuidV1($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV1
    {
        return $this->create(dateTime: $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::GregorianTime;
    }

    protected function getUuidClass(): string
    {
        return UuidV1::class;
    }
}
