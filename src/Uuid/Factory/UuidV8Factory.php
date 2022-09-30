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
use Identifier\Uuid\Version;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\UuidV8;

use function hex2bin;
use function sprintf;
use function strspn;

/**
 * A factory for creating version 8, custom UUIDs
 */
final class UuidV8Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    /**
     * Creates a new instance of an identifier
     *
     * The three custom fields, A, B, and C, may contain any values according to
     * your application's needs. Be aware, however, that other implementations
     * may not understand the semantics of the values.
     *
     * @param string | null $customFieldA An arbitrary 48-bit (12-character)
     *     hexadecimal string
     * @param string | null $customFieldB An arbitrary 12-bit (3-character)
     *     hexadecimal string
     * @param string | null $customFieldC An arbitrary 64-bit (16-character)
     *     hexadecimal string (if set, the 2 most significant bits will be lost,
     *     since they are replaced with the variant bits, so don't rely on these
     *     bits to hold any important data; in other words, treat this as a
     *     62-bit value)
     *
     * @throws InvalidArgumentException
     */
    public function create(
        ?string $customFieldA = null,
        ?string $customFieldB = null,
        ?string $customFieldC = null,
    ): UuidV8 {
        if ($customFieldA === null) {
            throw new InvalidArgumentException('$customFieldA cannot be null when creating version 8 UUIDs');
        }

        if ($customFieldB === null) {
            throw new InvalidArgumentException('$customFieldB cannot be null when creating version 8 UUIDs');
        }

        if ($customFieldC === null) {
            throw new InvalidArgumentException('$customFieldC cannot be null when creating version 8 UUIDs');
        }

        $customFieldA = sprintf('%012s', $customFieldA);
        $customFieldB = sprintf('%03s', $customFieldB);
        $customFieldC = sprintf('%016s', $customFieldC);

        if (strspn($customFieldA, Format::MASK_HEX) !== 12) {
            throw new InvalidArgumentException('$customFieldA must be a 48-bit hexadecimal string');
        }

        if (strspn($customFieldB, Format::MASK_HEX) !== 3) {
            throw new InvalidArgumentException('$customFieldB must be a 12-bit hexadecimal string');
        }

        if (strspn($customFieldC, Format::MASK_HEX) !== 16) {
            throw new InvalidArgumentException('$customFieldC must be a 62-bit hexadecimal string');
        }

        /** @psalm-var non-empty-string $bytes */
        $bytes = hex2bin($customFieldA . '0' . $customFieldB . $customFieldC);

        $bytes = Binary::applyVersionAndVariant($bytes, Version::Custom);

        return new UuidV8($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromString(string $identifier): UuidV8
    {
        /** @var UuidV8 */
        return $this->createFromStringInternal($identifier);
    }

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::Custom;
    }

    protected function getUuidClass(): string
    {
        return UuidV8::class;
    }
}
