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

use Brick\Math\BigInteger;
use DateTimeInterface;
use Exception;
use Identifier\Uuid\UuidFactoryInterface;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceServiceInterface;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Identifier\Service\Node\NodeServiceInterface;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Identifier\Service\Time\TimeServiceInterface;
use Ramsey\Identifier\Uuid\Util;
use Ramsey\Identifier\Uuid\UuidV1;

use function hex2bin;
use function pack;
use function sprintf;
use function str_pad;
use function substr;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * A factory for creating version 1, Gregorian time UUIDs
 */
final class UuidV1Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    public function __construct(
        private readonly ClockSequenceServiceInterface $clockSequenceService = new RandomClockSequenceService(),
        private readonly NodeServiceInterface $nodeService = new SystemNodeService(),
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
     * @throws Exception If a suitable source of randomness is not available
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function create(
        int | string | null $node = null,
        ?DateTimeInterface $dateTime = null,
        ?int $clockSequence = null,
    ): UuidV1 {
        /** @psalm-suppress ImpureMethodCall */
        $node = $node !== null
            ? (new StaticNodeService($node))->getNode()
            : $this->nodeService->getNode();

        /** @psalm-suppress ImpureMethodCall */
        $dateTime = $dateTime ?? $this->timeService->getDateTime();

        /** @psalm-suppress ImpureMethodCall */
        $clockSequence = $clockSequence !== null
            ? (new StaticClockSequenceService($clockSequence))->getClockSequence()
            : $this->clockSequenceService->getClockSequence();

        if (PHP_INT_SIZE >= 8) {
            $timeBytes = pack('J*', (int) $dateTime->format('Uu0') + Util::GREGORIAN_OFFSET_INT);
        } else {
            /** @psalm-suppress ImpureMethodCall */
            $timeBytes = str_pad(
                BigInteger::of($dateTime->format('Uu0'))->plus(
                    BigInteger::fromBytes(Util::GREGORIAN_OFFSET_BIN),
                )->toBytes(false),
                8,
                "\x00",
                STR_PAD_LEFT,
            );
        }

        /** @var non-empty-string $bytes */
        $bytes = substr($timeBytes, -4)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n*', $clockSequence)
            . hex2bin(sprintf('%012s', $node));

        $bytes = Util::applyVersionAndVariant($bytes, Version::GregorianTime);

        return new UuidV1($bytes);
    }

    public function createFromBytes(string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromBytesInternal($identifier);
    }

    public function createFromHexadecimal(string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    public function createFromInteger(int | string $identifier): UuidV1
    {
        /** @var UuidV1 */
        return $this->createFromIntegerInternal($identifier);
    }

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
