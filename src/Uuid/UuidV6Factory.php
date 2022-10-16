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

use function bin2hex;
use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 6, reordered time UUIDs
 */
final class UuidV6Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardUuidFactory;

    /**
     * Constructs a factory for creating version 6, reordered time UUIDs
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
     * @throws RandomSourceNotFound
     * @throws InvalidArgument
     * @throws NodeNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        $dateTime = $dateTime ?? $this->timeService->getDateTime();
        $clockSequence = $clockSequence === null
            ? $this->clockSequenceService->getClockSequence()
            : (new StaticClockSequenceService($clockSequence))->getClockSequence();
        $node = $node === null ? $this->nodeService->getNode() : (new StaticNodeService($node))->getNode();

        $timeBytes = Time::getTimeBytesForGregorianEpoch($dateTime);
        $timeHex = bin2hex($timeBytes);

        /** @psalm-var non-empty-string $bytes */
        $bytes = hex2bin(substr($timeHex, 1, 12) . '0' . substr($timeHex, -3))
            . pack('n*', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = Binary::applyVersionAndVariant($bytes, Version::ReorderedGregorianTime);

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
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
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

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::ReorderedGregorianTime;
    }

    protected function getUuidClass(): string
    {
        return UuidV6::class;
    }
}
