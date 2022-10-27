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

use Identifier\BinaryIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;

use function hash;

/**
 * A factory for creating version 3, name-based (MD5) UUIDs
 */
final class UuidV3Factory implements BinaryIdentifierFactory, IntegerIdentifierFactory, StringIdentifierFactory
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
    public function create(?Uuid $namespace = null, ?string $name = null): UuidV3
    {
        if ($namespace === null) {
            throw new InvalidArgument('$namespace cannot be null when creating version 3 UUIDs');
        }

        if ($name === null) {
            throw new InvalidArgument('$name cannot be null when creating version 3 UUIDs');
        }

        $bytes = hash('md5', $namespace->toBytes() . $name, true);
        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::HashMd5);

        return new UuidV3($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
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
