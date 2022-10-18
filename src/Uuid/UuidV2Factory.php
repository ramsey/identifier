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
use Ramsey\Identifier\Exception\DceSecurityIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Exception\NodeNotFound;
use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Identifier\Service\DateTime\CurrentDateTimeService;
use Ramsey\Identifier\Service\DateTime\DateTimeService;
use Ramsey\Identifier\Service\DceSecurity\DceSecurityService;
use Ramsey\Identifier\Service\DceSecurity\SystemDceSecurityService;
use Ramsey\Identifier\Service\Node\FallbackNodeService;
use Ramsey\Identifier\Service\Node\NodeService;
use Ramsey\Identifier\Service\Node\RandomNodeService;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardUuidFactory;
use Ramsey\Identifier\Uuid\Utility\Time;

use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 2, DCE Security UUIDs
 */
final class UuidV2Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardUuidFactory;

    /**
     * Constructs a factory for creating version 2, DCE Security UUIDs
     *
     * @param ClockSequenceService $clockSequenceService A service used
     *     to generate a clock sequence; defaults to
     *     {@see RandomClockSequenceService}
     * @param DceSecurityService $dceSecurityService A service used
     *     to get local identifiers when creating version 2 UUIDs; defaults to
     *     {@see SystemDceSecurityService}
     * @param NodeService $nodeService A service used to provide the
     *     system node; defaults to {@see FallbackNodeService} with
     *     {@see SystemNodeService} and {@see RandomNodeService}, as a fallback
     * @param DateTimeService $timeService A service used to provide a
     *     date-time instance; defaults to {@see CurrentDateTimeService}
     */
    public function __construct(
        private readonly ClockSequenceService $clockSequenceService = new RandomClockSequenceService(),
        private readonly DceSecurityService $dceSecurityService = new SystemDceSecurityService(),
        private readonly NodeService $nodeService = new FallbackNodeService([
            new SystemNodeService(),
            new RandomNodeService(),
        ]),
        private readonly DateTimeService $timeService = new CurrentDateTimeService(),
    ) {
    }

    /**
     * @param DceDomain $localDomain The local domain to which the local identifier
     *     belongs; this defaults to "Person," and if $localIdentifier is not
     *     provided, the factory will attempt to obtain a suitable local ID for
     *     the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | null $localIdentifier A local identifier belonging
     *     to the local domain specified in $localDomain; if no identifier is
     *     provided, the factory will attempt to obtain a suitable local ID for
     *     the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 63> | null $clockSequence A 6-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidCacheKey
     * @throws DceSecurityIdentifierNotFound
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        $node = $node === null ? $this->nodeService->getNode() : (new StaticNodeService($node))->getNode();
        $dateTime = $dateTime ?? $this->timeService->getDateTime();
        $clockSequence = $clockSequence === null
            ? $this->clockSequenceService->getClockSequence()
            : (new StaticClockSequenceService($clockSequence))->getClockSequence();

        $localIdentifier = $localIdentifier ?? match ($localDomain) {
            DceDomain::Person => $this->dceSecurityService->getPersonIdentifier(),
            DceDomain::Group => $this->dceSecurityService->getGroupIdentifier(),
            default => $this->dceSecurityService->getOrgIdentifier(),
        };

        $timeBytes = Time::getTimeBytesForGregorianEpoch($dateTime);

        $bytes = pack('N', $localIdentifier)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n', $clockSequence)[1]
            . pack('n', $localDomain->value)[1]
            . hex2bin(sprintf('%012s', $node));

        $bytes = Binary::applyVersionAndVariant($bytes, Version::DceSecurity);

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
     * @throws InvalidCacheKey
     * @throws DceSecurityIdentifierNotFound
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV2
    {
        return $this->create(dateTime: $dateTime);
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

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::DceSecurity;
    }

    protected function getUuidClass(): string
    {
        return UuidV2::class;
    }
}
