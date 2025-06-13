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

namespace Ramsey\Identifier\Uuid\Internal;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Throwable;

use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * Provides common methods for UUID factories.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait StandardFactory
{
    use Validation;

    /**
     * Returns the name of the UUID class to use when instantiating UUID instances from this trait.
     *
     * @return class-string<Uuid>
     */
    abstract protected function getUuidClass(): string;

    /**
     * @throws InvalidArgument
     */
    private function createFromBytesInternal(string $identifier): Uuid
    {
        if ($this->hasValidFormat($identifier, Format::Bytes)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('The identifier must be a 16-byte octet string');
    }

    /**
     * @throws InvalidArgument
     */
    private function createFromHexadecimalInternal(string $identifier): Uuid
    {
        if ($this->hasValidFormat($identifier, Format::Hex)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('The identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgument
     */
    private function createFromIntegerInternal(int | string $identifier): Uuid
    {
        try {
            $bigInteger = BigInteger::of($identifier);
        } catch (MathException $exception) {
            throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
        }

        try {
            return $this->createFromBytesInternal(str_pad($bigInteger->toBytes(false), 16, "\x00", STR_PAD_LEFT));
        } catch (Throwable $exception) {
            throw new InvalidArgument(
                sprintf('Invalid version %d UUID: %s', $this->getVersion()->value, $identifier),
                0,
                $exception,
            );
        }
    }

    /**
     * @throws InvalidArgument
     */
    private function createFromStringInternal(string $identifier): Uuid
    {
        if ($this->hasValidFormat($identifier, Format::String)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('The identifier must be a UUID in standard string representation');
    }
}
