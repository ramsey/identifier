<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Override;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomBytesGenerator;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

/**
 * A factory for creating version 4, random UUIDs
 */
final class UuidV4Factory implements UuidFactoryInterface
{
    use StandardFactory;

    /**
     * @param BytesGenerator $bytesGenerator A random generator used to generate
     *     bytes; defaults to {@see RandomBytesGenerator}
     */
    public function __construct(private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator())
    {
    }

    #[Override]
    public function create(): UuidV4
    {
        $bytes = $this->bytesGenerator->bytes();

        return UuidV4::fromRawBytes($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromBytes(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromHexadecimal(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromInteger(int | string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromString(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromStringInternal($identifier);
    }

    #[Override]
    private function getVersion(): Version
    {
        return Version::Random;
    }

    #[Override]
    private function getUuidClass(): string
    {
        return UuidV4::class;
    }
}
