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
use Ramsey\Identifier\Exception\NodeNotFound;
use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Identifier\Service\DateTime\CurrentDateTimeService;
use Ramsey\Identifier\Service\DateTime\DateTimeService;
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
 * A factory for creating version 1, Gregorian time UUIDs
 */
final class UuidV1Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardUuidFactory;

    /**
     * Constructs a factory for creating version 1, Gregorian time UUIDs
     *
     * @param ClockSequenceService $clockSequenceService A service used
     *     to generate a clock sequence; defaults to
     *     {@see RandomClockSequenceService}
     * @param NodeService $nodeService A service used to provide the
     *     system node; defaults to {@see FallbackNodeService} with
     *     {@see SystemNodeService} and {@see RandomNodeService}, as a fallback
     * @param DateTimeService $timeService A service used to provide a
     *     date-time instance; defaults to {@see CurrentDateTimeService}
     */
    public function __construct(
        private readonly ClockSequenceService $clockSequenceService = new RandomClockSequenceService(),
        private readonly NodeService $nodeService = new FallbackNodeService([
            new SystemNodeService(),
            new RandomNodeService(),
        ]),
        private readonly DateTimeService $timeService = new CurrentDateTimeService(),
    ) {
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
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        $node = $node === null ? $this->nodeService->getNode() : (new StaticNodeService($node))->getNode();
        $dateTime = $dateTime ?? $this->timeService->getDateTime();
        $clockSequence = $clockSequence === null
            ? $this->clockSequenceService->getClockSequence()
            : (new StaticClockSequenceService($clockSequence))->getClockSequence();

        $timeBytes = Time::getTimeBytesForGregorianEpoch($dateTime);

        /** @psalm-var non-empty-string $bytes */
        $bytes = substr($timeBytes, -4)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n*', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = Binary::applyVersionAndVariant($bytes, Version::GregorianTime);

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
     * @throws NodeNotFound
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
