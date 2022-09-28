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

namespace Ramsey\Identifier\Uuid\Factory;

use DateTimeInterface;
use Identifier\Uuid\UuidFactoryInterface;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NodeNotFoundException;
use Ramsey\Identifier\Exception\RandomSourceException;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceServiceInterface;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Identifier\Service\Node\FallbackNodeService;
use Ramsey\Identifier\Service\Node\NodeServiceInterface;
use Ramsey\Identifier\Service\Node\RandomNodeService;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Identifier\Service\Time\TimeServiceInterface;
use Ramsey\Identifier\Uuid\Util;
use Ramsey\Identifier\Uuid\UuidV6;

use function bin2hex;
use function hex2bin;
use function pack;
use function sprintf;
use function substr;

/**
 * A factory for creating version 6, reordered time UUIDs
 */
final class UuidV6Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    public function __construct(
        private readonly ClockSequenceServiceInterface $clockSequenceService = new RandomClockSequenceService(),
        private readonly NodeServiceInterface $nodeService = new FallbackNodeService([
            new SystemNodeService(),
            new RandomNodeService(),
        ]),
        private readonly TimeServiceInterface $timeService = new CurrentDateTimeService(),
    ) {
    }

    /**
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     *
     * @throws RandomSourceException
     * @throws InvalidArgumentException
     * @throws NodeNotFoundException
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        int | string | null $node = null,
        ?DateTimeInterface $dateTime = null,
        ?int $clockSequence = null,
    ): UuidV6 {
        $dateTime = $dateTime ?? $this->timeService->getDateTime();
        $clockSequence = $clockSequence === null
            ? $this->clockSequenceService->getClockSequence()
            : (new StaticClockSequenceService($clockSequence))->getClockSequence();
        $node = $node === null ? $this->nodeService->getNode() : (new StaticNodeService($node))->getNode();

        $timeBytes = Util::getTimeBytesForGregorianEpoch($dateTime);
        $timeHex = bin2hex($timeBytes);

        /** @psalm-var non-empty-string $bytes */
        $bytes = hex2bin(substr($timeHex, 1, 12) . '0' . substr($timeHex, -3))
            . pack('n*', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = Util::applyVersionAndVariant($bytes, Version::ReorderedGregorianTime);

        return new UuidV6($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UuidV6
    {
        /** @var UuidV6 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
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
