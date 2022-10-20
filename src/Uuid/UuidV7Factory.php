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
use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\RandomGenerator\PhpRandomGenerator;
use Ramsey\Identifier\Service\RandomGenerator\RandomGenerator;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardUuidFactory;
use Ramsey\Identifier\Uuid\Utility\Time;
use StellaMaris\Clock\ClockInterface as Clock;

/**
 * A factory for creating version 7, Unix Epoch time UUIDs
 */
final class UuidV7Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardUuidFactory;

    private readonly Binary $binary;
    private readonly Time $time;

    /**
     * Constructs a factory for creating version 7, Unix Epoch time UUIDs
     *
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param RandomGenerator $randomGenerator A random generator used to
     *     generate bytes; defaults to {@see PhpRandomGenerator}
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly RandomGenerator $randomGenerator = new PhpRandomGenerator(),
    ) {
        $this->binary = new Binary();
        $this->time = new Time();
    }

    /**
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws RandomSourceNotFound
     */
    public function create(?DateTimeInterface $dateTime = null): UuidV7
    {
        $dateTime = $dateTime ?? $this->clock->now();

        $bytes = $this->time->getTimeBytesForUnixEpoch($dateTime)
            . $this->randomGenerator->bytes(10);
        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::UnixTime);

        return new UuidV7($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     * @throws RandomSourceNotFound
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV7
    {
        return $this->create(dateTime: $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
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
