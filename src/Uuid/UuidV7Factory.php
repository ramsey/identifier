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

use DateTimeInterface;
use Override;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\RandomSourceFailure;
use Ramsey\Identifier\Service\BytesGenerator\TimeSourceFailure;
use Ramsey\Identifier\TimeBasedUuidFactory;
use Ramsey\Identifier\Uuid\Internal\StandardFactory;

use function sprintf;

/**
 * A factory for creating version 7, Unix Epoch time UUIDs
 */
final class UuidV7Factory implements TimeBasedUuidFactory
{
    use StandardFactory;

    /**
     * @param BytesGenerator $bytesGenerator A generator used to generate bytes
     *     for a version 7 UUID; defaults to {@see MonotonicBytesGenerator}
     */
    public function __construct(private readonly BytesGenerator $bytesGenerator = new MonotonicBytesGenerator())
    {
    }

    /**
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    #[Override]
    public function create(?DateTimeInterface $dateTime = null): UuidV7
    {
        if ($dateTime !== null && $dateTime->getTimestamp() < 0) {
            throw new InvalidArgument('Timestamp may not be earlier than the Unix Epoch');
        } elseif ($dateTime !== null && (int) $dateTime->format('Uv') > 0x000ffffffffffff) {
            throw new InvalidArgument(sprintf(
                'The date exceeds the maximum value allowed for Unix Epoch time UUIDs: %s',
                $dateTime->format('Y-m-d H:i:s.u P'),
            ));
        }

        $bytes = $this->bytesGenerator->bytes(dateTime: $dateTime);

        return UuidV7::fromRawBytes($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromBytes(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromBytesInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     * @throws RandomSourceFailure
     * @throws TimeSourceFailure
     */
    #[Override]
    public function createFromDateTime(DateTimeInterface $dateTime): UuidV7
    {
        return $this->create(dateTime: $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromHexadecimal(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromInteger(int | string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromIntegerInternal($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    #[Override]
    public function createFromString(string $identifier): UuidV7
    {
        /** @var UuidV7 */
        return $this->createFromStringInternal($identifier);
    }

    #[Override]
    private function getVersion(): Version
    {
        return Version::UnixTime;
    }

    #[Override]
    private function getUuidClass(): string
    {
        return UuidV7::class;
    }
}
