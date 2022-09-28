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
use Ramsey\Identifier\Exception\RandomSourceException;
use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Identifier\Service\Random\RandomServiceInterface;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Identifier\Service\Time\TimeServiceInterface;
use Ramsey\Identifier\Uuid\Util;
use Ramsey\Identifier\Uuid\UuidV7;

/**
 * A factory for creating version 1, Gregorian time UUIDs
 */
final class UuidV7Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    public function __construct(
        private readonly RandomServiceInterface $randomService = new RandomBytesService(),
        private readonly TimeServiceInterface $timeService = new CurrentDateTimeService(),
    ) {
    }

    /**
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function create(?DateTimeInterface $dateTime = null): UuidV7
    {
        $dateTime = $dateTime ?? $this->timeService->getDateTime();

        $bytes = Util::getTimeBytesForUnixEpoch($dateTime)
            . $this->randomService->getRandomBytes(10);
        $bytes = Util::applyVersionAndVariant($bytes, Version::UnixTime);

        return new UuidV7($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromString(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromStringInternal($identifier);
    }

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::UnixTime;
    }

    protected function getUuidClass(): string
    {
        return UuidV7::class;
    }
}
