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
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

use function hash;

/**
 * A factory for creating version 3, name-based (MD5) UUIDs.
 */
final class UuidV3Factory implements UuidFactoryInterface
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
            throw new InvalidArgument('When creating version 3 UUIDs, the namespace cannot be null');
        }

        if ($name === null) {
            throw new InvalidArgument('When creating version 3 UUIDs, the name cannot be null');
        }

        $bytes = hash('md5', $namespace->toBytes() . $name, true);
        $bytes = $this->binary->applyVersionAndVariant($bytes, Version::NameMd5);

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
    public function createFromHexadecimal(string $identifier): UuidV3
    {
        /** @var UuidV3 */
        return $this->createFromHexadecimalInternal($identifier);
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

    protected function getVersion(): Version
    {
        return Version::NameMd5;
    }

    protected function getUuidClass(): string
    {
        return UuidV3::class;
    }
}
