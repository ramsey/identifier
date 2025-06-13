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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Mask;
use Ramsey\Identifier\Uuid\Internal\Validation;
use Ramsey\Identifier\UuidFactory as UuidFactoryInterface;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;

use const PHP_INT_MAX;
use const STR_PAD_LEFT;

/**
 * A factory that generates UUIDs.
 */
final class UuidFactory implements UuidFactoryInterface
{
    use Validation;

    public function __construct(
        private readonly UuidV1Factory $v1Factory = new UuidV1Factory(),
        private readonly UuidV2Factory $v2Factory = new UuidV2Factory(),
        private readonly UuidV3Factory $v3Factory = new UuidV3Factory(),
        private readonly UuidV4Factory $v4Factory = new UuidV4Factory(),
        private readonly UuidV5Factory $v5Factory = new UuidV5Factory(),
        private readonly UuidV6Factory $v6Factory = new UuidV6Factory(),
        private readonly UuidV7Factory $v7Factory = new UuidV7Factory(),
        private readonly UuidV8Factory $v8Factory = new UuidV8Factory(),
    ) {
    }

    public function create(): UuidV4
    {
        return $this->v4Factory->create();
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === Format::Bytes->value) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('The identifier must be a 16-byte octet string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === Format::Hex->value) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('The identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UntypedUuid
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
                throw new InvalidArgument('Unable to create a UUID from a negative integer');
            }

            $bytes = pack('J', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgument('Unable to create a UUID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === 36) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('The identifier must be a UUID in standard string representation (with dashes)');
    }

    /**
     * Creates a Max UUID with all bits set to one (1).
     */
    public function max(): MaxUuid
    {
        return new MaxUuid();
    }

    /**
     * Creates a Nil UUID with all bits set to zero (0).
     */
    public function nil(): NilUuid
    {
        return new NilUuid();
    }

    /**
     * Creates a version 1, Gregorian time UUID.
     *
     * @param int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 16,384, giving it an
     *     effective range of 0-16383 (i.e., 14 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument if parameters are not legal values.
     */
    public function v1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return $this->v1Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 2, DCE Security UUID.
     *
     * @param DceDomain $localDomain The local domain to which the local identifier belongs; this MUST default to a
     *     suitable domain for the implementation.
     * @param int<0, 4294967295> | null $localIdentifier A 32-bit local identifier belonging to the local domain
     *     specified in `$localDomain`; if no identifier is provided, the factory SHOULD attempt to get a suitable local
     *     ID for the domain (e.g., the UID or GID of the user running the script).
     * @param int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 64, giving it an
     *     effective range of 0-63 (i.e., 6 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument if parameters are not legal values.
     * @throws DceIdentifierNotFound if unable to obtain a DCE identifier.
     */
    public function v2(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        return $this->v2Factory->create($localDomain, $localIdentifier, $node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 3, name-based (MD5) UUID.
     *
     * @param string | Uuid $namespace The UUID namespace to use when creating this version 3 identifier.
     * @param string $name The name used to create the version 3 identifier in the given namespace.
     *
     * @throws InvalidArgument if parameters are not legal values.
     */
    public function v3(string | Uuid $namespace, string $name): UuidV3
    {
        if (!$namespace instanceof Uuid) {
            $namespace = match (strlen($namespace)) {
                Format::String->value => $this->createFromString($namespace),
                Format::Hex->value => $this->createFromHexadecimal($namespace),
                Format::Bytes->value => $this->createFromBytes($namespace),
                default => throw new InvalidArgument(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->v3Factory->create($namespace, $name);
    }

    /**
     * Creates a version 4, random UUID.
     */
    public function v4(): UuidV4
    {
        return $this->v4Factory->create();
    }

    /**
     * Creates a version 5, name-based (SHA-1) UUID.
     *
     * @param string | Uuid $namespace The UUID namespace to use when creating this version 5 identifier.
     * @param string $name The name used to create the version 5 identifier in the given namespace.
     *
     * @throws InvalidArgument if parameters are not legal values.
     */
    public function v5(string | Uuid $namespace, string $name): UuidV5
    {
        if (!$namespace instanceof Uuid) {
            $namespace = match (strlen($namespace)) {
                Format::String->value => $this->createFromString($namespace),
                Format::Hex->value => $this->createFromHexadecimal($namespace),
                Format::Bytes->value => $this->createFromBytes($namespace),
                default => throw new InvalidArgument(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->v5Factory->create($namespace, $name);
    }

    /**
     * Creates a version 6, reordered Gregorian time UUID.
     *
     * @param int<0, 281474976710655> | non-empty-string | null $node A 48-bit integer or hexadecimal string
     *     representing the hardware address of the machine where this identifier was generated.
     * @param int | null $clockSequence A number used to help avoid duplicates that could arise when the clock is set
     *     backwards in time or the node ID changes; we take the modulo of this integer divided by 16,384, giving it an
     *     effective range of 0-16383 (i.e., 14 bits).
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument if parameters are not legal values.
     */
    public function v6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        return $this->v6Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 7, Unix Epoch time UUID.
     *
     * @param DateTimeInterface | null $dateTime A date-time to use when creating the identifier.
     *
     * @throws InvalidArgument if `$dateTime` is not a legal value.
     */
    public function v7(?DateTimeInterface $dateTime = null): UuidV7
    {
        return $this->v7Factory->create($dateTime);
    }

    /**
     * Creates a version 8, custom format UUID.
     *
     * The bytes provided may contain any value according to your application's needs. Be aware, however, that other
     * applications may not understand the format and meaning of the value.
     *
     * @param string $bytes A 16-byte octet string. This is an open blob of data that you may fill with 128 bits of
     *     information. Be aware, however, bits 48 through 51 will be replaced with the UUID version field, and bits 64
     *     and 65 will be replaced with the UUID variant. Your application SHOULD NOT set these bits, since they will be
     *     overwritten.
     *
     * @throws InvalidArgument if `$bytes` is not a legal value.
     */
    public function v8(string $bytes): UuidV8
    {
        return $this->v8Factory->create($bytes);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getVersion(): never
    {
        throw new BadMethodCall('method called out of context');
    }
}
