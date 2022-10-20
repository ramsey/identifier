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
use Identifier\BinaryIdentifierFactory;
use Identifier\DateTimeIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\MacAddressNotFound;
use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Counter\Counter;
use Ramsey\Identifier\Service\Counter\RandomCounter;
use Ramsey\Identifier\Service\Nic\FallbackNic;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\Service\Nic\SystemNic;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardUuidFactory;
use Ramsey\Identifier\Uuid\Utility\Time;
use StellaMaris\Clock\ClockInterface as Clock;

use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 1, Gregorian time UUIDs
 */
final class UuidV1Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardUuidFactory;

    private readonly Binary $binary;
    private readonly Time $time;

    /**
     * Constructs a factory for creating version 1, Gregorian time UUIDs
     *
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Counter $counter A counter that provides the next value in a
     *     sequence to prevent collisions; defaults to {@see RandomCounter}
     * @param Nic $nic A NIC that provides the system MAC address value;
     *     defaults to {@see FallbackNic}, with {@see SystemNic} and
     *     {@see RandomNic} as fallbacks
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly Counter $counter = new RandomCounter(),
        private readonly Nic $nic = new FallbackNic([new SystemNic(), new RandomNic()]),
    ) {
        $this->binary = new Binary();
        $this->time = new Time();
    }

    /**
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws MacAddressNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        $node = $node === null ? $this->nic->address() : (new StaticNic($node))->address();
        $clockSequence = ($clockSequence ?? $this->counter->next()) % 16384;
        $dateTime = $dateTime ?? $this->clock->now();

        $timeBytes = $this->time->getTimeBytesForGregorianEpoch($dateTime);

        /** @psalm-var non-empty-string $bytes */
        $bytes = substr($timeBytes, -4)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n*', $clockSequence)
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
     * @throws MacAddressNotFound
     * @throws RandomSourceNotFound
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV1
    {
        return $this->create(dateTime: $dateTime);
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

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::GregorianTime;
    }

    protected function getUuidClass(): string
    {
        return UuidV1::class;
    }
}
