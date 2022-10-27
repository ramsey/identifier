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
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;

/**
 * A factory for creating version 7, Unix Epoch time UUIDs
 */
final class UuidV7Factory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardFactory;

    private readonly Binary $binary;

    /**
     * Constructs a factory for creating version 7, Unix Epoch time UUIDs
     *
     * @param BytesGenerator $bytesGenerator A bytes generator used to
     *     generate bytes for a version 7 UUID; defaults to
     *     {@see MonotonicBytesGenerator}
     */
    public function __construct(
        private readonly BytesGenerator $bytesGenerator = new MonotonicBytesGenerator(),
    ) {
        $this->binary = new Binary();
    }

    /**
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     */
    public function create(?DateTimeInterface $dateTime = null): UuidV7
    {
        if ($dateTime !== null && $dateTime->getTimestamp() < 0) {
            throw new InvalidArgument('Timestamp may not be earlier than the Unix Epoch');
        }

        $bytes = $this->bytesGenerator->bytes(dateTime: $dateTime);
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
