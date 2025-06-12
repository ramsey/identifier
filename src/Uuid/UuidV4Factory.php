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
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomBytesGenerator;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

/**
 * A factory for creating version 4, random UUIDs.
 */
final class UuidV4Factory implements UuidFactoryInterface
{
    use StandardFactory;

    private readonly Binary $binary;

    /**
     * @param BytesGenerator $bytesGenerator A random generator used to generate bytes; defaults to {@see RandomBytesGenerator}.
     */
    public function __construct(private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator())
    {
        $this->binary = new Binary();
    }

    public function create(): UuidV4
    {
        $bytes = $this->bytesGenerator->bytes();
        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::Random);

        return new UuidV4($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromStringInternal($identifier);
    }

    protected function getVersion(): Version
    {
        return Version::Random;
    }

    protected function getUuidClass(): string
    {
        return UuidV4::class;
    }
}
