<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
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

use function bin2hex;
use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 6, reordered Gregorian time UUIDs.
 */
final class UuidV6Factory implements TimeBasedUuidFactory
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
     * @param int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 16,384, giving it an
     *     effective range of 0-16383 (i.e., 14 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument
     */
    public function create(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        $node = $node === null ? $this->nic->address() : (new StaticNic($node))->address();
        $dateTime = $dateTime ?? $this->clock->now();

        // Use modular arithmetic to roll over the sequence value at mod 0x4000 (16384).
        $clockSequence = ($clockSequence ?? $this->sequence->next($node, $dateTime)) % 0x4000;

        $timeBytes = $this->time->getTimeBytesForGregorianEpoch($dateTime);
        $timeHex = bin2hex($timeBytes);

        /** @var non-empty-string $bytes */
        $bytes = hex2bin(substr($timeHex, 1, 12) . '0' . substr($timeHex, -3))
            . pack('n', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::ReorderedGregorianTime);

        return new UuidV6($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV6
    {
        return $this->create(dateTime: $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::ReorderedGregorianTime;
    }

    protected function getUuidClass(): string
    {
        return UuidV6::class;
    }
}
