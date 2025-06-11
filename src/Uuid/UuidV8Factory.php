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
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

use function strlen;

/**
 * A factory for creating version 8, custom format UUIDs.
 */
final class UuidV8Factory implements UuidFactoryInterface
{
    use StandardFactory;

    private readonly Binary $binary;

    public function __construct()
    {
        $this->binary = new Binary();
    }

    /**
     * Creates a new instance of an identifier.
     *
     * The bytes provided may contain any value according to your application's needs. Be aware, however, that other
     * applications may not understand the format and meaning of the value.
     *
     * @param string | null $bytes A 16-byte octet string. This is an open blob of data that you may fill with 128 bits
     *     of information. Be aware, however, bits 48 through 51 will be replaced with the UUID version field, and bits
     *     64 and 65 will be replaced with the UUID variant. You MUST NOT rely on these bits for your application needs.
     *
     * @throws InvalidArgument if `$bytes` is null or is not a 16-byte octet string.
     */
    public function create(?string $bytes = null): UuidV8
    {
        if ($bytes === null) {
            throw new InvalidArgument('$bytes cannot be null when creating version 8 UUIDs');
        }

        if (strlen($bytes) !== 16) {
            throw new InvalidArgument('$bytes must be a 16-byte octet string');
        }

        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::Custom);

        return new UuidV8($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::Custom;
    }

    protected function getUuidClass(): string
    {
        return UuidV8::class;
    }
}
