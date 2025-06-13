<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;
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
        if ($bytes === null || strlen($bytes) !== 16) {
            throw new InvalidArgument('To create a version 8 UUID, the bytes must be a 16-byte octet string');
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
