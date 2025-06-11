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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

use function hash;
use function substr;

/**
 * A factory for creating version 5, name-based (SHA-1) UUIDs.
 */
final class UuidV5Factory implements UuidFactoryInterface
{
    use StandardFactory;

    private readonly Binary $binary;

    public function __construct()
    {
        $this->binary = new Binary();
    }

    /**
     * @throws InvalidArgument
     */
    public function create(?Uuid $namespace = null, ?string $name = null): UuidV5
    {
        if ($namespace === null) {
            throw new InvalidArgument('$namespace cannot be null when creating version 5 UUIDs');
        }

        if ($name === null) {
            throw new InvalidArgument('$name cannot be null when creating version 5 UUIDs');
        }

        /** @var non-empty-string $bytes */
        $bytes = substr(hash('sha1', $namespace->toBytes() . $name, true), 0, 16);
        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::NameSha1);

        return new UuidV5($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV5
    {
        /** @var UuidV5 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV5
    {
        /** @var UuidV5 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV5
    {
        /** @var UuidV5 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV5
    {
        /** @var UuidV5 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::NameSha1;
    }

    protected function getUuidClass(): string
    {
        return UuidV5::class;
    }
}
