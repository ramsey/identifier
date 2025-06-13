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

namespace Ramsey\Identifier\Ulid;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Ulid\Internal\Format;
use Ramsey\Identifier\Ulid\Internal\Mask;
use Ramsey\Identifier\Ulid\Internal\Validation;
use Ramsey\Identifier\UlidFactory as UlidFactoryInterface;
use Ramsey\Identifier\Uuid;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;
use function strtr;

use const PHP_INT_MAX;
use const STR_PAD_LEFT;

/**
 * A factory that generates ULIDs.
 *
 * @link https://github.com/ulid/spec ULID specification.
 * @see Ulid
 */
final class UlidFactory implements UlidFactoryInterface
{
    use Validation;

    /**
     * @param BytesGenerator $bytesGenerator A generator used to generate bytes; defaults to {@see MonotonicBytesGenerator}.
     */
    public function __construct(private readonly BytesGenerator $bytesGenerator = new MonotonicBytesGenerator())
    {
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): Ulid
    {
        return new Ulid($this->bytesGenerator->bytes());
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): MaxUlid | NilUlid | Ulid
    {
        if ($this->isMax($identifier, Format::Bytes)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::Bytes)) {
            return new NilUlid();
        }

        if ($this->isValid($identifier, Format::Bytes)) {
            return new Ulid($identifier);
        }

        throw new InvalidArgument('The identifier must be a 16-byte octet string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): Ulid
    {
        if ($dateTime->getTimestamp() < 0) {
            throw new InvalidArgument('Timestamp may not be earlier than the Unix Epoch');
        } elseif ((int) $dateTime->format('Uv') > 0x000ffffffffffff) {
            throw new InvalidArgument(sprintf(
                'The date exceeds the maximum value allowed for ULIDs: %s',
                $dateTime->format('Y-m-d H:i:s.u P'),
            ));
        }

        return new Ulid($this->bytesGenerator->bytes(dateTime: $dateTime));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): MaxUlid | NilUlid | Ulid
    {
        if ($this->isMax($identifier, Format::Hex)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::Hex)) {
            return new NilUlid();
        }

        if ($this->isValid($identifier, Format::Hex)) {
            return new Ulid($identifier);
        }

        throw new InvalidArgument('The identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): MaxUlid | NilUlid | Ulid
    {
        if (
            is_string($identifier)
            && strspn($identifier, Mask::INT) === strlen($identifier)
            && $identifier <= PHP_INT_MAX
        ) {
            $identifier = (int) $identifier;
        }

        if (is_int($identifier)) {
            if ($identifier < 0) {
                throw new InvalidArgument('Unable to create a ULID from a negative integer');
            }

            $bytes = pack('J', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgument('Unable to create a ULID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        try {
            return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
        } catch (InvalidArgument $exception) {
            throw new InvalidArgument(sprintf('Invalid ULID: %s', $identifier), previous: $exception);
        }
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): MaxUlid | NilUlid | Ulid
    {
        if ($this->isMax($identifier, Format::Ulid)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::Ulid)) {
            return new NilUlid();
        }

        $identifierEncoded = strtr($identifier, ...self::DECODE_SYMBOLS);

        if ($this->isValid($identifierEncoded, Format::Ulid)) {
            return new Ulid($identifierEncoded);
        }

        throw new InvalidArgument('The identifier must be a valid ULID string representation');
    }

    /**
     * Returns a ULID created from the value of a UUID.
     *
     * ULIDs are defined as being generated from a timestamp based on a count of milliseconds since the Unix Epoch. The
     * only type of UUID that is binary compatible with the ULID specification is version 7. Only version 7 UUIDs will
     * produce sortable ULIDs with meaningful timestamps.
     *
     * That said, any type of UUID may be converted to a ULID representation, though the ULID produced may not be
     * sortable or contain any meaningful timestamp information.
     */
    public function createFromUuid(Uuid $uuid): MaxUlid | NilUlid | Ulid
    {
        return $this->createFromBytes($uuid->toBytes());
    }

    /**
     * Creates a Max ULID with all bits set to one (1).
     */
    public function max(): MaxUlid
    {
        return new MaxUlid();
    }

    /**
     * Creates a Nil ULID with all bits set to zero (0).
     */
    public function nil(): NilUlid
    {
        return new NilUlid();
    }
}
