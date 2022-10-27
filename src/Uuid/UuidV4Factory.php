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
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomBytesGenerator;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\StandardFactory;

/**
 * A factory for creating version 4, random UUIDs
 */
final class UuidV4Factory implements BinaryIdentifierFactory, IntegerIdentifierFactory, StringIdentifierFactory
{
    use StandardFactory;

    private readonly Binary $binary;

    /**
     * Constructs a factory for creating version 4, random UUIDs
     *
     * @param BytesGenerator $bytesGenerator A random generator used to
     *     generate bytes; defaults to {@see RandomBytesGenerator}
     */
    public function __construct(
        private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator(),
    ) {
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

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::Random;
    }

    protected function getUuidClass(): string
    {
        return UuidV4::class;
    }
}
