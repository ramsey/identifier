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

use Identifier\Uuid\UuidFactoryInterface;
use Identifier\Uuid\UuidInterface;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\UuidV3;

use function hash;

/**
 * A factory for creating version 3, name-based (MD5) UUIDs
 */
final class UuidV3Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    /**
     * @throws InvalidArgumentException
     */
    public function create(?UuidInterface $namespace = null, ?string $name = null): UuidV3
    {
        if ($namespace === null) {
            throw new InvalidArgumentException('$namespace cannot be null when creating version 3 UUIDs');
        }

        if ($name === null) {
            throw new InvalidArgumentException('$name cannot be null when creating version 3 UUIDs');
        }

        /** @psalm-var non-empty-string $bytes */
        $bytes = hash('md5', $namespace->toBytes() . $name, true);
        $bytes = Binary::applyVersionAndVariant($bytes, Version::HashMd5);

        return new UuidV3($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromString(string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromStringInternal($identifier);
    }

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::HashMd5;
    }

    protected function getUuidClass(): string
    {
        return UuidV3::class;
    }
}
