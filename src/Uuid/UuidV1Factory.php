<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\Sequence;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\Service\Nic\SystemNic;
use Ramsey\Identifier\TimeBasedUuidFactory;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;
use Ramsey\Identifier\Uuid\Utility\Time;

use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 1, Gregorian time UUIDs
 */
final class UuidV1Factory implements TimeBasedUuidFactory
{
    use StandardFactory;

    private readonly Binary $binary;
    private readonly Time $time;

    /**
     * Constructs a factory for creating version 1, Gregorian time UUIDs
     *
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Nic $nic A NIC that provides the system MAC address value;
     *     defaults to {@see SystemNic}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence}
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly Nic $nic = new SystemNic(),
        private readonly Sequence $sequence = new StatefulSequence(),
    ) {
        $this->binary = new Binary();
        $this->time = new Time();
    }

    /**
     * @param int<0, max> | non-empty-string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     */
    public function create(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        $node = $node === null ? $this->nic->address() : (new StaticNic($node))->address();
        $dateTime = $dateTime ?? $this->clock->now();
        $clockSequence = ($clockSequence ?? $this->sequence->value($node, $dateTime)) % 16384;

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
