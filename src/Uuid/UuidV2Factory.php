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
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\ClockSequence;
use Ramsey\Identifier\Service\Clock\RandomClockSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Dce\Dce;
use Ramsey\Identifier\Service\Dce\SystemDce;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\TimeBasedUuidFactory;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;
use Ramsey\Identifier\Uuid\Utility\Time;

use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 2, DCE Security UUIDs.
 */
final class UuidV2Factory implements TimeBasedUuidFactory
{
    use StandardFactory;

    private readonly Binary $binary;
    private readonly Time $time;

    /**
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param Dce $dce A service that provides local identifiers when creating version 2 UUIDs; defaults to {@see SystemDce}.
     * @param Nic $nic A NIC that provides the system MAC address value; defaults to {@see RandomNic}.
     * @param ClockSequence $sequence A sequence that provides a clock sequence value to prevent collisions; defaults to {@see RandomClockSequence}.
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly Dce $dce = new SystemDce(),
        private readonly Nic $nic = new RandomNic(),
        private readonly ClockSequence $sequence = new RandomClockSequence(),
    ) {
        $this->binary = new Binary();
        $this->time = new Time();
    }

    /**
     * @param DceDomain $localDomain The local domain to which the local identifier belongs; this defaults to "Person,"
     *     and if $localIdentifier is not provided, the factory will attempt to get a suitable local ID for the domain
     *     (e.g., the UID or GID of the user running the script).
     * @param int<0, 4294967295> | null $localIdentifier A 32-bit local identifier belonging to the local domain
     *     specified in `$localDomain`; if no identifier is provided, the factory will attempt to get a suitable local
     *     ID for the domain (e.g., the UID or GID of the user running the script).
     * @param Nic | int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 64, giving it an
     *     effective range of 0-63 (i.e., 6 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws DceIdentifierNotFound
     * @throws InvalidArgument
     */
    public function create(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        Nic | int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        $localIdentifier = $localIdentifier ?? match ($localDomain) {
            DceDomain::Person => $this->dce->userId(),
            DceDomain::Group => $this->dce->groupId(),
            default => $this->dce->orgId(),
        };

        if ($localIdentifier < 0 || $localIdentifier > 0xffffffff) {
            throw new InvalidArgument('$localIdentifier must be a non-negative 32-bit integer');
        }

        if ($node === null) {
            $node = $this->nic->address();
        } elseif ($node instanceof Nic) {
            $node = $node->address();
        } else {
            $node = (new StaticNic($node))->address();
        }

        $dateTime = $dateTime ?? $this->clock->now();

        // Use modular arithmetic to roll over the sequence value at mod 0x40 (64).
        $clockSequence = ($clockSequence ?? $this->sequence->next($node, $dateTime)) % 0x40;

        $timeBytes = $this->time->getTimeBytesForGregorianEpoch($dateTime);

        $bytes = pack('N', $localIdentifier)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n', $clockSequence)[1]
            . pack('n', $localDomain->value)[1]
            . hex2bin(sprintf('%012s', $node));

        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::DceSecurity);

        return new UuidV2($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV2
    {
        /** @var UuidV2 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws DceIdentifierNotFound
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV2
    {
        return $this->create(dateTime: $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV2
    {
        /** @var UuidV2 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV2
    {
        /** @var UuidV2 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV2
    {
        /** @var UuidV2 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::DceSecurity;
    }

    protected function getUuidClass(): string
    {
        return UuidV2::class;
    }
}
